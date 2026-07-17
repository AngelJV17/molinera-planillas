<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InertiaDocumentNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_navigation_with_inertia_header_returns_html_not_raw_json(): void
    {
        Permission::create([
            'name' => 'attendance.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('attendance.view');

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => 'stale-browser-header',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
            ])
            ->get(route('attendance.index'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('<html', false)
            ->assertDontSee('"component":"Attendance/Index"', false);
    }
}
