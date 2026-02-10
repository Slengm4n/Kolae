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
        // Foreign Keys
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
        $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('cascade')->onUpdate('cascade');

        $table->string('name')->nullable();
        $table->decimal('average_price_per_hour', 10, 2)->nullable();
        $table->integer('court_capacity')->nullable();
        $table->boolean('has_leisure_area')->default(false);
        $table->integer('leisure_area_capacity')->nullable();
        
        $table->enum('floor_type', ['grama natural', 'grama sintética', 'cimento', 'madeira', 'outro'])->nullable();
        
        $table->boolean('has_lighting')->default(false);
        $table->boolean('is_covered')->default(false);
        $table->enum('status', ['available', 'unavailable', 'in_maintenance'])->default('available');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
