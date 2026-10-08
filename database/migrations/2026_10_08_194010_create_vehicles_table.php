<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['admin', 'public'])->default('admin');

            $table->foreignId('make_id')->constrained();
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->foreignId('body_type_id')->nullable()->constrained();
            $table->foreignId('fuel_type_id')->nullable()->constrained();
            $table->foreignId('transmission_id')->nullable()->constrained();
            $table->foreignId('exterior_color_id')->nullable()->constrained('colors');
            $table->foreignId('interior_color_id')->nullable()->constrained('colors');

            $table->string('trim')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('mileage_km')->default(0);
            $table->decimal('price', 12, 2);
            $table->enum('condition', ['new', 'certified_pre_owned', 'used'])->default('used');
            $table->text('description')->nullable();

            $table->string('vin')->nullable();
            $table->string('registration_number')->nullable();

            $table->unsignedTinyInteger('owners_count')->nullable();
            $table->text('accident_history')->nullable();
            $table->text('warranty')->nullable();
            $table->date('last_service_date')->nullable();

            $table->decimal('finance_deposit', 12, 2)->nullable();
            $table->unsignedSmallInteger('finance_term_months')->nullable();
            $table->decimal('finance_apr', 5, 2)->nullable();

            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('phone_display')->nullable();

            $table->string('location')->nullable();

            $table->enum('status', ['draft', 'pending', 'published', 'sold', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views_count')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['make_id', 'model_id']);
            $table->index('year');
            $table->index('price');
            $table->index('mileage_km');
            $table->index('condition');
            $table->index('is_featured');
            $table->index('vin');
            $table->index('registration_number');
            $table->index('location');
            $table->index('source');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
