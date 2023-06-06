<?php


namespace App\Http\Controllers;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Jim;
use App\Http\Middleware\RateLimitor;
use App\Http\Middleware\Tool;
use App\Jobs\FooJob;
use App\Mails\TestMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Laras\Contracts\Auth\Authenticatable;
use Laras\Facades\Auth;
use Laras\Facades\DB;
use Laras\Facades\Mail;
use Laras\Facades\Storage;
use Laras\Facades\View;
use Laras\Http\Request;
use Laras\Http\Response;
use Laras\Support\Annotation\Inject;
use Laras\Support\Annotation\Middleware;

/**
 * Class TestController
 * @package App\Http\Controllers
 */
class HttpController extends BaseController
{
    /**
     * @Inject(Client::class)
     * @var Client $client
     */
    protected $client;

    public function index(Request $request, Response $response, string $name)
    {
        var_dump($name);
        var_dump($request->get());
        return compact('name');
    }

    public function login(Request $request)
    {
        /**@var Authenticatable $user */
        $user = User::query()->find(1);
        return Auth::jwtloginUser($user, 3600);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testAuth()
    {
        $response = $this->client->request('get', '192.168.10.10:9501/auth', [
            'headers' => [
                'token' => 'eyJpdiI6Ikd3ZU5CR0RGQUNLNlZFR1ZxOThrNUE9PSIsInZhbHVlIjoiQ3M0L0QxZ0M2Qkc4a3ZPdjd5RHNJODJVZlM5Y0Y4cWZQMTJCbkhWQkp2T2tjWnVPN3VCV2tITVJnWFEwa0RrUCIsIm1hYyI6IjRkMjc4ZWNhNzQxNzdlYjJjODRhNzAzYmNmMmM1YTdmNzQ4NjE2YjU4MmJlYWYyNzI2MTNhMDE0Y2M2Y2FiZjkifQ=='
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @Middleware(Authenticate::class)
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request)
    {
        return \Laras\Facades\Request::user();
        //return $request->user();
        //return Auth::user();
    }

    public function response(Response $response, Request $request)
    {
        // $foo = $request->get('foo'));
        // $foo = \Laras\Facades\Request::get('foo');
        // $post = $request->post();
        // $file = $request->file('file');
        // 响应一个字符串
        return 'string';
        // 发送邮件
        Mail::to('2169046620@qq.com')->send(new TestMail());
        return 'done';
        // 响应DB
        return DB::table('users')->first();
        // 响应ORM分页
        return User::query()->paginate(2, '*', 'page', 3);
        // 定义响应头、COOKIE、响应体等
        return $response->setContent(['foo' => 'bar'])->setHeader('Content-type', 'application/json');
        // 直接返回数组。
        return ['foo' => 'bar'];
        // 响应laravel的blade模板
        return View::make('test', ['foo' => 'bar']);
        // 助手函数方式调用响应试图
        return view('foo', ['name' => 'test']);
        // 下载文件
        return $response->download(storage_path('app/file.txt'));
        // 跳转uri
        return Response::route('test');
        // 全路径跳转
        return Response::redirect('https://learnku.com/laravel');
        // 指定驱动下载文件  自带 AWS 、 FTP 、SFTP 3种驱动
        return Storage::disk('local')->download('file.txt');

        var_dump(Storage::disk('local')->url('file.txt'));
        // 文件上传
        Storage::disk('public')->put('file.txt', 'tests');

        var_dump(env('APP_ENV'));
    }

    /**
     * @Middleware({Jim::class,Tool::class:"2,4"})
     * @param Response $response
     * @return mixed
     * @throws Exception
     */
    public function middleware(Response $response)
    {
        return 'done';
    }

    /**
     * 限流传递参数到middleware必须使用{}形式
     * @Middleware({RateLimitor::class:"1,2",Jim::class})
     * @return string
     */
    public function ratelimit()
    {
        var_dump('controller->call');
        return 'test';
    }

    /**
     * 事件
     * @throws Exception
     */
    public function event()
    {
        \App\Events\Foo::dispatch('this is foo');
    }

    /**
     * 任务
     * @throws BindingResolutionException
     */
    public function job()
    {
        $count = 100;
        var_dump(Carbon::now()->toDateTimeString());
        while ($count > 0) {
            FooJob::dispatch(['name' => 'take idea on test queue!->>>>' . $count])->delay(Carbon::now()->addSeconds(4))->onQueue('test');
            FooJob::dispatch(['name' => 'take idea on default queue!->>>>' . $count])->delay(Carbon::now()->addSeconds(5));
            $count--;
        }
        return 'done';
    }

    /**
     * http://192.168.10.10:9501/validates?id=3&name=foo
     *
     * @param Request $request
     * @return string
     * @throws ValidationException
     */
    public function validates(Request $request)
    {
        var_dump($request->all());
        $this->validate(
            $request->all(),
            [
                'id' => 'required|unique:users',
                'name' => 'required'
            ]
        );

        return 'test';
    }
}
