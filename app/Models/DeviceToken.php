<?php
// app/Models/DeviceToken.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DeviceToken extends Model
{
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_DRIVER = 'driver';
    public const TYPE_MERCHANT = 'merchants';

    protected $fillable = ['user_id', 'device_token','user_type'];

    public static function normalizeUserType(?string $userType): string
    {
        return match ($userType) {
            'merchant', 'merchants' => self::TYPE_MERCHANT,
            'driver' => self::TYPE_DRIVER,
            default => self::TYPE_CUSTOMER,
        };
    }

    public function scopeForUserType(Builder $query, ?string $userType): Builder
    {
        $normalizedType = self::normalizeUserType($userType);

        if ($normalizedType === self::TYPE_MERCHANT) {
            return $query->whereIn('user_type', ['merchant', self::TYPE_MERCHANT]);
        }

        return $query->where('user_type', $normalizedType);
    }

    public function user() {
        return $this->belongsTo(Merchant::class);
    }
}
