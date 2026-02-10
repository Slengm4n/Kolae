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
        Schema::create('addresses', function (Blueprint $table) {
        $table->id();
        $table->string('cep', 9)->nullable();
        $table->string('street', 100)->nullable();
        $table->string('number', 10)->nullable();
        $table->string('neighborhood', 50)->nullable();
        $table->string('complement', 100)->nullable();
        $table->string('city', 50)->nullable();
        $table->string('state', 2)->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
