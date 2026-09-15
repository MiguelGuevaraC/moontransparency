<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginCorsTest extends TestCase
{
    public function test_transparency_dashboard_preflight_allows_the_public_website_origin(): void
    {
        $origin = 'https://www.moongroup.com.pe';

        $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'uuid,x-requested-with,content-type',
        ])->options('/api/dashboard')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Methods', 'GET')
            ->assertHeader('Access-Control-Allow-Headers', 'uuid,x-requested-with,content-type');
    }

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
