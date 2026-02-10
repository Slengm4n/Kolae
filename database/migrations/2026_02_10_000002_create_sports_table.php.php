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
    Schema::create('sports', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100)->nullable();
        $table->string('icon', 100)->nullable(); // Ícone antigo (classe css?)
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->text('description')->nullable();
        $table->string('icon_path')->nullable(); // Caminho do ficheiro novo
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
