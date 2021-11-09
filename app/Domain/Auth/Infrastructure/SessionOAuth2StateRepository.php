<?php


namespace App\Domain\Auth\Infrastructure;


use App\Domain\Auth\OAuth2StateRepositoryInterface;
use App\User;

class SessionOAuth2StateRepository implements OAuth2StateRepositoryInterface
{
    public function get(User $user, string $provider): ?string
    {
        return $_SESSION[$this->key($provider)] ?? null;
    }

    public function set(User $user, string $provider, string $state): void
    {
        $_SESSION[$this->key($provider)] = $state;
    }

    public function del(User $user, string $provider): void
    {
        unset($_SESSION[$this->key($provider)]);
    }

    private function key(string $provider): string
    {
        return 'oauth2state_' . $provider;
    }
}
