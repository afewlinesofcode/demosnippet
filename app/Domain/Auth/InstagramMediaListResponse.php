<?php


namespace App\Domain\Auth;


class InstagramMediaListResponse
{
    public array $items;
    public bool $hasNext;
    public bool $hasPrev;
    public ?string $after;
    public ?string $before;
    public int $mediaCount;
}
