<?php


namespace App\Http\Controllers;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Jim;
use App\Http\Middleware\RateLimitor;
use App\Http\Middleware\Tool;
use App\Jobs\FooJob;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Laras\Facades\Auth;
use Laras\Facades\DB;
use Laras\Facades\Storage;
use Laras\Facades\View;
use Laras\Http\Request;
use Laras\Http\Response;
use Laras\Support\Annotation\Middleware;

/**
 * Class TestController
 * @package App\Http\Controllers
 */
class HttpController extends BaseController
{
    public function index(Request $request)
    {
        //return 3333;
//        var_dump($request->get('foo'));
        //$user = User::query()->first();
        //return $user;
        DB::table('user')->first();

//        return $user;
//        return DB::table('users')->get();
//        $user = User::query()->first();
        return '333333555555556669999';
    }

    public function login(Request $request)
    {
        $user = User::find(1);
        return Auth::jwtloginUser($user, 3600);
    }

    public function testAuth()
    {
        $client = new Client();
        $response = $client->request('get','192.168.10.10:9501/auth',[
            'headers' => [
                'token' => 'eyJpdiI6Ikd3ZU5CR0RGQUNLNlZFR1ZxOThrNUE9PSIsInZhbHVlIjoiQ3M0L0QxZ0M2Qkc4a3ZPdjd5RHNJODJVZlM5Y0Y4cWZQMTJCbkhWQkp2T2tjWnVPN3VCV2tITVJnWFEwa0RrUCIsIm1hYyI6IjRkMjc4ZWNhNzQxNzdlYjJjODRhNzAzYmNmMmM1YTdmNzQ4NjE2YjU4MmJlYWYyNzI2MTNhMDE0Y2M2Y2FiZjkifQ=='
            ]
        ]);

        return json_decode($response->getBody()->getContents(),true);
    }

    /**
     * @Middleware(Authenticate::class)
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request)
    {
        return \Laras\Facades\Request::user();
        return $request->user();
        return Auth::user();
    }

    /**
     * http://192.168.10.10:9503/inject/fenxin?foo=bar
     *
     * @param Request $request
     * @param Response $response
     * @param string $name
     */
    public function inject(Request $request, Response $response, string $name)
    {
        var_dump($name);
        var_dump($request->get());
        var_dump($this->foo->getName());
    }

    public function response(Response $response, Request $request)
    {
        //Mail::to('2169046620@qq.com')->send(new TestMail());
        // 响应一个字符串
        return 'string';
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
        return Response::route('test/test');
        // 全路径跳转
        return Response::redirect();
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
        return 'done11';
    }

    /**
     * 传递参数到middleware必须使用{}形式
     * @Middleware({RateLimitor::class:"1,2",Jim::class})
     * @return string
     */
    public function ratelimit()
    {
        var_dump('controller->call');
        return 'test';
    }

    /**
     * @throws Exception
     */
    public function event()
    {
        \App\Events\Foo::dispatch('this is foo');
    }

    /**
     * @throws BindingResolutionException
     */
    public function job()
    {
        FooJob::dispatch(['name' => 'take idea!'])->delay(Carbon::now()->addSeconds(4));
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
