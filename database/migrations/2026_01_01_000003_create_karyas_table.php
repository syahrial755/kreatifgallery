<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();            // mengunggah
            $table->foreignId('kategori_id')->constrained('kategoris')->restrictOnDelete();  // mengelompokkan (cegah referensi yatim)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete(); // memoderasi
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->string('status', 20)->default('menunggu'); // menunggu | disetujui | ditolak
            $table->text('catatan_moderasi')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyas');
    }
};
