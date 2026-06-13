<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_designs', function (Blueprint $table) {
            $table->boolean('logo_match_fg_color')->default(false)->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('qr_designs', function (Blueprint $table) {
            $table->dropColumn('logo_match_fg_color');
        });
    }
};
