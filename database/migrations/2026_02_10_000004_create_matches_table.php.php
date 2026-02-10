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
    Schema::create('matches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('venue_id')->nullable()->constrained('venues')->onDelete('cascade')->onUpdate('cascade');
        $table->foreignId('sport_id')->nullable()->constrained('sports')->onDelete('cascade')->onUpdate('cascade');
        // No SQL era 'creator_user_id', aqui mantemos o nome da coluna mas ligamos à tabela 'users'
        $table->foreignId('creator_user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
        
        $table->dateTime('start_time')->nullable();
        $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
