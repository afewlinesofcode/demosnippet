<?php


namespace App\Domain\Auth;


use App\User;

interface OAuth2StateRepositoryInterface
{
    public function get(User $user, string $provider): ?string;
    public function set(User $user, string $provider, string $state): void;
    public function del(User $user, string $provider): void;
}
