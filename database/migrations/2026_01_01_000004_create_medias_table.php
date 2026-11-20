<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karya_id')->constrained('karyas')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('thumb_path')->nullable(); // hanya gambar
            $table->string('tipe_media', 10); // gambar | video
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['karya_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
