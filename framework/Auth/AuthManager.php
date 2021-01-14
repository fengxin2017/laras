<?php


namespace MoneyMaker\Auth;


use MoneyMaker\Contracts\Auth\Authenticatable;
use MoneyMaker\Facades\Redis;
use MoneyMaker\Facades\Request;

class AuthManager
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var Authenticatable $user
     */
    protected $user;

    /**
     * @var string $token
     */
    protected $token;

    /**
     * @var  $userProvider
     */
    protected $userProvider;

    /**
     * AuthManager constructor.
     */
    public function __construct()
    {
        $this->userProvider = new UserProvider();
    }

    /**
     * @return UserProvider
     */
    public function getUserProvider()
    {
        return $this->userProvider;
    }

    /**
     * @param string $token
     * @return bool|mixed
     */
    public function attemptWithToken(string $token)
    {
        if ($user = $this->userProvider->retrieveByToken($token)) {
            $this->setToken($token);
            $this->user = $user;
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function id(): ?int
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->getAuthIdentifier();
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return bool|mixed
     */
    public function logout(): bool
    {
        Redis::del($this->token);
        $this->user = null;
        return true;
    }

    /**
     * @return bool
     */
    public function checkToken(): bool
    {
        return $this->attemptWithToken(Request::header('token'));
    }

    /**
     * @param Authenticatable $user
     * @return bool|mixed
     */
    public function login(Authenticatable $user)
    {
        $this->user = $user;
        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function loginUsingId(int $id): bool
    {
        if ($user = $this->userProvider->retrieveById($id)) {
            $this->user = $user;
            return true;
        }
        return false;
    }


    public function __destruct()
    {
        //var_dump('我被销毁了');
    }
}