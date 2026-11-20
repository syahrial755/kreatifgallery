<?php

namespace Tests\Feature;

use App\Models\Karya;
use App\Models\Like;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * "Tidak ada route rusak, tombol mati, akses tidak sah, atau data tidak konsisten" (Part 12).
 * Data seeder dijalankan, lalu setiap peran menelusuri semua tautan & form yang benar-benar dirender.
 */
class SiteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @return array{links: string[], forms: array<int, array{action: string, method: string}>} */
    private function extract(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xp = new DOMXPath($dom);
        $links = $forms = [];

        foreach ($xp->query('//a[@href]') as $a) {
            $links[] = $a->getAttribute('href');
        }
        foreach ($xp->query('//form[@action]') as $f) {
            $method = strtoupper($f->getAttribute('method') ?: 'GET');
            $spoof = $xp->query('.//input[@name="_method"]', $f)->item(0);
            if ($spoof) {
                $method = strtoupper($spoof->getAttribute('value'));
            }
            $forms[] = ['action' => $f->getAttribute('action'), 'method' => $method];
        }

        return ['links' => $links, 'forms' => $forms];
    }

    private function internalPath(string $href): ?string
    {
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript|data):/i', $href)) {
            return null;
        }
        $p = parse_url($href);
        if (isset($p['host']) && $p['host'] !== parse_url(config('app.url'), PHP_URL_HOST)) {
            return null; // tautan eksternal
        }

        return ($p['path'] ?? '/').(isset($p['query']) ? '?'.$p['query'] : '');
    }

    private function crawl(?User $user, array $seeds): array
    {
        $queue = $seeds;
        $seen = [];
        $formsChecked = 0;
        $pending = [];

        while ($queue && count($seen) < 400) {
            $path = array_shift($queue);
            if (isset($seen[$path]) || str_starts_with($path, '/storage/')) {
                continue;
            }
            $seen[$path] = true;

            $res = $user ? $this->actingAs($user)->get($path) : $this->get($path);
            if (! in_array($res->getStatusCode(), [200, 302], true)) {
                $pending[] = "GET $path -> {$res->getStatusCode()}";
                continue;
            }
            if ($res->getStatusCode() !== 200) {
                continue;
            }

            $found = $this->extract($res->getContent());
            foreach ($found['links'] as $href) {
                if (($p = $this->internalPath($href)) !== null && ! isset($seen[$p])) {
                    $queue[] = $p;
                }
            }
            foreach ($found['forms'] as $f) {
                if (($p = $this->internalPath($f['action'])) === null) {
                    continue;
                }
                $formsChecked++;
                try {
                    $route = Route::getRoutes()->match(Request::create(parse_url($p, PHP_URL_PATH), $f['method']));
                    if (! in_array($f['method'], $route->methods(), true)) {
                        $pending[] = "FORM {$f['method']} $p di $path: metode tidak cocok";
                    }
                } catch (NotFoundHttpException|MethodNotAllowedHttpException|UrlGenerationException) {
                    $pending[] = "FORM {$f['method']} $p di $path: tidak ada route";
                }
            }
        }

        return ['seen' => count($seen), 'forms' => $formsChecked, 'problems' => $pending];
    }

    public function test_every_link_and_form_rendered_for_each_role_resolves(): void
    {
        $roles = [
            'tamu' => [null, ['/', '/jelajah', '/kategori', '/tentang', '/masuk', '/daftar']],
            'pengunjung' => [User::where('role', 'pengunjung')->first(), ['/', '/jelajah', '/notifikasi', '/karya/1']],
            'kreator' => [User::where('role', 'kreator')->first(), ['/', '/kreator/dashboard', '/kreator/karya', '/kreator/karya/unggah', '/kreator/portofolio', '/akun/profil', '/akun/pengaturan', '/notifikasi']],
            'admin' => [User::where('role', 'admin')->first(), ['/admin', '/admin/moderasi', '/admin/karya', '/admin/kategori', '/admin/pengguna', '/admin/laporan', '/admin/analitik', '/akun/profil', '/notifikasi']],
        ];

        $total = 0;
        foreach ($roles as $name => [$user, $seeds]) {
            $r = $this->crawl($user, $seeds);
            $this->assertSame([], $r['problems'], "Tautan/form rusak untuk peran $name");
            $this->assertGreaterThan(5, $r['seen'], "peran $name hanya menelusuri {$r['seen']} halaman");
            $total += $r['seen'];
            auth()->logout();
        }
        $this->assertGreaterThan(80, $total, 'penelusuran harus mencakup banyak halaman nyata, bukan hanya halaman awal');
    }

    public function test_every_named_get_route_without_parameters_responds_without_server_error(): void
    {
        $admin = User::where('role', 'admin')->first();
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{') || str_starts_with($route->uri(), 'up') || str_starts_with($route->uri(), 'storage')) {
                continue;
            }
            $status = $this->actingAs($admin)->get('/'.ltrim($route->uri(), '/'))->getStatusCode();
            $this->assertLessThan(500, $status, "GET /{$route->uri()} -> $status");
            $checked++;
        }
        $this->assertGreaterThan(20, $checked);
    }

    public function test_admin_only_and_creator_only_routes_reject_wrong_roles(): void
    {
        $guards = ['admin' => 'admin', 'kreator' => 'kreator'];
        $pengunjung = User::where('role', 'pengunjung')->first();
        $kreator = User::where('role', 'kreator')->first();

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();
            foreach ($guards as $prefix => $role) {
                if (! str_starts_with($uri, $prefix === 'admin' ? 'admin' : 'kreator')) {
                    continue;
                }
                $this->assertTrue(
                    (bool) array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'role:')),
                    "route $uri tidak dilindungi middleware role"
                );
            }
        }

        $this->actingAs($pengunjung)->get('/kreator/dashboard')->assertForbidden();
        $this->actingAs($pengunjung)->get('/admin')->assertForbidden();
        $this->actingAs($kreator)->get('/admin')->assertForbidden();
    }

    public function test_seeded_data_is_internally_consistent(): void
    {
        $this->assertGreaterThan(0, Karya::count());

        foreach (Karya::with('medias')->get() as $k) {
            $n = $k->medias->count();
            $this->assertTrue($n >= 1 && $n <= 10, "karya {$k->id} punya $n media");
            match ($k->status) {
                'disetujui' => $this->assertTrue($k->published_at !== null && $k->admin_id !== null, "karya {$k->id} disetujui tanpa published_at/admin_id"),
                'menunggu' => $this->assertNull($k->published_at, "karya {$k->id} menunggu tetapi sudah punya published_at"),
                'ditolak' => $this->assertNotEmpty($k->catatan_moderasi, "karya {$k->id} ditolak tanpa catatan"),
                default => $this->fail("status tidak dikenal: {$k->status}"),
            };
        }

        foreach (Media::all() as $m) {
            $this->assertTrue(Storage::disk('public')->exists($m->file_path), "berkas hilang: {$m->file_path}");
            if ($m->thumb_path) {
                $this->assertTrue(Storage::disk('public')->exists($m->thumb_path), "thumbnail hilang: {$m->thumb_path}");
            }
        }

        $dupes = Like::selectRaw('user_id, karya_id, count(*) c')->groupBy('user_id', 'karya_id')->having('c', '>', 1)->count();
        $this->assertSame(0, $dupes);
        $this->assertSame(0, User::whereNotIn('role', ['admin', 'kreator', 'pengunjung'])->count());
        $this->assertSame(0, Report::whereNull('karya_id')->whereNull('komentar_id')->whereNull('snapshot')->count());
    }
}
