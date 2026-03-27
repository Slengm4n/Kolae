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
    Schema::create('venues', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('address_id')->constrained('addresses');
        $table->string('name');
        $table->decimal('average_price_per_hour', 10, 2)->nullable();
        $table->integer('court_capacity')->nullable();
        $table->boolean('has_leisure_area')->default(false);
        $table->integer('leisure_area_capacity')->nullable();
        $table->enum('floor_type', ['grama natural', 'grama sintetica', 'cimento', 'madeira', 'outro'])->nullable();
        $table->boolean('has_lighting')->default(false);
        $table->boolean('is_covered')->default(false);
        $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('sports', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->text('description')->nullable();
        $table->string('icon_path')->nullable();
        $table->timestamps();
    });

    Schema::create('matches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('venue_id')->constrained('venues');
        $table->foreignId('sport_id')->constrained('sports');
        $table->foreignId('creator_user_id')->constrained('users');
        $table->enum('status', ['scheduled', 'in_progress', 'completed', 'canceled'])->default('scheduled');
        $table->dateTime('start_time');
        $table->dateTime('end_time');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_core_tables');
    }
};
