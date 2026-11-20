<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_avatar_is_local_svg_with_initials_and_escapes_name(): void
    {
        $u = User::create(['name' => 'Dewi Kreator', 'email' => 'd@t.id', 'password' => 'password123']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $u->foto_url);
        $svg = base64_decode(substr($u->foto_url, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('>DK<', $svg);

        $x = User::create(['name' => '<script>', 'email' => 'x@t.id', 'password' => 'password123']);
        $svg = base64_decode(substr($x->foto_url, strlen('data:image/svg+xml;base64,')));
        $this->assertStringNotContainsString('<script', $svg);
    }

    public function test_uploaded_photo_url_is_used_when_present(): void
    {
        $u = User::create(['name' => 'Dewi', 'email' => 'd@t.id', 'password' => 'password123', 'foto_profil' => 'profil/a.jpg']);
        $this->assertSame('/storage/profil/a.jpg', $u->foto_url);
    }
}
