<?php

use App\Providers\RouterServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Laras\AsyncQueue\ConsumerProcess;
use Laras\Auth\AuthServiceProvider;
use Laras\Crontab\CrontabProcess;
use Laras\Database\DatabaseServiceProvider;
use Laras\Facades\Auth;
use Laras\Facades\Config;
use Laras\Facades\DB;
use Laras\Facades\Hash;
use Laras\Facades\Log;
use Laras\Facades\Mail;
use Laras\Facades\Redis;
use Laras\Facades\Request;
use Laras\Facades\Response;
use Laras\Facades\Storage;
use Laras\Facades\Translator;
use Laras\Facades\ValidatorFactory;
use Laras\Facades\View;
use Laras\Redis\RedisServiceProvider;
use Laras\Support\Aspect\ControllerAspect;
use Laras\Support\Aspect\InjectAspect;

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

    'env' => env('APP_ENV', 'local'),

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

    'name' => 'Laras',
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
         * Laras framework Service Providers...
         */
        LogServiceProvider::class,
        DatabaseServiceProvider::class,
        RedisServiceProvider::class,
        AuthServiceProvider::class,
        RouterServiceProvider::class,
        FilesystemServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,
        MailServiceProvider::class,
        HashServiceProvider::class,
        EncryptionServiceProvider::class
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
        'Arr' => Arr::class,
        'Auth' => Auth::class,
        'Config' => Config::class,
        'DB' => DB::class,
        'Log' => Log::class,
        'Redis' => Redis::class,
        'Request' => Request::class,
        'Response' => Response::class,
        'Translator' => Translator::class,
        'ValidatorFactory' => ValidatorFactory::class,
        'Storage' => Storage::class,
        'View' => View::class,
        'Mail' => Mail::class,
        'Hash' => Hash::class
    ],

    'annotation' => [
        'scan' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        ],
        'classes' => [
            ControllerAspect::class,
            InjectAspect::class,
            CrontabProcess::class,
            ConsumerProcess::class,
        ],
    ]
];