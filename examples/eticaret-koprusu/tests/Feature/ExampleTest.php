<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root URL `/acr` prefix'ine yönlendirir (Route::redirect('/', '/acr')).
     */
    public function test_root_redirects_to_acr(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/acr');
    }
}
