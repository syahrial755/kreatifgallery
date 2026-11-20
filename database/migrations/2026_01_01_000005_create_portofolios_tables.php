<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portofolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        // Pivot N:M KARYA - PORTOFOLIO
        Schema::create('karya_portofolio', function (Blueprint $table) {
            $table->foreignId('portofolio_id')->constrained('portofolios')->cascadeOnDelete();
            $table->foreignId('karya_id')->constrained('karyas')->cascadeOnDelete();
            $table->primary(['portofolio_id', 'karya_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya_portofolio');
        Schema::dropIfExists('portofolios');
    }
};
