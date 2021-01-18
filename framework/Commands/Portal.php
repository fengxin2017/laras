<?php

namespace Laras\Commands;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laras\Foundation\Application;
use Laras\Server\HttpServer;
use Laras\Server\TcpServer;
use Laras\Server\WebsocketServer;
use Laras\Watcher\Inotify;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Process\Pool;
use Swoole\Timer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Portal extends Command
{
    /**
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * @var SymfonyStyle $style
     */
    protected $style;

    /**
     * @var Table $table
     */
    protected $table;

    /**
     * @var int $pid
     */
    protected $pid;

    /**
     * @var string $hash
     */
    protected $hash;

    /**
     * @var bool $reloading
     */
    protected $reloading = false;

    /**
     * @var null
     */
    protected $watchConfig = null;

    /**
     * Portal constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->openSwooleHook();

        $configFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'reloader.php';

        if (is_file($configFile)) {
            $this->watchConfig = require "{$configFile}";
            if ($this->watchConfig['driver'] == 'hash') {
                $this->hash = $this->watch($this->watchConfig['hash']['watch_path']);
            }
        }

        $this->pid = getmypid();
        parent::__construct();
    }

    protected function openSwooleHook()
    {
        Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);
    }

    protected function configure()
    {
        $this->setName('start');
        $this->setDescription('Laras server start command.');
        $this->setHelp(sprintf('%s ./bin/laras start', PHP_BINARY));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->style = new SymfonyStyle($this->input, $this->output);

        try {
            if ($this->watchConfig['driver'] == 'inotify') {
                $this->addInotifyProcess();
            } else {
                $this->addFileWatchProcess();
            }

            return $this->start();
        } catch (Exception $e) {
            $error = sprintf(
                'Uncaught exception "%s"([%d]%s) at %s:%s, %s%s',
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                PHP_EOL,
                $e->getTraceAsString()
            );
            $this->error($error);
            return 1;
        }
    }

    protected function addFileWatchProcess()
    {
        $process = new Process(
            function () {
                Timer::tick(
                    1000,
                    function () {
                        if ($this->reloading == false) {
                            $this->reloading = true;
                            $hash = $this->watch($this->watchConfig['hash']['watch_path']);
                            if ($hash != $this->hash) {
                                $ret = Process::kill($this->pid, SIGUSR1);
                                $this->hash = $hash;
                                if ($ret) {
                                    Timer::after(
                                        100,
                                        function () {
                                            $this->reloading = false;
                                        }
                                    );
                                } else {
                                    throw new Exception('realod failed!');
                                }
                            } else {
                                $this->reloading = false;
                            }
                        }
                    }
                );
            }
        );

        $process->start();
    }

    /**
     * @param array $items
     * @param string $hash
     * @return string
     * @throws Exception
     */
    protected function watch(array $items, string $hash = '')
    {
        foreach ($items as $item) {
            if (is_dir($item)) {
                $hash .= $this->md5Folder($item);
            } elseif (is_file($item)) {
                $hash .= md5_file($item);
            } else {
                throw new Exception(sprintf('file or dir [%s] does not exists!', $item));
            }
        }

        return $hash;
    }

    /**
     * @param string $dir
     * @return bool|string
     */
    protected function md5Folder(string $dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $filemd5s = [];
        $d = dir($dir);

        while (false !== ($entry = $d->read())) {
            if ($entry != '.' && $entry != '..') {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                    $filemd5s[] = $this->md5Folder($dir . DIRECTORY_SEPARATOR . $entry);
                } else {
                    $filemd5s[] = md5_file($dir . DIRECTORY_SEPARATOR . $entry);
                }
            }
        }
        $d->close();
        return md5(implode('', $filemd5s));
    }

    /**
     * @throws Exception
     */
    protected function addInotifyProcess()
    {
        if (!extension_loaded('inotify')) {
            throw new Exception('Require extension inotify');
        }

        $fileTypes = isset($this->watchConfig['file_types']) ? (array)$this->watchConfig['file_types'] : [];
        if (empty($fileTypes)) {
            throw new Exception('No file types to watch by inotify');
        }

        $callback = function () {
            $inotify = new Inotify(
                $this->watchConfig['watch_path'], IN_CREATE | IN_DELETE | IN_MODIFY | IN_MOVE,
                function ($event) {
                    Process::kill($this->pid, SIGUSR1);
                }
            );
            $inotify->addFileTypes($this->watchConfig['file_types']);
            if (empty($this->watchConfig['excluded_dirs'])) {
                $this->watchConfig['excluded_dirs'] = [];
            }

            $inotify->addExcludedDirs($this->watchConfig['excluded_dirs']);
            $inotify->watch();
            $inotify->start();
        };

        $process = new Process($callback, false, 1, true);
        $process->start();
    }

    /**
     * @throws Exception
     */
    public function start()
    {
        $serverConfig = require ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'server.php';
        $serverTypes = [];
        $httpWorkerNumber = (int)($serverConfig['http']['worker_number'] ?? 0);
        $tcpWorkerNumber = (int)($serverConfig['tcp']['worker_number'] ?? 0);
        $webSocketWorkerNumber = (int)($serverConfig['websocket']['worker_number'] ?? 0);
        $poolNumber = $httpWorkerNumber + $tcpWorkerNumber + $webSocketWorkerNumber;
        while ($httpWorkerNumber > 0) {
            $serverTypes[] = 'http';
            $httpWorkerNumber--;
        }

        while ($tcpWorkerNumber > 0) {
            $serverTypes[] = 'tcp';
            $tcpWorkerNumber--;
        }

        while ($webSocketWorkerNumber > 0) {
            $serverTypes[] = 'webSocket';
            $webSocketWorkerNumber--;
        }

        $this->table = new Table($this->output);
        $this->showLogo();
        $this->showComponents($serverConfig);
        $pool = new Pool($poolNumber, SWOOLE_IPC_UNIXSOCK, 0, true);

        $pool->on(
            'WorkerStart',
            function (Pool $pool, $workerId) use ($serverConfig, $serverTypes) {
                /**@var Process $worker */
                $worker = $pool->getProcess();
                $type = $serverTypes[$workerId];

                $onWorkerStartHandler = $serverConfig[$type]['on_worker_start'] ?? [];
                if (!empty($onWorkerStartHandler)) {
                    call_user_func_array([$onWorkerStartHandler[0], $onWorkerStartHandler[1]], [$pool, $worker]);
                }

                $this->$type($pool, $worker);
            }
        );
        $pool->on(
            'WorkerStop',
            function (Pool $pool, $workerId) use ($serverConfig, $serverTypes) {
                $worker = $pool->getProcess();
                $type = $serverTypes[$workerId];

                $onWorkerStopHandler = $serverConfig[$type]['on_worker_stop'] ?? [];
                if (!empty($onWorkerStopHandler)) {
                    call_user_func_array([$onWorkerStopHandler[0], $onWorkerStopHandler[1]], [$pool, $worker]);
                }
            }
        );

        $pool->start();

        return 0;
    }


    protected function showLogo()
    {
        $this->style->writeln("<comment>>>>Logo</comment>");
        static $logo = <<<BTC
 _                      __ 
| |                   /  __|
| |    __ _ _ __ __ _|  (__  
| |   / _` | '__/ _` |\___  \ 
| |__| (_| | | | (_| |____)  |
|_____\__,_|_|  \__,_|_____ / 
                                           
BTC;
        $colors = ['red', 'green', 'yellow', 'blue', 'magenta', 'cyan'];

        $this->style->writeln(sprintf("<fg=%s;bg=black>%s</>", $colors[rand(0, count($colors) - 1)], $logo));
    }

    protected function showComponents(array $serverConfig = [])
    {
        $this->style->writeln("<comment>>>> Components</comment>");

        $this->table->setHeaders(['Components', 'Version'])
            ->setRows(
                [
                    ['PHP', phpversion()],
                    ['Swoole', swoole_version()],
                ]
            );
        if (isset($serverConfig['tcp']['worker_number']) && $serverConfig['tcp']['worker_number'] != 0) {
            $this->table->addRow([
                'TCP', $serverConfig['tcp']['listen'] . ':' . $serverConfig['tcp']['port']
            ]);
            $this->table->addRow([
                'WorkerNumber', 'X' . $serverConfig['tcp']['worker_number'],
            ]);
        }

        if (isset($serverConfig['http']['worker_number']) && $serverConfig['http']['worker_number'] != 0) {
            $this->table->addRow([
                'HTTP', $serverConfig['http']['listen'] . ':' . $serverConfig['http']['port']
            ]);
            $this->table->addRow([
                'WorkerNumber', 'X' . $serverConfig['http']['worker_number']
            ]);
        }

        if (isset($serverConfig['websocket']['worker_number']) && $serverConfig['websocket']['worker_number'] != 0) {
            $this->table->addRow([
                'WEBSOCKET', $serverConfig['websocket']['listen'] . ':' . $serverConfig['websocket']['port']
            ]);
            $this->table->addRow([
                'WorkerNumber', 'X' . $serverConfig['websocket']['worker_number']
            ]);
        }

        $this->table->render();
    }

    /**
     * @param Pool $pool
     * @param Process $worker
     * @throws BindingResolutionException
     * @throws ReflectionException
     * @throws Exception
     */
    public function http(Pool $pool, Process $worker)
    {
        (new Application($pool, $worker, HttpServer::class))->runHttpServer();
    }

    /**
     * @param Pool $pool
     * @param Process $worker
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function tcp(Pool $pool, Process $worker)
    {
        (new Application($pool, $worker, TcpServer::class))->runTcpServer();
    }

    /**
     * @param Pool $pool
     * @param Process $worker
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function websocket(Pool $pool, Process $worker)
    {
        (new Application($pool, $worker, WebsocketServer::class))->runWebSocketServer();
    }

    public function log(string $msg, string $type = 'INFO')
    {
        $msg = sprintf('[%s] [%s] %s', date('Y-m-d H:i:s'), $type, $msg);

        switch (strtoupper($type)) {
            case 'INFO':
                $this->style->writeln("<info>$msg</info>");
                break;
            case 'WARNING':
                if (!$this->style->getFormatter()->hasStyle('warning')) {
                    $this->style->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
                }
                $this->style->writeln("<warning>$msg</warning>");
                break;
            case 'ERROR':
                $this->style->writeln("<error>$msg</error>");
                break;
            case 'TRACE':
            default:
                $this->style->writeln($msg);
                break;
        }
    }

    public function trace($msg)
    {
        $this->log($msg, 'TRACE');
    }

    public function info($msg)
    {
        $this->log($msg, 'INFO');
    }

    public function warning($msg)
    {
        $this->log($msg, 'WARNING');
    }

    public function error($msg)
    {
        $this->log($msg, 'ERROR');
    }
}