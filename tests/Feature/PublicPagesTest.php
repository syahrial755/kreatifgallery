<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Karya;
use App\Models\Like;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $kreator;
    private Kategori $kat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kreator = User::create(['name' => 'Dewi', 'email' => 'd@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->kat = Kategori::create(['nama_kategori' => 'Ilustrasi', 'slug' => 'ilustrasi']);
    }

    private function karya(string $judul, string $status = 'disetujui', array $over = []): Karya
    {
        $k = Karya::create(array_merge([
            'user_id' => $this->kreator->id, 'kategori_id' => $this->kat->id, 'judul' => $judul,
            'deskripsi' => "Deskripsi $judul", 'status' => $status, 'published_at' => now(),
        ], $over));
        Media::create(['karya_id' => $k->id, 'file_path' => "karya/$judul.png", 'thumb_path' => "karya/t-$judul.webp", 'tipe_media' => 'gambar', 'urutan' => 0]);

        return $k;
    }

    public function test_public_pages_render_without_login(): void
    {
        $k = $this->karya('Senja');
        $this->get('/')->assertOk()->assertSee('Setiap Karya Punya Cerita.')->assertSee('Explore Works')->assertSee('Start Creating');
        $this->get('/jelajah')->assertOk()->assertSee('Senja');
        $this->get('/kategori')->assertOk()->assertSee('Ilustrasi');
        $this->get('/tentang')->assertOk();
        $this->get("/karya/{$k->id}")->assertOk()->assertSee('Senja')->assertSee('Bagikan tautan');
        $this->get("/profil/{$this->kreator->id}")->assertOk()->assertSee('Dewi');
    }

    public function test_pending_and_rejected_works_are_not_public(): void
    {
        $p = $this->karya('Menunggu', 'menunggu');
        $r = $this->karya('Ditolak', 'ditolak');
        $this->karya('Tayang');

        $this->get('/jelajah')->assertSee('Tayang')->assertDontSee('Menunggu')->assertDontSee('Ditolak');
        $this->get('/')->assertDontSee('Menunggu');
        $this->get("/karya/{$p->id}")->assertNotFound();
        $this->get("/karya/{$r->id}")->assertNotFound();
        $this->get("/profil/{$this->kreator->id}")->assertDontSee('Menunggu');
    }

    public function test_owner_and_admin_can_preview_unpublished_but_others_cannot(): void
    {
        $p = $this->karya('Draf', 'menunggu');
        $this->actingAs($this->kreator)->get("/karya/{$p->id}")->assertOk()->assertSee('Pratinjau pribadi');

        $admin = User::create(['name' => 'A', 'email' => 'a@t.id', 'password' => 'password123', 'role' => 'admin']);
        $this->actingAs($admin)->get("/karya/{$p->id}")->assertOk();

        $other = User::create(['name' => 'O', 'email' => 'o@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->actingAs($other)->get("/karya/{$p->id}")->assertNotFound();
    }

    public function test_search_and_category_filter(): void
    {
        $kat2 = Kategori::create(['nama_kategori' => 'Fotografi', 'slug' => 'fotografi']);
        $this->karya('Gunung Biru');
        $this->karya('Kota Malam', 'disetujui', ['kategori_id' => $kat2->id]);

        $this->get('/jelajah?q=gunung')->assertSee('Gunung Biru')->assertDontSee('Kota Malam');
        $this->get('/jelajah?kategori=fotografi')->assertSee('Kota Malam')->assertDontSee('Gunung Biru');
        $this->get('/jelajah?q=tidakada')->assertSee('Tidak ada karya yang cocok');
        $this->get('/jelajah?q=%25')->assertOk()->assertSee('Tidak ada karya yang cocok'); // wildcard di-escape
    }

    public function test_popular_sorting_orders_by_likes(): void
    {
        $a = $this->karya('AAA Sepi');
        $b = $this->karya('BBB Ramai');
        foreach (['x', 'y'] as $n) {
            $u = User::create(['name' => $n, 'email' => "$n@t.id", 'password' => 'password123']);
            Like::create(['user_id' => $u->id, 'karya_id' => $b->id]);
        }

        $this->get('/jelajah?urut=populer')->assertSeeInOrder(['BBB Ramai', 'AAA Sepi']);
        $this->get('/jelajah?urut=ngawur')->assertSessionHasErrors('urut');
    }

    public function test_pagination_shows_12_per_page(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->karya(sprintf('Karya%02d', $i), 'disetujui', ['published_at' => now()->addMinutes($i)]);
        }
        $this->get('/jelajah')->assertSee('Karya14')->assertDontSee('Karya01');
        $this->get('/jelajah?page=2')->assertSee('Karya01')->assertDontSee('Karya14');
    }

    public function test_deactivated_creator_works_and_profile_hidden(): void
    {
        $k = $this->karya('Hilang');
        $this->kreator->update(['is_active' => false]);

        $this->get('/jelajah')->assertDontSee('Hilang');
        $this->get("/profil/{$this->kreator->id}")->assertNotFound();
        $this->get("/karya/{$k->id}")->assertNotFound();
    }

    public function test_profile_lists_only_public_portfolios_with_approved_works(): void
    {
        $tayang = $this->karya('Tayang');
        $draf = $this->karya('Draf', 'menunggu');
        $pub = $this->kreator->portofolios()->create(['judul' => 'Portofolio Publik', 'is_public' => true]);
        $pub->karyas()->attach([$tayang->id, $draf->id]);
        $priv = $this->kreator->portofolios()->create(['judul' => 'Portofolio Rahasia', 'is_public' => false]);
        $priv->karyas()->attach($tayang->id);
        $kosong = $this->kreator->portofolios()->create(['judul' => 'Portofolio Kosong', 'is_public' => true]);
        $kosong->karyas()->attach($draf->id);

        $this->get("/profil/{$this->kreator->id}")->assertOk()
            ->assertSee('Portofolio Publik')->assertSee('1 karya')
            ->assertDontSee('Portofolio Rahasia')->assertDontSee('Portofolio Kosong');
    }

    public function test_only_creator_profiles_are_public(): void
    {
        $visitor = User::create(['name' => 'V', 'email' => 'v@t.id', 'password' => 'password123', 'role' => 'pengunjung']);
        $admin = User::create(['name' => 'A', 'email' => 'a@t.id', 'password' => 'password123', 'role' => 'admin']);
        $this->get("/profil/{$visitor->id}")->assertNotFound();
        $this->get("/profil/{$admin->id}")->assertNotFound();
    }

    public function test_navbar_reflects_auth_state(): void
    {
        $this->get('/')->assertSee('Login')->assertSee('Register')->assertDontSee('Upload Work');
        $this->actingAs($this->kreator)->get('/')->assertSee('Upload Work')->assertSee('Logout')->assertDontSee('>Register<', false);
    }
}
