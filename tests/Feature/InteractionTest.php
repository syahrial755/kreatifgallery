<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $kreator;
    private User $visitor;
    private User $admin;
    private Karya $karya;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kreator = User::create(['name' => 'Dewi', 'email' => 'd@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->visitor = User::create(['name' => 'Budi', 'email' => 'b@t.id', 'password' => 'password123', 'role' => 'pengunjung']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'password123', 'role' => 'admin']);
        $kat = Kategori::create(['nama_kategori' => 'Foto', 'slug' => 'foto']);
        $this->karya = Karya::create(['user_id' => $this->kreator->id, 'kategori_id' => $kat->id, 'judul' => 'Senja Kota', 'status' => 'disetujui', 'published_at' => now()]);
        Media::create(['karya_id' => $this->karya->id, 'file_path' => 'k/a.jpg', 'tipe_media' => 'gambar', 'urutan' => 0]);
    }

    private function pending(): Karya
    {
        return Karya::create(['user_id' => $this->kreator->id, 'kategori_id' => $this->karya->kategori_id, 'judul' => 'Belum Tayang', 'status' => 'menunggu']);
    }

    // ---- Suka ----

    public function test_guest_cannot_like_comment_or_report(): void
    {
        $this->post("/karya/{$this->karya->id}/suka")->assertRedirect('/masuk');
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'Halo'])->assertRedirect('/masuk');
        $this->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Alasan cukup panjang'])->assertRedirect('/masuk');
        $this->assertSame([0, 0, 0], [Like::count(), Comment::count(), Report::count()]);
    }

    public function test_like_toggles_and_is_unique_per_user_and_work(): void
    {
        $this->actingAs($this->visitor)->post("/karya/{$this->karya->id}/suka")->assertRedirect();
        $this->assertSame(1, Like::count());

        $this->post("/karya/{$this->karya->id}/suka"); // klik kedua = batal suka
        $this->assertSame(0, Like::count());

        $this->post("/karya/{$this->karya->id}/suka");
        $this->actingAs($this->kreator)->post("/karya/{$this->karya->id}/suka");
        $this->assertSame(2, Like::count());
        $this->assertSame(2, $this->karya->likes()->count());
    }

    public function test_database_rejects_duplicate_like_rows(): void
    {
        Like::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id]);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Like::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id]);
    }

    public function test_like_returns_json_for_ajax(): void
    {
        $this->actingAs($this->visitor)->postJson("/karya/{$this->karya->id}/suka")->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->postJson("/karya/{$this->karya->id}/suka")->assertOk()->assertJson(['liked' => false, 'count' => 0]);
    }

    public function test_detail_page_reflects_like_state(): void
    {
        $this->actingAs($this->visitor)->get("/karya/{$this->karya->id}")->assertSee('aria-pressed="false"', false)->assertSee('Suka');
        Like::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id]);
        $this->get("/karya/{$this->karya->id}")->assertSee('aria-pressed="true"', false)->assertSee('Disukai');
        auth()->logout();
        $this->get("/karya/{$this->karya->id}")->assertSee('Masuk untuk suka')->assertSee('Masuk')->assertDontSee('Kirim komentar');
    }

    public function test_cannot_interact_with_unpublished_or_deactivated_owner_work(): void
    {
        $p = $this->pending();
        $this->actingAs($this->visitor);
        $this->post("/karya/{$p->id}/suka")->assertNotFound();
        $this->post("/karya/{$p->id}/komentar", ['isi_komentar' => 'Halo'])->assertNotFound();
        $this->post("/karya/{$p->id}/laporkan", ['alasan' => 'Alasan cukup panjang'])->assertNotFound();

        $this->kreator->update(['is_active' => false]);
        $this->post("/karya/{$this->karya->id}/suka")->assertNotFound();
        $this->assertSame([0, 0, 0], [Like::count(), Comment::count(), Report::count()]);
    }

    public function test_deactivated_user_cannot_interact(): void
    {
        $this->visitor->update(['is_active' => false]);
        $this->actingAs($this->visitor->fresh())->post("/karya/{$this->karya->id}/suka")->assertForbidden();
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'Halo'])->assertForbidden();
        $this->assertSame(0, Comment::count());
    }

    // ---- Komentar ----

    public function test_comment_is_saved_shown_and_notifies_owner(): void
    {
        $this->actingAs($this->visitor)->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => "  Bagus sekali!  "])
            ->assertRedirect()->assertSessionHas('success');

        $c = Comment::firstOrFail();
        $this->assertSame('Bagus sekali!', $c->isi_komentar);
        $this->assertSame([$this->visitor->id, $this->karya->id], [$c->user_id, $c->karya_id]);

        $n = $this->kreator->notifications()->first();
        $this->assertSame('comment', $n->data['kind']);
        $this->assertStringContainsString('Budi', $n->data['message']);
        $this->assertStringContainsString("#komentar-{$c->id}", $n->data['url']);

        auth()->logout();
        $this->get("/karya/{$this->karya->id}")->assertSee('Bagus sekali!')->assertSee('Komentar (1)');
    }

    public function test_comment_validation_and_html_is_escaped(): void
    {
        $this->actingAs($this->visitor);
        foreach ([null, '', '   ', str_repeat('x', 1001)] as $bad) {
            $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => $bad])->assertSessionHasErrors('isi_komentar');
        }
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => str_repeat('x', 1000)])->assertSessionHasNoErrors();
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => '<script>alert(1)</script>']);
        $this->get("/karya/{$this->karya->id}")->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $this->assertSame(2, Comment::count());
    }

    public function test_commenting_on_own_work_does_not_notify(): void
    {
        $this->actingAs($this->kreator)->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'Terima kasih semua'])->assertSessionHasNoErrors();
        $this->assertSame(1, Comment::count());
        $this->assertSame(0, $this->kreator->notifications()->count());
    }

    public function test_comment_is_rate_limited(): void
    {
        $this->actingAs($this->visitor);
        for ($i = 0; $i < 10; $i++) {
            $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => "k$i"])->assertRedirect();
        }
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'terlalu banyak'])->assertStatus(429);
        $this->assertSame(10, Comment::count());
    }

    public function test_only_author_or_admin_can_delete_comment(): void
    {
        $c = Comment::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Milik Budi']);
        $other = User::create(['name' => 'Cici', 'email' => 'c@t.id', 'password' => 'password123', 'role' => 'pengunjung']);

        $this->actingAs($other)->delete("/komentar/{$c->id}")->assertForbidden();
        $this->actingAs($this->kreator)->delete("/komentar/{$c->id}")->assertForbidden(); // pemilik karya pun tidak boleh
        $this->assertNotNull(Comment::find($c->id));

        $this->actingAs($this->visitor)->delete("/komentar/{$c->id}")->assertRedirect()->assertSessionHas('success');
        $this->assertNull(Comment::find($c->id));

        $c2 = Comment::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Akan dihapus admin']);
        $this->actingAs($this->admin)->delete("/komentar/{$c2->id}")->assertRedirect();
        $this->assertSame(0, Comment::count());
    }

    public function test_deleting_reported_comment_by_author_closes_its_reports(): void
    {
        $c = Comment::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Kasar']);
        $r = Report::create(['user_id' => $this->kreator->id, 'komentar_id' => $c->id, 'alasan' => 'Menghina saya', 'snapshot' => 'Kasar']);

        $this->actingAs($this->visitor)->delete("/komentar/{$c->id}");

        $r->refresh();
        $this->assertSame(['selesai', 'hapus_komentar', null], [$r->status, $r->tindakan, $r->komentar_id]);
        $this->assertSame('Kasar', $r->snapshot);
    }

    public function test_deleting_a_work_closes_open_comment_reports(): void
    {
        $c = Comment::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Kasar']);
        $r = Report::create(['user_id' => $this->admin->id, 'komentar_id' => $c->id, 'alasan' => 'Menghina saya', 'snapshot' => 'Kasar']);

        $this->actingAs($this->kreator)->delete(route('kreator.karya.destroy', $this->karya))->assertRedirect();
        $this->assertSame('selesai', $r->fresh()->status);
    }

    // ---- Laporan ----

    public function test_report_work_is_stored_as_new(): void
    {
        $this->actingAs($this->visitor)->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Karya ini hasil jiplakan.'])->assertSessionHas('success');

        $r = Report::firstOrFail();
        $this->assertSame([$this->visitor->id, $this->karya->id, null, 'baru', 'Karya ini hasil jiplakan.'], [$r->user_id, $r->karya_id, $r->komentar_id, $r->status, $r->alasan]);

        $this->actingAs($this->admin)->get('/admin/laporan')->assertSee('Senja Kota')->assertSee('Karya ini hasil jiplakan.');
    }

    public function test_report_validation_duplicates_and_self_report(): void
    {
        $this->actingAs($this->visitor);
        foreach ([null, '', 'pendek'] as $bad) {
            $this->post("/karya/{$this->karya->id}/laporkan", ['alasan' => $bad])->assertSessionHasErrors('alasan');
        }
        $this->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Alasan yang cukup'])->assertSessionHas('success');
        $this->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Alasan yang cukup'])->assertSessionHas('info');
        $this->assertSame(1, Report::count(), 'laporan terbuka ganda dicegah');

        $this->actingAs($this->kreator)->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Laporan karya sendiri'])->assertForbidden();
        $this->assertSame(1, Report::count());
    }

    public function test_can_report_again_after_previous_report_is_closed(): void
    {
        $this->actingAs($this->visitor)->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Laporan pertama ya']);
        Report::first()->update(['status' => 'selesai', 'tindakan' => 'abaikan']);
        $this->post("/karya/{$this->karya->id}/laporkan", ['alasan' => 'Laporan kedua ya'])->assertSessionHas('success');
        $this->assertSame(2, Report::count());
    }

    public function test_report_comment_saves_snapshot_and_survives_deletion(): void
    {
        $c = Comment::create(['user_id' => $this->kreator->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Isi komentar asli']);

        $this->actingAs($this->visitor)->post("/komentar/{$c->id}/laporkan", ['alasan' => 'Komentar ini mengandung SARA'])->assertSessionHas('success');
        $r = Report::firstOrFail();
        $this->assertSame([null, $c->id, 'Isi komentar asli'], [$r->karya_id, $r->komentar_id, $r->snapshot]);

        $c->update(['isi_komentar' => 'diedit']); // snapshot tidak berubah
        $this->actingAs($this->admin)->post("/admin/laporan/{$r->id}/tangani", ['tindakan' => 'hapus_komentar']);
        $this->assertSame('Isi komentar asli', $r->fresh()->snapshot);
    }

    public function test_cannot_report_own_comment(): void
    {
        $c = Comment::create(['user_id' => $this->visitor->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Punya saya']);
        $this->actingAs($this->visitor)->post("/komentar/{$c->id}/laporkan", ['alasan' => 'Laporan komentar sendiri'])->assertForbidden();
        $this->assertSame(0, Report::count());
    }

    public function test_report_forms_are_rendered_for_logged_in_non_owner_only(): void
    {
        Comment::create(['user_id' => $this->kreator->id, 'karya_id' => $this->karya->id, 'isi_komentar' => 'Halo']);
        $this->actingAs($this->visitor)->get("/karya/{$this->karya->id}")->assertSee('Laporkan karya')->assertSee('Kirim laporan')->assertSee('Kirim komentar');
        $this->actingAs($this->kreator)->get("/karya/{$this->karya->id}")->assertDontSee('Laporkan karya')->assertSee('Kirim komentar');
        auth()->logout();
        $this->get("/karya/{$this->karya->id}")->assertDontSee('Laporkan karya');
    }

    public function test_private_preview_of_pending_work_has_no_interaction_forms(): void
    {
        $p = $this->pending();
        $this->actingAs($this->kreator)->get("/karya/{$p->id}")->assertOk()->assertDontSee('Kirim komentar')->assertDontSee('Masuk untuk suka');
    }

    // ---- Notifikasi ----

    public function test_notification_lifecycle_read_and_isolation(): void
    {
        $this->actingAs($this->visitor)->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'Satu']);
        $this->post("/karya/{$this->karya->id}/komentar", ['isi_komentar' => 'Dua']);
        $this->assertSame(2, $this->kreator->unreadNotifications()->count());

        $this->actingAs($this->kreator)->get('/notifikasi')->assertOk()->assertSee('mengomentari');
        $id = $this->kreator->notifications()->first()->id;

        $this->actingAs($this->visitor)->post("/notifikasi/{$id}/baca")->assertNotFound(); // milik orang lain
        $this->assertSame(2, $this->kreator->unreadNotifications()->count());

        $this->actingAs($this->kreator)->post("/notifikasi/{$id}/baca");
        $this->assertSame(1, $this->kreator->unreadNotifications()->count());
        $this->post('/notifikasi/baca-semua');
        $this->assertSame(0, $this->kreator->unreadNotifications()->count());
        $this->assertSame(2, $this->kreator->notifications()->count(), 'riwayat tetap tersimpan');
    }
}
