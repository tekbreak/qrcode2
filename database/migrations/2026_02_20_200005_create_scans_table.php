<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->text('referrer')->nullable();
            $table->boolean('is_unique')->default(false);
            $table->timestamp('scanned_at');

            $table->index(['short_link_id', 'scanned_at']);
            $table->index('scanned_at');
        });

        Schema::create('scan_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('hour')->nullable();
            $table->unsignedInteger('total_scans')->default(0);
            $table->unsignedInteger('unique_scans')->default(0);
            $table->json('countries')->nullable();
            $table->json('devices')->nullable();
            $table->json('browsers')->nullable();
            $table->json('referrers')->nullable();
            $table->timestamps();

            $table->unique(['short_link_id', 'date', 'hour']);
            $table->index(['short_link_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_aggregates');
        Schema::dropIfExists('scans');
    }
};
