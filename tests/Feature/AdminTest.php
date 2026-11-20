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

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kreator;
    private Kategori $kat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'password123', 'role' => 'admin']);
        $this->kreator = User::create(['name' => 'Dewi', 'email' => 'd@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->kat = Kategori::create(['nama_kategori' => 'Ilustrasi', 'slug' => 'ilustrasi']);
    }

    private function karya(string $judul = 'Karya Uji', string $status = 'menunggu', ?User $owner = null, bool $withMedia = true): Karya
    {
        $k = Karya::create(['user_id' => ($owner ?? $this->kreator)->id, 'kategori_id' => $this->kat->id, 'judul' => $judul,
            'status' => $status, 'published_at' => $status === 'disetujui' ? now() : null]);
        if ($withMedia) {
            Media::create(['karya_id' => $k->id, 'file_path' => "karya/{$k->id}/a.jpg", 'thumb_path' => null, 'tipe_media' => 'gambar', 'urutan' => 0]);
        }

        return $k;
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin);
    }

    private function visitor(string $email = 'v@t.id'): User
    {
        return User::create(['name' => 'Budi', 'email' => $email, 'password' => 'password123', 'role' => 'pengunjung']);
    }

    // ---- Akses ----

    public function test_admin_area_is_protected(): void
    {
        $k = $this->karya();
        $urls = ['/admin', '/admin/moderasi', "/admin/moderasi/{$k->id}", '/admin/karya', '/admin/kategori', '/admin/pengguna', '/admin/laporan', '/admin/analitik'];

        $pengunjung = $this->visitor();
        foreach ($urls as $u) {
            auth()->logout();
            $this->get($u)->assertRedirect('/masuk');
            $this->actingAs($this->kreator)->get($u)->assertForbidden();
            $this->actingAs($pengunjung)->get($u)->assertForbidden();
            $this->actingAs($this->admin)->get($u)->assertOk();
        }

        $this->actingAs($this->kreator)->post("/admin/moderasi/{$k->id}/setujui")->assertForbidden();
        $this->actingAs($this->kreator)->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => 'halo halo'])->assertForbidden();
        $this->assertSame('menunggu', $k->fresh()->status);
    }

    public function test_deactivated_admin_gets_403(): void
    {
        $this->admin->update(['is_active' => false]);
        $this->actingAs($this->admin->fresh())->get('/admin')->assertForbidden();
    }

    // ---- Overview ----

    public function test_overview_shows_real_statistics_and_chart_data(): void
    {
        $this->karya('A', 'menunggu');
        $this->karya('B', 'disetujui');
        $this->karya('C', 'disetujui');
        $this->karya('D', 'ditolak');
        $this->visitor();
        $r = Report::create(['user_id' => $this->visitor('x@t.id')->id, 'karya_id' => Karya::first()->id, 'alasan' => 'Spam']);

        $res = $this->asAdmin()->get('/admin')->assertOk();
        $this->assertSame(
            ['users' => 4, 'creators' => 1, 'karya' => 4, 'menunggu' => 1, 'disetujui' => 2, 'ditolak' => 1, 'laporan' => 1, 'laporan_baru' => 1],
            $res->viewData('stats')
        );
        $chart = $res->viewData('chart');
        $this->assertCount(14, $chart['labels']);
        $this->assertSame(4, array_sum($chart['uploads']));
        $this->assertSame(2, array_sum($chart['published']));
        $res->assertSee('Antrian moderasi')->assertSee('Ringkasan laporan');
    }

    // ---- Moderasi ----

    public function test_queue_lists_only_pending_oldest_first(): void
    {
        $lama = $this->karya('Karya Lama');
        $lama->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->karya('Karya Baru');
        $this->karya('Sudah Tayang', 'disetujui');

        $this->asAdmin()->get('/admin/moderasi')->assertSeeInOrder(['Karya Lama', 'Karya Baru'])->assertDontSee('Sudah Tayang');
    }

    public function test_detail_shows_all_media_and_creator_info(): void
    {
        $k = $this->karya();
        Media::create(['karya_id' => $k->id, 'file_path' => 'karya/x/b.mp4', 'tipe_media' => 'video', 'urutan' => 1]);

        $this->asAdmin()->get("/admin/moderasi/{$k->id}")->assertOk()
            ->assertSee('Media 1 dari 2')->assertSee('Media 2 dari 2')->assertSee('<video', false)
            ->assertSee('Dewi')->assertSee('d@t.id')->assertSee('Setujui karya')->assertSee('Tolak karya');
    }

    public function test_approve_publishes_records_admin_and_notifies(): void
    {
        $k = $this->karya('Senja');
        $this->get('/jelajah')->assertDontSee('Senja');

        $this->asAdmin()->post("/admin/moderasi/{$k->id}/setujui")->assertRedirect('/admin/moderasi');

        $k->refresh();
        $this->assertSame('disetujui', $k->status);
        $this->assertSame($this->admin->id, $k->admin_id);
        $this->assertNotNull($k->published_at);
        $this->assertNull($k->catatan_moderasi);

        $n = $this->kreator->notifications()->first();
        $this->assertSame('approved', $n->data['kind']);
        $this->assertStringContainsString('Senja', $n->data['message']);

        auth()->logout();
        $this->get('/jelajah')->assertSee('Senja');
        $this->get("/karya/{$k->id}")->assertOk();
    }

    public function test_reject_requires_note_and_does_not_change_status_without_it(): void
    {
        $k = $this->karya();
        foreach ([null, '', '   ', 'abc'] as $bad) {
            $this->asAdmin()->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => $bad])->assertSessionHasErrors('catatan_moderasi');
        }
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => str_repeat('x', 1001)])->assertSessionHasErrors('catatan_moderasi');

        $this->assertSame('menunggu', $k->fresh()->status);
        $this->assertSame(0, $this->kreator->notifications()->count());
    }

    public function test_reject_saves_note_admin_and_notifies_creator(): void
    {
        $k = $this->karya('Buram');
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => 'Resolusi terlalu rendah.'])->assertRedirect('/admin/moderasi');

        $k->refresh();
        $this->assertSame('ditolak', $k->status);
        $this->assertSame('Resolusi terlalu rendah.', $k->catatan_moderasi);
        $this->assertSame($this->admin->id, $k->admin_id);
        $this->assertNull($k->published_at);

        $n = $this->kreator->notifications()->first();
        $this->assertSame('rejected', $n->data['kind']);
        $this->assertStringContainsString('Resolusi terlalu rendah.', $n->data['message']);
        $this->actingAs($this->kreator)->get(route('kreator.karya.edit', $k))->assertSee('Resolusi terlalu rendah.');
    }

    public function test_cannot_moderate_a_work_twice(): void
    {
        $k = $this->karya();
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/setujui");
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/setujui")->assertSessionHas('error');
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => 'Terlambat menolak'])->assertSessionHas('error');

        $this->assertSame('disetujui', $k->fresh()->status);
        $this->assertSame(1, $this->kreator->notifications()->count(), 'hanya satu notifikasi untuk satu keputusan');
    }

    public function test_work_without_media_cannot_be_approved(): void
    {
        $k = $this->karya('Kosong', 'menunggu', null, false);
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/setujui")->assertSessionHas('error');
        $this->assertSame('menunggu', $k->fresh()->status);
    }

    public function test_full_cycle_reject_revise_resubmit_approve(): void
    {
        $k = $this->karya('Siklus');
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/tolak", ['catatan_moderasi' => 'Perbaiki pencahayaan.']);
        $this->assertSame('ditolak', $k->fresh()->status);

        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), ['judul' => 'Siklus v2', 'kategori_id' => $this->kat->id])->assertSessionHasNoErrors();
        $this->assertSame('menunggu', $k->fresh()->status);

        $this->asAdmin()->get('/admin/moderasi')->assertSee('Siklus v2');
        $this->asAdmin()->post("/admin/moderasi/{$k->id}/setujui");
        $this->assertSame('disetujui', $k->fresh()->status);
        $kinds = $this->kreator->notifications()->get()->pluck('data.kind')->sort()->values()->all();
        $this->assertSame(['approved', 'rejected', 'revision'], $kinds);
    }

    // ---- Semua karya ----

    public function test_all_works_filters_and_unpublish(): void
    {
        $this->karya('Gunung Biru', 'disetujui');
        $this->karya('Menunggu Saja', 'menunggu');
        $lain = User::create(['name' => 'Raka', 'email' => 'r@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->karya('Kota Malam', 'ditolak', $lain);

        $this->asAdmin()->get('/admin/karya')->assertSee('Gunung Biru')->assertSee('Menunggu Saja')->assertSee('Kota Malam');
        $this->asAdmin()->get('/admin/karya?status=disetujui')->assertSee('Gunung Biru')->assertDontSee('Kota Malam');
        $this->asAdmin()->get('/admin/karya?q=raka')->assertSee('Kota Malam')->assertDontSee('Gunung Biru');
        $this->asAdmin()->get('/admin/karya?q=gunung')->assertSee('Gunung Biru')->assertDontSee('Kota Malam');
        $this->asAdmin()->get('/admin/karya?status=ngawur')->assertSessionHasErrors('status');

        $k = Karya::where('judul', 'Gunung Biru')->first();
        $this->asAdmin()->post("/admin/karya/{$k->id}/turunkan", ['catatan_moderasi' => ''])->assertSessionHasErrors('catatan_moderasi');
        $this->assertSame('disetujui', $k->fresh()->status);

        $this->asAdmin()->post("/admin/karya/{$k->id}/turunkan", ['catatan_moderasi' => 'Melanggar hak cipta.'])->assertSessionHas('success');
        $k->refresh();
        $this->assertSame('ditolak', $k->status);
        $this->assertStringContainsString('Melanggar hak cipta.', $k->catatan_moderasi);
        auth()->logout();
        $this->get("/karya/{$k->id}")->assertNotFound();

        $m = Karya::where('judul', 'Menunggu Saja')->first();
        $this->asAdmin()->post("/admin/karya/{$m->id}/turunkan", ['catatan_moderasi' => 'Tidak berlaku'])->assertSessionHas('error');
    }

    // ---- Kategori ----

    public function test_category_crud_with_unique_names_and_stable_slug(): void
    {
        $this->asAdmin()->post('/admin/kategori', ['nama_kategori' => 'Seni Rupa', 'deskripsi' => 'Lukisan'])->assertRedirect('/admin/kategori');
        $kat = Kategori::where('nama_kategori', 'Seni Rupa')->firstOrFail();
        $this->assertSame('seni-rupa', $kat->slug);
        $this->assertSame($this->admin->id, $kat->admin_id);

        $this->asAdmin()->post('/admin/kategori', ['nama_kategori' => 'Seni Rupa'])->assertSessionHasErrors('nama_kategori');
        $this->asAdmin()->post('/admin/kategori', ['nama_kategori' => ''])->assertSessionHasErrors('nama_kategori');
        $this->asAdmin()->post('/admin/kategori', ['nama_kategori' => str_repeat('a', 81)])->assertSessionHasErrors('nama_kategori');

        $this->asAdmin()->post('/admin/kategori', ['nama_kategori' => 'Seni-Rupa!'])->assertSessionHasNoErrors(); // slug bentrok -> sufiks
        $this->assertSame('seni-rupa-2', Kategori::where('nama_kategori', 'Seni-Rupa!')->value('slug'));

        $this->asAdmin()->get("/admin/kategori/{$kat->id}/ubah")->assertOk()->assertSee('Seni Rupa');
        $this->asAdmin()->put("/admin/kategori/{$kat->id}", ['nama_kategori' => 'Seni Lukis', 'deskripsi' => 'Baru'])->assertRedirect('/admin/kategori');
        $kat->refresh();
        $this->assertSame('Seni Lukis', $kat->nama_kategori);
        $this->assertSame('seni-rupa', $kat->slug, 'slug tetap agar tautan lama valid');
        $this->asAdmin()->put("/admin/kategori/{$kat->id}", ['nama_kategori' => 'Ilustrasi'])->assertSessionHasErrors('nama_kategori'); // bentrok dengan kategori lain
        $this->asAdmin()->put("/admin/kategori/{$kat->id}", ['nama_kategori' => 'Seni Lukis'])->assertSessionHasNoErrors();   // nama sendiri boleh

        $this->asAdmin()->delete("/admin/kategori/{$kat->id}")->assertRedirect('/admin/kategori');
        $this->assertNull(Kategori::find($kat->id));
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $this->karya();
        $this->asAdmin()->delete("/admin/kategori/{$this->kat->id}")->assertSessionHas('error');
        $this->assertNotNull(Kategori::find($this->kat->id));
        $this->assertSame(1, Karya::count());
        $this->asAdmin()->get('/admin/kategori')->assertSee('Masih dipakai 1 karya', false);
    }

    // ---- Pengguna ----

    public function test_user_search_filter_and_detail(): void
    {
        $this->visitor('budi@t.id');
        User::create(['name' => 'Nonaktif', 'email' => 'off@t.id', 'password' => 'password123', 'role' => 'kreator', 'is_active' => false]);

        $this->asAdmin()->get('/admin/pengguna?q=budi')->assertSee('budi@t.id')->assertDontSee('d@t.id');
        $this->asAdmin()->get('/admin/pengguna?role=kreator')->assertSee('Dewi')->assertDontSee('budi@t.id');
        $this->asAdmin()->get('/admin/pengguna?status=nonaktif')->assertSee('off@t.id')->assertDontSee('Dewi');
        $this->asAdmin()->get('/admin/pengguna?q=%25')->assertOk()->assertSee('Tidak ada pengguna yang cocok');

        $this->karya('A', 'disetujui');
        $this->karya('B', 'ditolak');
        $res = $this->asAdmin()->get("/admin/pengguna/{$this->kreator->id}")->assertOk()->assertSee('d@t.id');
        $this->assertSame(['karya' => 2, 'disetujui' => 1, 'menunggu' => 0, 'ditolak' => 1, 'suka_diterima' => 0, 'komentar_ditulis' => 0, 'laporan_dikirim' => 0], $res->viewData('stats'));
    }

    public function test_deactivate_and_reactivate_account(): void
    {
        $k = $this->karya('Tayang', 'disetujui');

        $this->asAdmin()->patch("/admin/pengguna/{$this->kreator->id}/status")->assertSessionHas('success');
        $this->assertFalse($this->kreator->fresh()->is_active);

        auth()->logout();
        $this->post('/masuk', ['email' => 'd@t.id', 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->get('/jelajah')->assertDontSee('Tayang');
        $this->get("/karya/{$k->id}")->assertNotFound();

        $this->asAdmin()->patch("/admin/pengguna/{$this->kreator->id}/status");
        $this->assertTrue($this->kreator->fresh()->is_active);
        auth()->logout();
        $this->get('/jelajah')->assertSee('Tayang');
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->asAdmin()->patch("/admin/pengguna/{$this->admin->id}/status")->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    // ---- Laporan ----

    private function reportOnKarya(Karya $k, string $alasan = 'Konten tidak pantas', ?User $by = null): Report
    {
        return Report::create(['user_id' => ($by ?? $this->visitor('p'.Report::count().'@t.id'))->id, 'karya_id' => $k->id, 'alasan' => $alasan]);
    }

    private function reportOnComment(Comment $c, string $alasan = 'Kasar'): Report
    {
        return Report::create(['user_id' => $this->visitor('q'.Report::count().'@t.id')->id, 'komentar_id' => $c->id, 'alasan' => $alasan, 'snapshot' => $c->isi_komentar]);
    }

    public function test_reports_list_filter_and_detail(): void
    {
        $k = $this->karya('Dilaporkan', 'disetujui');
        $baru = $this->reportOnKarya($k, 'Plagiat');
        $selesai = $this->reportOnKarya($k, 'Spam lama');
        $selesai->update(['status' => 'selesai', 'tindakan' => 'abaikan', 'handled_by' => $this->admin->id, 'handled_at' => now()]);

        $this->asAdmin()->get('/admin/laporan')->assertSee('Plagiat')->assertSee('Spam lama');
        $this->asAdmin()->get('/admin/laporan?status=baru')->assertSee('Plagiat')->assertDontSee('Spam lama');
        $this->asAdmin()->get('/admin/laporan?status=selesai')->assertSee('Spam lama')->assertDontSee('Plagiat');
        $this->asAdmin()->get("/admin/laporan/{$baru->id}")->assertOk()->assertSee('Dilaporkan')->assertSee('Plagiat')->assertSee('Turunkan karya');
        $this->asAdmin()->get("/admin/laporan/{$selesai->id}")->assertOk()->assertSee('Sudah ditangani')->assertSee('Diabaikan');
    }

    public function test_dismiss_report_leaves_content_untouched(): void
    {
        $k = $this->karya('Aman', 'disetujui');
        $r = $this->reportOnKarya($k);

        $this->asAdmin()->post("/admin/laporan/{$r->id}/tangani", ['tindakan' => 'abaikan'])->assertRedirect('/admin/laporan');

        $r->refresh();
        $this->assertSame(['selesai', 'abaikan', $this->admin->id], [$r->status, $r->tindakan, $r->handled_by]);
        $this->assertNotNull($r->handled_at);
        $this->assertSame('disetujui', $k->fresh()->status);
    }

    public function test_deleting_reported_comment_keeps_report_as_resolved(): void
    {
        $k = $this->karya('Ada Komentar', 'disetujui');
        $c = Comment::create(['user_id' => $this->visitor()->id, 'karya_id' => $k->id, 'isi_komentar' => 'Komentar kasar sekali']);
        $r1 = $this->reportOnComment($c);
        $r2 = $this->reportOnComment($c, 'Pelapor kedua');

        $this->asAdmin()->post("/admin/laporan/{$r1->id}/tangani", ['tindakan' => 'hapus_komentar'])->assertRedirect('/admin/laporan');

        $this->assertNull(Comment::find($c->id));
        foreach ([$r1, $r2] as $r) {
            $r->refresh();
            $this->assertSame(['selesai', 'hapus_komentar', null], [$r->status, $r->tindakan, $r->komentar_id], 'laporan tetap ada & selesai');
            $this->assertSame('Komentar kasar sekali', $r->snapshot);
        }
        $this->asAdmin()->get("/admin/laporan/{$r1->id}")->assertOk()->assertSee('sudah dihapus')->assertSee('Komentar kasar sekali');
        $this->assertSame(0, $k->comments()->count());
    }

    public function test_unpublish_from_report_closes_all_open_reports_and_notifies(): void
    {
        $k = $this->karya('Bermasalah', 'disetujui');
        $r1 = $this->reportOnKarya($k, 'Plagiat');
        $r2 = $this->reportOnKarya($k, 'Pelanggaran');
        $lain = $this->reportOnKarya($this->karya('Lain', 'disetujui'), 'Tak terkait');

        $this->asAdmin()->post("/admin/laporan/{$r1->id}/tangani", ['tindakan' => 'turunkan_karya', 'catatan_moderasi' => ''])->assertSessionHasErrors('catatan_moderasi');
        $this->assertSame('disetujui', $k->fresh()->status);

        $this->asAdmin()->post("/admin/laporan/{$r1->id}/tangani", ['tindakan' => 'turunkan_karya', 'catatan_moderasi' => 'Terbukti plagiat.'])->assertRedirect('/admin/laporan');

        $this->assertSame('ditolak', $k->fresh()->status);
        $this->assertSame(['selesai', 'selesai', 'baru'], [$r1->fresh()->status, $r2->fresh()->status, $lain->fresh()->status]);
        $this->assertSame('turunkan_karya', $r2->fresh()->tindakan);
        $this->assertSame('rejected', $this->kreator->notifications()->first()->data['kind']);
        $this->assertStringContainsString('Terbukti plagiat.', $k->fresh()->catatan_moderasi);
    }

    public function test_report_cannot_be_handled_twice_or_with_wrong_action(): void
    {
        $k = $this->karya('Karya', 'disetujui');
        $c = Comment::create(['user_id' => $this->visitor()->id, 'karya_id' => $k->id, 'isi_komentar' => 'Halo']);
        $onKarya = $this->reportOnKarya($k);
        $onComment = $this->reportOnComment($c);

        $this->asAdmin()->post("/admin/laporan/{$onKarya->id}/tangani", ['tindakan' => 'hapus_komentar'])->assertSessionHas('error'); // bukan laporan komentar
        $this->asAdmin()->post("/admin/laporan/{$onComment->id}/tangani", ['tindakan' => 'turunkan_karya', 'catatan_moderasi' => 'Alasan cukup panjang'])->assertSessionHas('error'); // bukan laporan karya
        $this->asAdmin()->post("/admin/laporan/{$onKarya->id}/tangani", ['tindakan' => 'ngawur'])->assertSessionHasErrors('tindakan');
        $this->assertSame('baru', $onKarya->fresh()->status);
        $this->assertNotNull(Comment::find($c->id));

        $this->asAdmin()->post("/admin/laporan/{$onKarya->id}/tangani", ['tindakan' => 'abaikan']);
        $this->asAdmin()->post("/admin/laporan/{$onKarya->id}/tangani", ['tindakan' => 'turunkan_karya', 'catatan_moderasi' => 'Sudah selesai kok'])->assertSessionHas('error');
        $this->assertSame('disetujui', $k->fresh()->status);
    }

    public function test_deleting_a_reported_work_removes_its_reports_without_error(): void
    {
        $k = $this->karya('Akan Dihapus', 'disetujui');
        $this->reportOnKarya($k);
        $this->actingAs($this->kreator)->delete(route('kreator.karya.destroy', $k))->assertRedirect();
        $this->assertSame(0, Report::count());
    }

    // ---- Analitik ----

    public function test_analytics_uses_real_data(): void
    {
        $a = $this->karya('Populer', 'disetujui');
        $this->karya('Biasa', 'disetujui');
        $this->karya('Ditolak', 'ditolak');
        foreach (['u1', 'u2', 'u3'] as $n) {
            Like::create(['user_id' => $this->visitor("$n@t.id")->id, 'karya_id' => $a->id]);
        }
        Comment::create(['user_id' => $this->visitor('c@t.id')->id, 'karya_id' => $a->id, 'isi_komentar' => 'Bagus']);

        $res = $this->asAdmin()->get('/admin/analitik')->assertOk()->assertSee('Populer');
        $s = $res->viewData('summary');
        $this->assertEquals([3, 1, 1.5, 67], [$s['suka'], $s['komentar'], $s['rata_suka'], $s['approval_rate']]);
        $this->assertEquals([2, 0, 1], $res->viewData('charts')['status']['data']);
        $this->assertSame($this->kreator->id, $res->viewData('topCreators')->first()->user->id);
        $this->assertSame(3, $res->viewData('topCreators')->first()->suka);
        $this->assertSame('Populer', $res->viewData('topWorks')->first()->judul);
        $this->assertSame(30, count($res->viewData('charts')['pengguna']['data']));
    }

    public function test_sidebar_badges_show_pending_and_new_reports(): void
    {
        $this->karya('A');
        $this->karya('B');
        $this->reportOnKarya($this->karya('C', 'disetujui'));

        $html = $this->asAdmin()->get('/admin')->getContent();
        $this->assertMatchesRegularExpression('/Artwork Moderation<\/span>\s*<span[^>]*>2<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Reports<\/span>\s*<span[^>]*>1<\/span>/', $html);
    }
}
