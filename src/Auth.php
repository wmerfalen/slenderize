<?php

namespace slenderize;

class Auth
{
    protected $m_users = [];
    public function __construct(array $users)
    {
        $this->m_users = $users;
    }
    public function login($user, $password): bool
    {
        if (isset($this->m_users[$user]) && $this->m_users[$user] === $password) {
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }
    public static function isAdmin(): bool
    {
        return isset($_SESSION['admin']);
    }
}
