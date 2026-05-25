@props([])
<a href="{{ $loginUrl() }}" class="{{ $class }}" data-dowaba="login-button">
    {{ $slot->isEmpty() ? 'Dowaba ile Giriş' : $slot }}
</a>
