<?php


namespace App\Domain\Auth;


interface InstagramClientInterface
{
    public function getAuthorizationUrl(?string $source = null): string;
    public function getAuthorizationState(): string;
    public function getAuthorizationSource(string $state): ?string;
    public function isExpired(): bool;
    public function getAccessToken(): ?array;
    public function setAccessToken(array $token): void;
    public function resetAccessToken(): void;
    public function refreshAccessToken(): array;
    public function requestAccessToken(string $code): array;
    public function requestLLAccessToken(): array;
    public function getResourceOwnerId(): ?int;
    public function getResourceOwnerInfo(): array;
    public function getMediaCount(): int;
    public function getMediaList(InstagramMediaListQuery $query): InstagramMediaListResponse;
}
