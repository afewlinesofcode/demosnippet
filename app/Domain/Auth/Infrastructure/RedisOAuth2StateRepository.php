<?php

namespace App\Domain\Auth\Infrastructure;

use App\Contracts\Adapters\RedisAdapterInterface;
use App\Domain\Auth\OAuth2StateRepositoryInterface;
use App\User;

class RedisOAuth2StateRepository implements OAuth2StateRepositoryInterface
{
    /**
     * @var RedisAdapterInterface
     */
    private $redisAdapter;

    public function __construct(RedisAdapterInterface $redisAdapter)
    {
        $this->redisAdapter = $redisAdapter;
    }

    public function get(User $user, string $provider): ?string
    {
        return $this->redisAdapter->get($this->key($user, $provider));
    }

    public function set(User $user, string $provider, string $state): void
    {
        $this->redisAdapter->set($this->key($user, $provider), $state);
    }

    public function del(User $user, string $provider): void
    {
        $this->redisAdapter->del($this->key($user, $provider));
    }

    private function key(User $user, string $provider): string
    {
        return sprintf('auth:oauth2:state_%s_%s', $provider, $user->id);
    }
}
