<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('karya_id')->constrained('karyas')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'karya_id']); // satu suka per pengguna per karya
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('karya_id')->constrained('karyas')->cascadeOnDelete();
            $table->text('isi_komentar');
            $table->timestamps();

            $table->index(['karya_id', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();             // pelapor
            $table->foreignId('karya_id')->nullable()->constrained('karyas')->cascadeOnDelete();
            // nullOnDelete: laporan tetap ada (status penanganan tercatat) walau komentar yang dilaporkan dihapus.
            $table->foreignId('komentar_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('alasan');
            $table->text('snapshot')->nullable(); // salinan isi komentar saat dilaporkan (audit)
            $table->string('status', 20)->default('baru'); // baru | selesai
            $table->string('tindakan', 30)->nullable();     // abaikan | hapus_komentar | turunkan_karya
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Skema standar notifikasi database Laravel (sesuai ERD: uuid, type, notifiable, data, read_at)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('likes');
    }
};
