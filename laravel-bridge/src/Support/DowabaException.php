<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Support;

use RuntimeException;

class DowabaException extends RuntimeException
{
    protected ?string $errorCode = null;
    protected ?array $context = null;

    public function withCode(string $code): static
    {
        $this->errorCode = $code;

        return $this;
    }

    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function context(): ?array
    {
        return $this->context;
    }
}
