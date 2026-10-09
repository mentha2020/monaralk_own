<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->index(['created_at']);
        });

        Schema::table('vehicle_submissions', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('vehicle_submissions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
