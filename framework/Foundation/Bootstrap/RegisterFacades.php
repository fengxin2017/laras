<?php

namespace Laras\Foundation\Bootstrap;

use Illuminate\Contracts\Container\BindingResolutionException;
use Laras\Contracts\Foundation\Application;
use Laras\Foundation\AliasLoader;

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
