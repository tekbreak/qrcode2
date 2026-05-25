<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_dynamic')->default(false);
            $table->json('content_data');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('total_scans')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('qr_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->string('fg_color', 7)->default('#000000');
            $table->string('bg_color', 7)->default('#FFFFFF');
            $table->json('gradient')->nullable();
            $table->string('dot_style')->default('square');
            $table->string('eye_style')->default('square');
            $table->string('frame_style')->nullable();
            $table->string('frame_text')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('template_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_designs');
        Schema::dropIfExists('qr_codes');
    }
};
