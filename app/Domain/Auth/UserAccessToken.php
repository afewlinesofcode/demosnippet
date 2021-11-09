<?php

namespace App\Domain\Auth;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Domain\Auth\UserAccessToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property array $access_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array|null $owner_info
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereOwnerInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAccessToken whereUserId($value)
 * @mixin \Eloquent
 */
class UserAccessToken extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'access_token',
        'owner_info',
        'expires_at',
    ];

    protected $casts = [
        'access_token' => 'array',
        'owner_info' => 'array',
        'expires_at' => 'datetime',
    ];

    public static function getByUserIds(\Illuminate\Support\Collection $userIds): Collection
    {
        /**
         * @var Collection $userAccessTokens
         */
        $userAccessTokens = static::whereIn('user_id', $userIds)->get();

        return $userAccessTokens;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
