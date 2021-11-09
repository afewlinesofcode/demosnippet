<?php

namespace App\Domain\Auth\Providers;

use App\Domain\Auth\Http\InstagramMediaListResponseSerializer;
use App\Domain\Auth\InstagramMediaListResponse;
use App\Domain\Auth\OAuth2RepositoryInterface;
use App\Domain\Auth\OAuth2StateRepositoryInterface;
use App\Domain\Auth\Infrastructure\EloquentOAuth2Repository;
use App\Domain\Auth\Infrastructure\InstagramClient;
use App\Domain\Auth\Infrastructure\RedisOAuth2StateRepository;
use App\Domain\Auth\Infrastructure\SessionOAuth2StateRepository;
use App\Domain\Auth\InstagramClientInterface;
use App\Services\Serializer;
use Illuminate\Support\ServiceProvider;

class InstagramProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(OAuth2RepositoryInterface::class, EloquentOAuth2Repository::class);
        $this->app->bind(InstagramClientInterface::class, InstagramClient::class);
        $this->app->bind(OAuth2StateRepositoryInterface::class, RedisOAuth2StateRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Serializer::registerSerializer(InstagramMediaListResponse::class, InstagramMediaListResponseSerializer::class);
    }
}
