<?php


namespace Laras\Auth;


use Exception;
use Laras\Contracts\Auth\Authenticatable;
use Laras\Facades\Crypt;
use Laras\Facades\Redis;
use Laras\Facades\Request;

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
     * @return bool
     * @throws Exception
     */
    public function attemptWithToken(?string $token = null)
    {
        if (is_null($token)) {
            return false;
        }
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
     * @param Authenticatable $user
     * @param int $expire
     * @return string
     */
    public function jwtloginUser(Authenticatable $user, int $expire)
    {
        $token = Crypt::encryptString('laras:auth|' . $user->getAuthIdentifier() . '|' . microtime(true) . ':' . mt_rand(10000000, 99999999));
        Redis::set($token, true, $expire);
        $this->setToken($token);
        $this->user = $user;
        return $token;
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
     * @throws Exception
     */
    public function jwtCheck(): bool
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

    /**
     * @return Authenticatable
     */
    public function user()
    {
        return $this->user;
    }

    public function __destruct()
    {
    }
}