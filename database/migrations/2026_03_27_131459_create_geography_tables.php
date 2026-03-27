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
    Schema::create('countries', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
    });

    Schema::create('states', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_country')->constrained('countries');
        $table->string('name')->unique();
    });

    Schema::create('cities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_state')->constrained('states');
        $table->string('name');
    });

    Schema::create('neighborhoods', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_city')->constrained('cities');
        $table->string('name');
    });

    Schema::create('streets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_neighborhood')->constrained('neighborhoods');
        $table->string('name');
    });

    Schema::create('addresses', function (Blueprint $table) {
        $table->id();
        $table->string('cep', 9)->nullable();
        $table->foreignId('id_street')->constrained('streets');
        $table->string('number', 50)->nullable();
        $table->string('complement')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->timestamps();
    });
}
};
