<?php

namespace Laras\Config;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Support\ServiceProvider;
use Laras\Foundation\Application;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;

    public function register()
    {
        $this->app->instance(RepositoryContract::class, $repository = new Repository());
        $this->loadConfigurationFiles($repository);
        $this->app->alias(RepositoryContract::class, 'config');
        $this->app['env'] = $repository['app.env'] ?? 'local';
    }

    /**
     * @param Repository $repository
     */
    public function loadConfigurationFiles(Repository $repository)
    {
        $files = $this->getConfigurationFiles();

        foreach ($files as $key => $path) {
            $repository->set($key, require "{$path}");
        }
    }

    /**
     * @return array
     */
    protected function getConfigurationFiles(): array
    {
        $files = [];

        $configPath = realpath($this->app->configPath());

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            /**@var SplFileInfo $file */
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory . basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * @param SplFileInfo $file
     * @param $configPath
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested) . '.';
        }

        return $nested;
    }

    public function boot()
    {

    }
}