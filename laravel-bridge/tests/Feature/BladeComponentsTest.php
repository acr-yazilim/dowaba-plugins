<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Test ortamında bir users tablosu + auth guard simüle et
    if (! Schema::hasTable('users')) {
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('name')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
        });
    }
});

it('login-button renders anchor pointing to Dowaba auth login route', function () {
    Route::get('/_test-login-btn', fn () => view()->file(__DIR__.'/../Fixtures/login-button-view.blade.php'));

    $response = $this->get('/_test-login-btn');

    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('data-dowaba="login-button"');
    expect($html)->toContain('href=');
    expect($html)->toContain('/dowaba/auth/login');
    expect($html)->toContain('Dowaba ile Giriş');
});

it('login-button respects custom slot content', function () {
    Route::get('/_test-login-btn-slot', fn () => Blade::render(
        '<x-dowaba::login-button>Hesabıma bağlan</x-dowaba::login-button>'
    ));

    $response = $this->get('/_test-login-btn-slot');

    expect($response->getContent())->toContain('Hesabıma bağlan');
});

it('widget-script renders script tag with data-destek-key and HMAC user token', function () {
    config()->set('dowaba.widget.site_id', 42);
    config()->set('dowaba.widget.secret', 'test-widget-secret-very-long-string');

    $user = (object) [
        'id' => 99,
        'email' => 'ali@example.com',
        'name' => 'Ali',
    ];

    $html = Blade::render(
        '<x-dowaba::widget-script :site-id="42" :user="$user" />',
        ['user' => $user]
    );

    expect($html)->toContain('data-destek-key="42"');
    expect($html)->toContain('data-user-token=');
    expect($html)->toContain('data-dowaba="widget-script"');
    expect($html)->toContain('https://dowaba.test/widget.js');
});

it('widget-script omits user token for guest user', function () {
    config()->set('dowaba.widget.site_id', 42);
    config()->set('dowaba.widget.secret', 'test-secret-long-enough');

    $html = Blade::render('<x-dowaba::widget-script :site-id="42" :user="null" />');

    expect($html)->toContain('data-destek-key="42"');
    expect($html)->not->toContain('data-user-token');
});

it('chat-window renders iframe with embed URL and sandbox attrs', function () {
    config()->set('dowaba.widget.site_id', 42);
    config()->set('dowaba.widget.secret', 'test-secret-long-enough');

    $html = Blade::render('<x-dowaba::chat-window :conversation-id="123" height="600" />');

    expect($html)->toContain('<iframe');
    expect($html)->toContain('https://dowaba.test/embed/conversation/123');
    expect($html)->toContain('data-conversation-id="123"');
    expect($html)->toContain('sandbox=');
    expect($html)->toContain('height: 600px');
});

it('conversation-list shows "connect first" prompt when no token', function () {
    $html = Blade::render('<x-dowaba::conversation-list :site-id="42" />');

    expect($html)->toContain('data-dowaba="conversation-list"');
    expect($html)->toContain('Dowaba');
    expect($html)->toContain('href=');
});

it('contact-create-form renders form with csrf and required inputs', function () {
    $html = Blade::render(
        '<x-dowaba::contact-create-form :site-id="42" action="/my/contacts/save" />'
    );

    expect($html)->toContain('action="/my/contacts/save"');
    expect($html)->toContain('method="POST"');
    expect($html)->toContain('data-dowaba="contact-create-form"');
    expect($html)->toContain('name="_token"');
    expect($html)->toContain('name="name"');
    expect($html)->toContain('name="phone"');
    expect($html)->toContain('name="email"');
});
