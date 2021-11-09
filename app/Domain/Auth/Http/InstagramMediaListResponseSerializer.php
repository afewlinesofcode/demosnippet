<?php


namespace App\Domain\Auth\Http;


use App\Domain\Auth\InstagramMediaListResponse;
use App\Services\Serializer\Base;

class InstagramMediaListResponseSerializer extends Base
{
    /**
     * @param InstagramMediaListResponse $object
     * @param array $params
     * @return array
     */
    public function serialize($object, array $params = [])
    {
        return [
            'data' => $object->items,
            'count' => $object->mediaCount,
            'paging' => [
                'has_next' => $object->hasNext,
                'has_prev' => $object->hasPrev,
                'after' => $object->after,
                'before' => $object->before,
            ],
        ];
    }
}
