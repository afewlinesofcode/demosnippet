<?php


namespace App\Domain\Auth;


class InstagramMediaListQuery
{
    /**
     * @var string
     */
    public $after;

    /**
     * @var string
     */
    public $before;

    /**
     * @var int
     */
    public $limit;

    public static function createDefault(): InstagramMediaListQuery
    {
        $query = new static();
        $query->after = null;
        $query->before = null;
        $query->limit = 12;
        return $query;
    }

    public static function createFromAttributes(array $attributes): InstagramMediaListQuery
    {
        $query = static::createDefault();

        $query->after = $attributes['after'] ?? $query->after;
        $query->before = $attributes['before'] ?? $query->before;
        $query->limit = $attributes['limit'] ?? $query->limit;

        return $query;
    }
}
