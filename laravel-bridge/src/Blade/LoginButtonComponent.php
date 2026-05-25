<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Blade;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * <x-dowaba::login-button :intended="url()->current()">Dowaba ile Giriş</x-dowaba::login-button>
 */
class LoginButtonComponent extends Component
{
    public function __construct(
        public ?string $intended = null,
        public string $class = 'dowaba-login-button',
    ) {}

    public function loginUrl(): string
    {
        $params = $this->intended ? ['intended' => $this->intended] : [];

        return route('dowaba.auth.login', $params);
    }

    public function render(): View
    {
        return view('dowaba::components.login-button');
    }
}
