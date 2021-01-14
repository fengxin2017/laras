<?php

use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use MoneyMaker\Annotation\AnnotationServiceProvider;
use MoneyMaker\Auth\AuthServiceProvider;
use MoneyMaker\Crontab\CrontabServiceProvider;
use MoneyMaker\Database\DatabaseServiceProvider;
use MoneyMaker\Redis\RedisServiceProvider;
use App\Providers\RouterServiceProvider;
use MoneyMaker\Support\RateLimitorServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool)env('APP_DEBUG', true),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'name' => 'moneymaker',
    /*
    |--------------------------------------------------------------------------
    | Application Debug In Console
    |--------------------------------------------------------------------------
    |
    */
    'debug_inconsole' => true,

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'zh_CN',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    'providers' => [
        /*
         * MoneyMaker framework Service Providers...
         */
        AnnotationServiceProvider::class,
        LogServiceProvider::class,
        DatabaseServiceProvider::class,
        RedisServiceProvider::class,
        AuthServiceProvider::class,
        RouterServiceProvider::class,
        FilesystemServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        RateLimitorServiceProvider::class,
        CrontabServiceProvider::class,
        ViewServiceProvider::class,
        MailServiceProvider::class,
        HashServiceProvider::class
        /*
         * Application Service Providers...
         */
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */
    'aliases' => [
        'Arr' => \Illuminate\Support\Arr::class,
        'Auth' => \MoneyMaker\Facades\Auth::class,
        'Config' => \MoneyMaker\Facades\Config::class,
        'DB' => \MoneyMaker\Facades\DB::class,
        'Log' => \MoneyMaker\Facades\Log::class,
        'Redis' => \MoneyMaker\Facades\Redis::class,
        'Request' => \MoneyMaker\Facades\Request::class,
        'Response' => \MoneyMaker\Facades\Response::class,
        'Translator' => \MoneyMaker\Facades\Translator::class,
        'ValidatorFactory' => \MoneyMaker\Facades\ValidatorFactory::class,
        'Storage' => \MoneyMaker\Facades\Storage::class,
        'View' => \MoneyMaker\Facades\View::class,
        'Mail' => \MoneyMaker\Facades\Mail::class,
        'Hash' => \MoneyMaker\Facades\Hash::class
    ],
];