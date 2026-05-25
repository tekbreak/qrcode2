<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qr_designs', function (Blueprint $table) {
            $table->string('eye_frame_style', 20)->default('square')->after('eye_style');
            $table->string('eye_ball_style', 20)->default('square')->after('eye_frame_style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr_designs', function (Blueprint $table) {
            $table->dropColumn(['eye_frame_style', 'eye_ball_style']);
        });
    }
};
