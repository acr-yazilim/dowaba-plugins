<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Auth;

use Illuminate\Support\Carbon;

/**
 * Dowaba kullanıcı temsili — id_token claims'inden hidrate edilir.
 *
 * Bridge'in `Dowaba::user()` çağrısı bu DTO'yu döner. Senkronize edilmiş yerel
 * User modeli ile karıştırmamak için yalnızca id_token verisini taşır.
 */
class DowabaUser
{
    public function __construct(
        public readonly string $sub,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly array $scopes = [],
        public readonly ?Carbon $expiresAt = null,
        public readonly array $claims = [],
    ) {}

    public static function fromClaims(array $claims, array $scopes = [], ?Carbon $expiresAt = null): self
    {
        return new self(
            sub: (string) ($claims['sub'] ?? ''),
            email: $claims['email'] ?? null,
            name: $claims['name'] ?? null,
            scopes: $scopes,
            expiresAt: $expiresAt,
            claims: $claims,
        );
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function isExpired(int $safetyMarginSeconds = 0): bool
    {
        return $this->expiresAt === null
            || $this->expiresAt->lte(now()->addSeconds($safetyMarginSeconds));
    }

    public function toArray(): array
    {
        return [
            'sub' => $this->sub,
            'email' => $this->email,
            'name' => $this->name,
            'scopes' => $this->scopes,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'claims' => $this->claims,
        ];
    }
}
