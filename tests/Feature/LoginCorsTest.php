<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginCorsTest extends TestCase
{
    public function test_login_preflight_allows_the_main_vercel_admin_origin(): void
    {
        $this->withHeaders([
            'Origin' => 'https://moongroup-admin.vercel.app',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])->options('/api/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://moongroup-admin.vercel.app');
    }

    public function test_login_preflight_allows_vercel_preview_origins(): void
    {
        $origin = 'https://moongroup-admin-git-main-cesar.vercel.app';

        $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])->options('/api/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin);
    }
}
