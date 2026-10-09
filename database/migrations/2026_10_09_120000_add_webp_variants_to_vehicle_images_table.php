<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            $table->string('path_800w_webp')->nullable()->after('path_og');
            $table->string('path_1600w_webp')->nullable()->after('path_800w_webp');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            $table->dropColumn(['path_800w_webp', 'path_1600w_webp']);
        });
    }
};
