<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Karya;
use App\Models\Portofolio;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreatorAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $kreator;
    private Kategori $kat;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->kreator = User::create(['name' => 'Dewi', 'email' => 'd@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->kat = Kategori::create(['nama_kategori' => 'Ilustrasi', 'slug' => 'ilustrasi']);
    }

    private function karya(User $u, string $judul = 'K'): Karya
    {
        return Karya::create(['user_id' => $u->id, 'kategori_id' => $this->kat->id, 'judul' => $judul]);
    }

    // ---- Notifikasi ----

    public function test_notification_list_mark_read_and_redirect(): void
    {
        $this->kreator->notify(new AppNotification('approved', 'Karya disetujui', '/jelajah'));
        $this->kreator->notify(new AppNotification('rejected', 'Karya ditolak', '/tentang'));
        $this->assertSame(2, $this->kreator->unreadNotifications()->count());

        $this->actingAs($this->kreator)->get(route('notifikasi.index'))->assertOk()->assertSee('Karya disetujui')->assertSee('Karya ditolak');

        $first = $this->kreator->notifications()->where('data', 'like', '%disetujui%')->first();
        $this->actingAs($this->kreator)->post(route('notifikasi.baca', $first->id))->assertRedirect('/jelajah');
        $this->assertSame(1, $this->kreator->unreadNotifications()->count());

        $this->actingAs($this->kreator)->post(route('notifikasi.baca-semua'))->assertRedirect();
        $this->assertSame(0, $this->kreator->unreadNotifications()->count());
    }

    public function test_cannot_read_someone_elses_notification(): void
    {
        $other = User::create(['name' => 'O', 'email' => 'o@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $other->notify(new AppNotification('comment', 'Rahasia', '/'));
        $id = $other->notifications()->first()->id;

        $this->actingAs($this->kreator)->post(route('notifikasi.baca', $id))->assertNotFound();
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_unread_badge_is_shown_in_layouts(): void
    {
        $this->kreator->notify(new AppNotification('approved', 'Halo', '/'));
        $this->actingAs($this->kreator)->get(route('kreator.dashboard'))->assertOk()->assertSee('Notifikasi');
        $this->actingAs($this->kreator)->get('/')->assertOk()->assertSee('Tandai semua dibaca');
    }

    // ---- Portofolio ----

    public function test_creator_manages_portfolio_with_own_works_only(): void
    {
        $mine = $this->karya($this->kreator, 'Milik Saya');
        $other = User::create(['name' => 'O', 'email' => 'o@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $theirs = $this->karya($other, 'Milik Orang');

        $this->actingAs($this->kreator)->get(route('kreator.portofolio.create'))->assertOk()->assertSee('Milik Saya')->assertDontSee('Milik Orang');

        $this->actingAs($this->kreator)->post(route('kreator.portofolio.store'), [
            'judul' => 'Seri Senja', 'is_public' => 1, 'karya_ids' => [$theirs->id],
        ])->assertSessionHasErrors('karya_ids.0');
        $this->assertSame(0, Portofolio::count());

        $this->actingAs($this->kreator)->post(route('kreator.portofolio.store'), [
            'judul' => 'Seri Senja', 'is_public' => 1, 'karya_ids' => [$mine->id],
        ])->assertRedirect(route('kreator.portofolio.index'));

        $p = Portofolio::firstOrFail();
        $this->assertSame([$mine->id], $p->karyas()->pluck('karyas.id')->all());

        $this->actingAs($this->kreator)->put(route('kreator.portofolio.update', $p), ['judul' => 'Seri Fajar', 'karya_ids' => []])->assertRedirect();
        $p->refresh();
        $this->assertSame('Seri Fajar', $p->judul);
        $this->assertFalse($p->is_public);
        $this->assertCount(0, $p->karyas);

        $this->actingAs($other)->get(route('kreator.portofolio.edit', $p))->assertForbidden();
        $this->actingAs($other)->delete(route('kreator.portofolio.destroy', $p))->assertForbidden();

        $p->karyas()->attach($mine->id);
        $this->actingAs($this->kreator)->delete(route('kreator.portofolio.destroy', $p))->assertRedirect();
        $this->assertSame(0, Portofolio::count());
        $this->assertNotNull(Karya::find($mine->id), 'karya tidak ikut terhapus');
    }

    // ---- Profil & pengaturan ----

    public function test_profile_update_with_photo_replaces_old_file(): void
    {
        $this->actingAs($this->kreator)->put(route('akun.profil.update'), [
            'name' => 'Dewi Baru', 'bio' => 'Bio baru', 'foto' => UploadedFile::fake()->image('a.jpg', 200, 200),
        ])->assertSessionHasNoErrors();

        $u = $this->kreator->fresh();
        $this->assertSame('Dewi Baru', $u->name);
        $old = $u->foto_profil;
        Storage::disk('public')->assertExists($old);

        $this->actingAs($u)->put(route('akun.profil.update'), ['name' => 'Dewi Baru', 'foto' => UploadedFile::fake()->image('b.png', 200, 200)]);
        $new = $u->fresh()->foto_profil;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);

        $this->actingAs($u->fresh())->put(route('akun.profil.update'), ['name' => 'Dewi Baru', 'hapus_foto' => 1]);
        $this->assertNull($u->fresh()->foto_profil);
        Storage::disk('public')->assertMissing($new);
    }

    public function test_profile_rejects_bad_photo_and_empty_name(): void
    {
        $this->actingAs($this->kreator)->put(route('akun.profil.update'), ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($this->kreator)->put(route('akun.profil.update'), [
            'name' => 'X', 'foto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('foto');
        $this->actingAs($this->kreator)->put(route('akun.profil.update'), [
            'name' => 'X', 'foto' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertSessionHasErrors('foto');
    }

    public function test_change_password_requires_current_password(): void
    {
        $this->actingAs($this->kreator)->put(route('akun.password.update'), [
            'current_password' => 'salah', 'password' => 'barubaru123', 'password_confirmation' => 'barubaru123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($this->kreator)->put(route('akun.password.update'), [
            'current_password' => 'password123', 'password' => 'barubaru123', 'password_confirmation' => 'barubaru123',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/masuk', ['email' => 'd@t.id', 'password' => 'barubaru123'])->assertRedirect(route('kreator.dashboard'));
    }

    public function test_account_pages_are_not_for_visitors(): void
    {
        $v = User::create(['name' => 'V', 'email' => 'v@t.id', 'password' => 'password123', 'role' => 'pengunjung']);
        $this->actingAs($v)->get(route('akun.profil'))->assertForbidden();
        $this->actingAs($v)->get(route('notifikasi.index'))->assertOk(); // notifikasi berlaku untuk semua pengguna
    }
}
