<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $over = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role), 'email' => "$role@test.id", 'password' => 'password123', 'role' => $role,
        ], $over));
    }

    public function test_registration_as_kreator_and_pengunjung(): void
    {
        $this->post('/daftar', ['name' => 'Rina', 'email' => 'rina@test.id', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'kreator'])->assertRedirect(route('kreator.dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'rina@test.id', 'role' => 'kreator', 'is_active' => 1]);
        $this->assertNotEquals('password123', User::where('email', 'rina@test.id')->value('password')); // hashed

        auth()->logout();
        $this->post('/daftar', ['name' => 'Tono', 'email' => 'tono@test.id', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'pengunjung'])->assertRedirect(route('home'));
    }

    public function test_cannot_register_as_admin_or_with_duplicate_email(): void
    {
        $this->post('/daftar', ['name' => 'X', 'email' => 'x@test.id', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'x@test.id']);

        $this->makeUser('kreator');
        $this->post('/daftar', ['name' => 'Y', 'email' => 'kreator@test.id', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'kreator'])->assertSessionHasErrors('email');
    }

    public function test_login_redirects_by_role_and_rejects_bad_password(): void
    {
        $this->makeUser('admin');
        $this->makeUser('kreator');

        $this->post('/masuk', ['email' => 'admin@test.id', 'password' => 'password123'])->assertRedirect(route('admin.dashboard'));
        $this->post('/keluar');
        $this->post('/masuk', ['email' => 'kreator@test.id', 'password' => 'password123'])->assertRedirect(route('kreator.dashboard'));
        $this->post('/keluar');

        $this->post('/masuk', ['email' => 'kreator@test.id', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->makeUser('kreator', ['is_active' => false]);
        $this->post('/masuk', ['email' => 'kreator@test.id', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan oleh admin.']);
        $this->assertGuest();
    }

    public function test_deactivated_while_logged_in_gets_403(): void
    {
        $u = $this->makeUser('kreator');
        $this->actingAs($u)->get('/kreator/dashboard')->assertOk();
        $u->update(['is_active' => false]);
        $this->actingAs($u->fresh())->get('/kreator/dashboard')->assertForbidden();
    }

    public function test_role_based_access(): void
    {
        $this->get('/kreator/dashboard')->assertRedirect('/masuk');
        $this->get('/admin')->assertRedirect('/masuk');

        $this->actingAs($this->makeUser('pengunjung'))->get('/kreator/dashboard')->assertForbidden();
        $this->actingAs($this->makeUser('kreator'))->get('/admin')->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->get('/admin')->assertOk();
        $this->actingAs($this->makeUser('admin', ['email' => 'a2@test.id']))->get('/kreator/dashboard')->assertForbidden();
    }

    public function test_upload_route_is_rate_limited_to_6_per_minute(): void
    {
        $this->actingAs($this->makeUser('kreator'));
        // Permintaan kosong gagal validasi (302), tapi tetap dihitung oleh throttle:unggah.
        for ($i = 0; $i < 6; $i++) {
            $this->post('/kreator/karya')->assertStatus(302);
        }
        $this->post('/kreator/karya')->assertStatus(429);
    }
}
