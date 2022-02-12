<?php


namespace Laras\Auth;


use Laras\Contracts\Auth\UserProvider as UserProviderContract;
use Laras\Facades\Config;
use Laras\Facades\Crypt;
use Laras\Facades\Redis;
use Laras\Foundation\Application;
use Laras\Http\Request;
use Exception;

class UserProvider implements UserProviderContract
{
    /**
     * @param int $identifier
     * @return bool
     */
    public function retrieveById(int $identifier)
    {
        $model = Config::get('auth.model');

        if ($user = $model::find($identifier)) {
            return $user;
        }

        return false;
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function retrieveByToken(string $token)
    {
        if (Redis::exists($token) && ($decrypt = Crypt::decryptString($token))) {
            $decryptArray = explode('|', $decrypt, 3);
            if (isset($decryptArray[1])) {
                $userId = $decryptArray[1];
                $model = Config::get('auth.model');

                if ($user = $model::find($userId)) {
                    Redis::exists($token) && Redis::expire($token, Config::get('auth.token.renewal'));
                    /**@var Request $request */
                    $request = Application::getInstance()->coMake(Request::class);
                    $request->setUser($user);
                    Application::getInstance()->coBind(
                        Request::class,
                        function () use ($request) {
                            return $request;
                        }
                    );
                    return $user;
                }
            }
        }
        return false;
    }
}