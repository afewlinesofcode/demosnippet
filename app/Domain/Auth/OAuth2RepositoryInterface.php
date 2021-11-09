<?php


namespace App\Domain\Auth;


use App\User;

interface OAuth2RepositoryInterface
{
    public function getAccessToken(User $user, string $provider): ?array;
    public function saveAccessToken(User $user, string $provider, array $token): void;
    public function deleteAccessToken(User $user, string $provider): void;
    public function saveOwnerInfo(User $user, array $info): void;
    public function saveProviderUserId(User $user, string $provider, string $providerId): void;
    public function deleteProviderId(User $user, string $provider): void;
    public function getUserFromProviderId(string $provider, string $providerId): ?User;
}
