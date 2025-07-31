<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('full_name', 100);
            $table->string('email', 100)->nullable();
            $table->enum('role', ['admin', 'user', 'manager', 'gm'])->default('user');
            $table->integer('approval_level')->default(1); // 1=user, 2=manager, 3=gm, 99=admin
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('users');
    }
};