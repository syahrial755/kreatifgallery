<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Karya;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KaryaUploadTest extends TestCase
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

    private function payload(array $over = [], ?array $media = null): array
    {
        return array_merge([
            'judul' => 'Senja di Pantai',
            'deskripsi' => 'Lukisan digital',
            'kategori_id' => $this->kat->id,
            'media' => $media ?? [UploadedFile::fake()->image('a.jpg', 1600, 900)],
        ], $over);
    }

    private function upload(array $over = [], ?array $media = null)
    {
        return $this->actingAs($this->kreator)->post(route('kreator.karya.store'), $this->payload($over, $media));
    }

    private function makeKarya(string $status = 'menunggu', int $files = 1, ?User $owner = null): Karya
    {
        $owner ??= $this->kreator;
        $k = Karya::create(['user_id' => $owner->id, 'kategori_id' => $this->kat->id, 'judul' => 'Karya Uji',
            'status' => $status, 'published_at' => $status === 'disetujui' ? now() : null]);
        for ($i = 0; $i < $files; $i++) {
            $path = Storage::disk('public')->putFileAs("karya/{$k->id}", UploadedFile::fake()->image("m$i.jpg"), "m$i.jpg");
            Storage::disk('public')->put("karya/{$k->id}/thumbs/t$i.webp", 'x');
            Media::create(['karya_id' => $k->id, 'file_path' => $path, 'thumb_path' => "karya/{$k->id}/thumbs/t$i.webp", 'tipe_media' => 'gambar', 'urutan' => $i]);
        }

        return $k;
    }

    private function filesOnDisk(): int
    {
        return count(Storage::disk('public')->allFiles('karya'));
    }

    // ---- Unggah ----

    public function test_creator_uploads_one_artwork_as_pending(): void
    {
        $this->upload()->assertRedirect(route('kreator.dashboard'));

        $k = Karya::firstOrFail();
        $this->assertSame('menunggu', $k->status);
        $this->assertSame($this->kreator->id, $k->user_id);
        $this->assertNull($k->admin_id);
        $this->assertCount(1, $k->medias);
        Storage::disk('public')->assertExists($k->medias[0]->file_path);

        // Belum publik. Diperiksa sebagai tamu: untuk kreator yang login, judul muncul di dropdown notifikasi navbar.
        auth()->logout();
        $this->get('/jelajah')->assertDontSee('Senja di Pantai');
        $this->get("/karya/{$k->id}")->assertNotFound();
    }

    public function test_thumbnail_is_webp_720_wide_for_images_and_absent_for_video(): void
    {
        $this->upload([], [
            UploadedFile::fake()->image('besar.png', 1600, 900),
            UploadedFile::fake()->image('kecil.jpg', 400, 300),
            UploadedFile::fake()->create('klip.mp4', 500, 'video/mp4'),
        ])->assertRedirect();

        [$besar, $kecil, $video] = Karya::first()->medias->all();

        $info = getimagesizefromstring(Storage::disk('public')->get($besar->thumb_path));
        $this->assertSame(720, $info[0]);
        $this->assertSame(405, $info[1]);
        $this->assertSame(IMAGETYPE_WEBP, $info[2]);

        $this->assertSame(400, getimagesizefromstring(Storage::disk('public')->get($kecil->thumb_path))[0]); // tidak diperbesar

        $this->assertSame('video', $video->tipe_media);
        $this->assertNull($video->thumb_path);
        $this->assertSame([0, 1, 2], Karya::first()->medias->pluck('urutan')->all());
    }

    public function test_creator_uploads_ten_files_but_not_eleven(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $ten = array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg", 100, 100), range(1, 10));
        $this->upload([], $ten)->assertRedirect();
        $this->assertSame(10, Media::count());

        $eleven = array_map(fn ($i) => UploadedFile::fake()->image("g$i.jpg", 100, 100), range(1, 11));
        $this->upload(['judul' => 'Lain'], $eleven)->assertSessionHasErrors('media');
        $this->assertSame(1, Karya::count());
    }

    public function test_invalid_input_is_rejected_server_side(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->upload([], [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')])->assertSessionHasErrors('media.0');
        $this->upload([], [UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload')])->assertSessionHasErrors('media.0');
        $this->upload([], [UploadedFile::fake()->image('besar.jpg')->size(20481)])->assertSessionHasErrors('media.0');   // > 20 MB
        $this->upload(['judul' => str_repeat('x', 151)])->assertSessionHasErrors('judul');
        $this->upload(['judul' => ''])->assertSessionHasErrors('judul');
        $this->upload(['kategori_id' => 9999])->assertSessionHasErrors('kategori_id');
        $this->actingAs($this->kreator)->post(route('kreator.karya.store'), ['judul' => 'T', 'kategori_id' => $this->kat->id])->assertSessionHasErrors('media');

        $this->assertSame(0, Karya::count());
        $this->assertSame(0, $this->filesOnDisk());
    }

    public function test_exactly_20mb_and_all_allowed_formats_are_accepted(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->upload([], [UploadedFile::fake()->image('pas.jpg')->size(20480)])->assertSessionHasNoErrors()->assertRedirect();

        $this->upload(['judul' => 'Format'], [
            UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png'), UploadedFile::fake()->image('c.webp'),
            UploadedFile::fake()->image('d.gif'), UploadedFile::fake()->create('e.mp4', 100, 'video/mp4'),
            UploadedFile::fake()->create('f.webm', 100, 'video/webm'),
        ])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_upload_is_rate_limited_to_six_per_minute(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->upload(['judul' => "Karya $i"])->assertRedirect(route('kreator.dashboard'));
        }
        $this->upload(['judul' => 'Ketujuh'])->assertStatus(429);
        $this->assertSame(6, Karya::count());
    }

    public function test_ajax_upload_returns_json_redirect_and_validation_errors(): void
    {
        $ok = $this->actingAs($this->kreator)->post(route('kreator.karya.store'), $this->payload(), ['Accept' => 'application/json']);
        $ok->assertOk()->assertJson(['redirect' => route('kreator.dashboard')]);

        $bad = $this->actingAs($this->kreator)->post(route('kreator.karya.store'), $this->payload(['judul' => '']), ['Accept' => 'application/json']);
        $bad->assertStatus(422)->assertJsonValidationErrors('judul');
    }

    public function test_only_active_creators_can_upload(): void
    {
        $this->post(route('kreator.karya.store'), $this->payload())->assertRedirect('/masuk');

        $visitor = User::create(['name' => 'V', 'email' => 'v@t.id', 'password' => 'password123', 'role' => 'pengunjung']);
        $this->actingAs($visitor)->post(route('kreator.karya.store'), $this->payload())->assertForbidden();

        $this->kreator->update(['is_active' => false]);
        $this->actingAs($this->kreator->fresh())->post(route('kreator.karya.store'), $this->payload())->assertForbidden();
        $this->assertSame(0, Karya::count());
    }

    // ---- Pencegahan file yatim ----

    public function test_failed_processing_rolls_back_db_and_removes_written_files(): void
    {
        $rusak = UploadedFile::fake()->create('rusak.jpg', 10, 'image/jpeg'); // lolos MIME, isi bukan gambar
        $this->upload([], [UploadedFile::fake()->image('bagus.jpg'), $rusak])
            ->assertSessionHasErrors('media');

        $this->assertSame(0, Karya::count());
        $this->assertSame(0, Media::count());
        $this->assertSame(0, $this->filesOnDisk(), 'file dari file pertama harus ikut dibersihkan');
    }

    // ---- Notifikasi ----

    public function test_creator_is_notified_on_submit(): void
    {
        $this->upload();
        $n = $this->kreator->notifications()->first();
        $this->assertNotNull($n);
        $this->assertSame('submitted', $n->data['kind']);
        $this->assertStringContainsString('Senja di Pantai', $n->data['message']);
    }

    // ---- Revisi ----

    public function test_rejected_artwork_revision_returns_to_pending_and_notifies(): void
    {
        $k = $this->makeKarya('ditolak');
        $k->update(['catatan_moderasi' => 'Terlalu gelap', 'admin_id' => User::create(['name' => 'A', 'email' => 'a@t.id', 'password' => 'password123', 'role' => 'admin'])->id]);

        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), [
            'judul' => 'Sudah Diperbaiki', 'kategori_id' => $this->kat->id,
            'media' => [UploadedFile::fake()->image('baru.jpg')], 'hapus_media' => [$k->medias[0]->id],
        ])->assertRedirect(route('kreator.karya.index'));

        $k->refresh();
        $this->assertSame('menunggu', $k->status);
        $this->assertSame('Sudah Diperbaiki', $k->judul);
        $this->assertSame('Terlalu gelap', $k->catatan_moderasi); // riwayat catatan dipertahankan
        $this->assertSame('revision', $this->kreator->notifications()->first()->data['kind']);
    }

    public function test_text_only_edit_of_published_work_keeps_it_public(): void
    {
        $k = $this->makeKarya('disetujui');
        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), ['judul' => 'Judul Baru', 'kategori_id' => $this->kat->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('disetujui', $k->fresh()->status);
        $this->assertSame(0, $this->kreator->notifications()->count());
        $this->get('/jelajah')->assertSee('Judul Baru');
    }

    public function test_changing_media_of_published_work_returns_it_to_moderation_and_hides_it(): void
    {
        $k = $this->makeKarya('disetujui');
        $this->get("/karya/{$k->id}")->assertOk();

        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), [
            'judul' => $k->judul, 'kategori_id' => $this->kat->id, 'media' => [UploadedFile::fake()->image('tambahan.jpg')],
        ]);

        $k->refresh();
        $this->assertSame('menunggu', $k->status);
        $this->assertNull($k->published_at);
        $this->assertCount(2, $k->medias);
        auth()->logout();
        $this->get("/karya/{$k->id}")->assertNotFound();
        $this->get('/jelajah')->assertDontSee('Karya Uji');
    }

    public function test_removed_media_files_are_deleted_from_disk(): void
    {
        $k = $this->makeKarya('menunggu', 2);
        [$a, $b] = $k->medias->all();

        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), [
            'judul' => $k->judul, 'kategori_id' => $this->kat->id, 'hapus_media' => [$a->id],
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing([$a->file_path, $a->thumb_path]);
        Storage::disk('public')->assertExists($b->file_path);
        $this->assertSame(1, $k->medias()->count());
    }

    public function test_revision_cannot_leave_zero_or_more_than_ten_media(): void
    {
        $k = $this->makeKarya('menunggu', 1);
        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), [
            'judul' => $k->judul, 'kategori_id' => $this->kat->id, 'hapus_media' => [$k->medias[0]->id],
        ])->assertSessionHasErrors('media');
        $this->assertSame(1, $k->medias()->count());

        $this->withoutMiddleware(ThrottleRequests::class);
        $ten = array_map(fn ($i) => UploadedFile::fake()->image("x$i.jpg", 50, 50), range(1, 10));
        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $k), [
            'judul' => $k->judul, 'kategori_id' => $this->kat->id, 'media' => $ten,
        ])->assertSessionHasErrors('media'); // 1 + 10 = 11
    }

    public function test_cannot_remove_media_belonging_to_another_artwork(): void
    {
        $mine = $this->makeKarya('menunggu', 1);
        $other = $this->makeKarya('menunggu', 1);

        $this->actingAs($this->kreator)->put(route('kreator.karya.update', $mine), [
            'judul' => 'X', 'kategori_id' => $this->kat->id, 'hapus_media' => [$other->medias[0]->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $other->medias()->count());
        $this->assertSame(1, $mine->medias()->count());
    }

    // ---- Otorisasi ----

    public function test_creators_cannot_touch_other_creators_works(): void
    {
        $other = User::create(['name' => 'Lain', 'email' => 'l@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $k = $this->makeKarya('menunggu', 1);

        $this->actingAs($other)->get(route('kreator.karya.edit', $k))->assertForbidden();
        $this->actingAs($other)->put(route('kreator.karya.update', $k), ['judul' => 'Dibajak', 'kategori_id' => $this->kat->id])->assertForbidden();
        $this->actingAs($other)->delete(route('kreator.karya.destroy', $k))->assertForbidden();

        $this->assertSame('Karya Uji', $k->fresh()->judul);
        $this->assertSame(1, $this->filesOnDisk() > 0 ? 1 : 0);
    }

    public function test_delete_own_work_removes_rows_and_files(): void
    {
        $k = $this->makeKarya('menunggu', 2);
        $this->assertGreaterThan(0, $this->filesOnDisk());

        $this->actingAs($this->kreator)->delete(route('kreator.karya.destroy', $k))->assertRedirect(route('kreator.karya.index'));

        $this->assertNull(Karya::find($k->id));
        $this->assertSame(0, Media::count());
        $this->assertSame(0, $this->filesOnDisk());
    }

    // ---- Dashboard / daftar ----

    public function test_dashboard_shows_real_statistics(): void
    {
        $this->makeKarya('disetujui');
        $this->makeKarya('disetujui');
        $this->makeKarya('menunggu');
        $this->makeKarya('ditolak');

        $r = $this->actingAs($this->kreator)->get(route('kreator.dashboard'))->assertOk();
        $stats = $r->viewData('stats');
        $this->assertSame(['total' => 4, 'disetujui' => 2, 'menunggu' => 1, 'ditolak' => 1, 'suka' => 0, 'komentar' => 0], $stats);
    }

    public function test_my_works_lists_only_own_works_and_filters_by_status(): void
    {
        $other = User::create(['name' => 'Lain', 'email' => 'l@t.id', 'password' => 'password123', 'role' => 'kreator']);
        $this->makeKarya('ditolak')->update(['judul' => 'Milik Saya Ditolak', 'catatan_moderasi' => 'Buram']);
        $this->makeKarya('menunggu')->update(['judul' => 'Milik Saya Menunggu']);
        $this->makeKarya('menunggu', 1, $other)->update(['judul' => 'Milik Orang Lain']);

        $this->actingAs($this->kreator)->get(route('kreator.karya.index'))
            ->assertSee('Milik Saya Ditolak')->assertSee('Milik Saya Menunggu')->assertDontSee('Milik Orang Lain')
            ->assertSee('Submit revision')->assertSee('Buram');
        $this->actingAs($this->kreator)->get(route('kreator.karya.index', ['status' => 'ditolak']))
            ->assertSee('Milik Saya Ditolak')->assertDontSee('Milik Saya Menunggu');
    }

    public function test_upload_and_edit_forms_render(): void
    {
        $k = $this->makeKarya('ditolak');
        $k->update(['catatan_moderasi' => 'Perbaiki warna']);

        $this->actingAs($this->kreator)->get(route('kreator.karya.create'))->assertOk()->assertSee('data-uploader', false)->assertSee('Unggah karya');
        $this->actingAs($this->kreator)->get(route('kreator.karya.edit', $k))->assertOk()->assertSee('Perbaiki warna')->assertSee('Kirim revisi');
    }
}
