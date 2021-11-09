<?php


namespace App\Domain\Auth;


use App\Domain\Auth\Exceptions\InstagramException;
use App\User;

class InstagramService
{
    /**
     * @var InstagramClientInterface
     */
    private InstagramClientInterface $instagramClient;

    /**
     * @var OAuth2RepositoryInterface
     */
    private OAuth2RepositoryInterface $authRepository;

    /**
     * @var OAuth2StateRepositoryInterface
     */
    private OAuth2StateRepositoryInterface $stateRepository;

    public function __construct(
        InstagramClientInterface $instagramClient,
        OAuth2RepositoryInterface $authRepository,
        OAuth2StateRepositoryInterface $stateRepository
    ) {
        $this->instagramClient = $instagramClient;
        $this->authRepository = $authRepository;
        $this->stateRepository = $stateRepository;
    }

    public function isAuthorized(User $user): bool
    {
        $accessToken = $this->authRepository->getAccessToken($user, OAuth2Provider::Instagram);

        if (!$accessToken) {
            return false;
        }

        $this->instagramClient->setAccessToken($accessToken);

        if ($this->instagramClient->isExpired()) {
            $this->refreshAccessToken($user);
        }

        return !$this->instagramClient->isExpired();
    }

    public function startAuthorization(User $user, ?string $source = null): string
    {
        $url = $this->instagramClient->getAuthorizationUrl($source);
        $state = $this->instagramClient->getAuthorizationState();

        $this->stateRepository->set($user, OAuth2Provider::Instagram, $state);

        return $url;
    }

    public function completeAuthorization(User $user, string $state, string $code): void
    {
        $savedState = $this->stateRepository->get($user, OAuth2Provider::Instagram);
        $this->stateRepository->del($user, OAuth2Provider::Instagram);

        if ($savedState !== $state) {
            throw new InstagramException('Invalid state');
        }

        $this->instagramClient->requestAccessToken($code);
        $this->instagramClient->requestLLAccessToken();

        $token = $this->instagramClient->getAccessToken();
        $ownerInfo = $this->instagramClient->getResourceOwnerInfo();

        $this->authRepository->saveAccessToken($user, OAuth2Provider::Instagram, $token);
        $this->authRepository->saveOwnerInfo($user, $ownerInfo);
        $this->authRepository->saveProviderUserId($user, OAuth2Provider::Instagram, $ownerInfo['id']);
    }

    public function getAuthorizationSource(string $state): ?string
    {
        return $this->instagramClient->getAuthorizationSource($state);
    }

    public function removeAuthorization(User $user): void
    {
        $this->stateRepository->del($user, OAuth2Provider::Instagram);
        $this->authRepository->deleteAccessToken($user, OAuth2Provider::Instagram);
    }

    public function clearAuthorization(User $user): void
    {
        $this->removeAuthorization($user);
        $this->authRepository->deleteProviderId($user, OAuth2Provider::Instagram);
    }

    public function authorizeFromShortLivedToken(User $user, string $token): void
    {
        $token = [
            'access_token' => $token,
        ];

        $this->instagramClient->setAccessToken($token);
        $this->instagramClient->requestLLAccessToken();
        $ownerInfo = $this->instagramClient->getResourceOwnerInfo();

        $token = $this->instagramClient->getAccessToken();
        $this->authRepository->saveAccessToken($user, OAuth2Provider::Instagram, $token);
        $this->authRepository->saveOwnerInfo($user, $ownerInfo);
        $this->authRepository->saveProviderUserId($user, OAuth2Provider::Instagram, $ownerInfo['id']);
    }

    public function getUserFromOwnerId(string $ownerId): ?User
    {
        return $this->authRepository->getUserFromProviderId(OAuth2Provider::Instagram, $ownerId);
    }

    public function getMediaList(User $user, InstagramMediaListQuery $query): InstagramMediaListResponse
    {
        $token = $this->authRepository->getAccessToken($user, OAuth2Provider::Instagram);

        if (!$token) {
            throw new InstagramException('Missing access token');
        } else {
            try {
                $this->instagramClient->setAccessToken($token);
                return $this->instagramClient->getMediaList($query);
            } catch (InstagramException $exception) {
                $this->authRepository->deleteAccessToken($user, OAuth2Provider::Instagram);
                abort(403, $exception->getMessage());
            }
        }
    }

    private function refreshAccessToken(User $user): void
    {
        try {
            $accessToken = $this->instagramClient->refreshAccessToken();
            $this->authRepository->saveAccessToken($user, OAuth2Provider::Instagram, $accessToken);
        } catch (InstagramException $exception) {
            $this->authRepository->deleteAccessToken($user, OAuth2Provider::Instagram);
            $this->instagramClient->resetAccessToken();
        }
    }
}
