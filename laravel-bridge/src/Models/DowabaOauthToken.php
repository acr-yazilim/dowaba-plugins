<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $local_user_id
 * @property string $dowaba_user_id
 * @property string|null $email
 * @property string $access_token
 * @property string|null $refresh_token
 * @property string|null $id_token
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array|null $scopes
 * @property array|null $claims
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DowabaOauthToken extends Model
{
    protected $table = 'dowaba_oauth_tokens';

    protected $fillable = [
        'local_user_id',
        'dowaba_user_id',
        'email',
        'access_token',
        'refresh_token',
        'id_token',
        'expires_at',
        'scopes',
        'claims',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'id_token' => 'encrypted',
            'expires_at' => 'datetime',
            'scopes' => 'array',
            'claims' => 'array',
        ];
    }

    public function isExpired(int $safetyMarginSeconds = 0): bool
    {
        return $this->expires_at === null || $this->expires_at->lte(now()->addSeconds($safetyMarginSeconds));
    }

    public function hasScope(string $scope): bool
    {
        return is_array($this->scopes) && in_array($scope, $this->scopes, true);
    }
}
