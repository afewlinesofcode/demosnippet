<?php


namespace App\Domain\Auth\Infrastructure;


use App\Domain\Auth\Exceptions\InstagramException;
use App\Domain\Auth\InstagramMediaListQuery;
use App\Domain\Auth\InstagramMediaListResponse;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Token\AccessToken;

class InstagramClient implements \App\Domain\Auth\InstagramClientInterface
{
    /**
     * @var AccessToken|null
     */
    private ?AccessToken $accessToken = null;

    /**
     * @var Instagram
     */
    private Instagram $provider;

    public function __construct()
    {
        $this->provider = new Instagram(
            [
                'clientId' => config('services.instagram.client_id'),
                'clientSecret' => config('services.instagram.client_secret'),
                'redirectUri' => config('services.instagram.redirect'),
            ]
        );
    }

    public function getAuthorizationUrl(?string $source = null): string
    {
        $options = [
            'scope' => ['user_profile', 'user_media'],
        ];

        if ($source) {
            $options['state'] = bin2hex(random_bytes(16)) . 'authsource_' . $source;
        }
        return $this->provider->getAuthorizationUrl($options);
    }

    public function getAuthorizationState(): string
    {
        return $this->provider->getState();
    }

    public function getAuthorizationSource(string $state): ?string
    {
        $p = explode('authsource_', $state);
        return $p[1] ?? null;
    }

    public function isExpired(): bool
    {
        if (!$this->accessToken) {
            return true;
        }

        if (!$this->accessToken->getExpires()) {
            return false;
        }

        return $this->accessToken->hasExpired();
    }

    public function getAccessToken(): ?array
    {
        return $this->accessToken->jsonSerialize() ?? null;
    }

    public function setAccessToken(array $token): void
    {
        $this->accessToken = new AccessToken($token);
    }

    public function resetAccessToken(): void
    {
        $this->accessToken = null;
    }

    public function refreshAccessToken(): array
    {
        if (!$this->accessToken) {
            throw new InstagramException('Missing access token');
        }

        $refreshToken = $this->accessToken->getRefreshToken();

        if (!$refreshToken) {
            throw new InstagramException('Missing refresh token');
        }

        try {
            $this->accessToken = $this->provider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refreshToken,
                ]
            );

            return $this->accessToken->jsonSerialize();
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }

    public function requestAccessToken(string $code): array
    {
        try {
            $this->accessToken = $this->provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code,
                ]
            );

            return $this->accessToken->jsonSerialize();
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }

    public function requestLLAccessToken(): array
    {
        if (!$this->accessToken) {
            throw new InstagramException('Missing access token');
        }

        try {
            $query = http_build_query(
                [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => config('services.instagram.client_secret'),
                ]
            );

            $url = $this->provider->getGraphHost() . '/access_token?' . $query;
            $response = $this->provider->getParsedResponse(
                $this->provider->getAuthenticatedRequest(Instagram::METHOD_GET, $url, $this->accessToken)
            );

            $this->accessToken = new AccessToken($response);

            return $this->accessToken->jsonSerialize();
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }

    public function getResourceOwnerId(): ?int
    {
        return $this->accessToken->getResourceOwnerId() ?? null;
    }

    public function getResourceOwnerInfo(): array
    {
        try {
            $url = $this->provider->getGraphHost() . '/me?fields=id,username,account_type,media_count';

            return $this->provider->getParsedResponse(
                $this->provider->getAuthenticatedRequest(Instagram::METHOD_GET, $url, $this->accessToken)
            );
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }

    public function getMediaCount(): int
    {
        try {
            $url = $this->provider->getGraphHost() . '/me?fields=media_count';

            $response = $this->provider->getParsedResponse(
                $this->provider->getAuthenticatedRequest(Instagram::METHOD_GET, $url, $this->accessToken)
            );

            return $response['media_count'] ?? 0;
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }

    public function getMediaList(InstagramMediaListQuery $query): InstagramMediaListResponse
    {
        try {
            $url = $this->provider->getGraphHost() . '/me/media?fields=id,media_type,media_url&limit=' . $query->limit;

            if ($query->after) {
                $url .= sprintf('&after=%s', urlencode($query->after));
            } elseif ($query->before) {
                $url .= sprintf('&before=%s', urlencode($query->before));
            }

            $response = $this->provider->getParsedResponse(
                $this->provider->getAuthenticatedRequest(Instagram::METHOD_GET, $url, $this->accessToken)
            );

            $paging = $response['paging'] ?? [];

            $result = new InstagramMediaListResponse();
            $result->items = $response['data'] ?? [];
            $result->hasNext = isset($paging['next']);
            $result->hasPrev = isset($paging['previous']);
            $result->after = $paging['cursors']['after'] ?? null;
            $result->before = $paging['cursors']['before'] ?? null;
            $result->mediaCount = $this->getMediaCount();

            return $result;
        } catch (IdentityProviderException $exception) {
            throw new InstagramException($exception->getMessage());
        }
    }
}
