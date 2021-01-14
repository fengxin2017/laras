<?php

namespace MoneyMaker\Foundation\Bootstrap;

use Illuminate\Contracts\Container\BindingResolutionException;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Foundation\AliasLoader;

class RegisterFacades
{
    /**
     * @param Application $app
     * @throws BindingResolutionException
     */
    public function bootstrap(Application $app)
    {
        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
        ))->register();
    }
}
