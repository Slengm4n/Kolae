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
    Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->date('birthdate')->nullable();
    $table->string('cnpj')->unique();
    $table->string('password'); // Laravel usa 'password' por padrão
    $table->enum('role', ['admin', 'owner', 'player'])->default('player');
    $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
    $table->string('avatar_path')->nullable();
    $table->boolean('force_password_change')->default(false);
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
