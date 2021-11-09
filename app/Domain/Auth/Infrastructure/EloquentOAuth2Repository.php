<?php


namespace App\Domain\Auth\Infrastructure;


use App\Domain\Auth\UserAccessToken;
use App\User;
use App\UserProviderUid;
use Carbon\Carbon;

class EloquentOAuth2Repository implements \App\Domain\Auth\OAuth2RepositoryInterface
{
    public function getAccessToken(User $user, string $provider): ?array
    {
        $uat = UserAccessToken::where(
            [
                ['user_id', $user->id],
                ['provider', $provider]
            ]
        )->first();

        return $uat ? $uat->access_token : null;
    }

    public function saveAccessToken(User $user, string $provider, array $token): void
    {
        $expiresAt = isset($token['expires'])
            ? Carbon::createFromTimestampUTC($token['expires'])
            : null;

        UserAccessToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider
            ],
            [
                'access_token' => $token,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * @param User $user
     * @param string $provider
     * @throws \Exception
     */
    public function deleteAccessToken(User $user, string $provider): void
    {
        UserAccessToken::where(
            [
                ['user_id', $user->id],
                ['provider', $provider],
            ]
        )->delete();
    }

    public function saveOwnerInfo(User $user, array $info): void
    {
        if ($uat = UserAccessToken::whereUserId($user->id)->first()) {
            $uat->owner_info = $info;
            $uat->save();
        }
    }

    public function saveProviderUserId(User $user, string $provider, string $providerId): void
    {
        $uid = UserProviderUid::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider
            ],
            [
                'uid' => $providerId,
            ]
        );
    }

    public function deleteProviderId(User $user, string $provider): void
    {
        UserProviderUid::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();
    }

    public function getUserFromProviderId(string $provider, string $providerId): ?User
    {
        $uid = UserProviderUid::where(
            [
                ['provider', $provider],
                ['uid', $providerId],
            ]
        )->first();

        return $uid->user ?? null;
    }
}
