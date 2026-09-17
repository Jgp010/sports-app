<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_active_admin_can_login(): void
    {
        User::factory()->create([
            'username' => 'admin_test',
            'password' => 'secret-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post('/admin/login', ['username' => 'admin_test', 'password' => 'secret-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_inactive_admin_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'disabled_admin',
            'password' => 'secret-password',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->post('/admin/login', ['username' => 'disabled_admin', 'password' => 'secret-password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_can_create_backend_account_without_email(): void
    {
        $admin = User::factory()->create(['username' => 'owner', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/admin/accounts', [
            'username' => 'content_editor', 'name' => '內容編輯', 'role' => 'editor',
            'password' => 'secret-password', 'is_active' => '1',
        ])->assertRedirect('/admin/accounts');

        $this->assertDatabaseHas('users', [
            'username' => 'content_editor', 'email' => null, 'role' => 'editor', 'is_active' => true,
        ]);
    }
}
