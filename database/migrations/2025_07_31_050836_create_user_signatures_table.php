<?php
// database/migrations/2025_01_31_000001_create_user_signatures_table.php

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
        Schema::connection('modern')->create('user_signatures', function (Blueprint $table) {
            $table->id(); // จะเป็น BIGINT IDENTITY หรือ INT IDENTITY ตาม Laravel version
            
            // ใช้ unsignedBigInteger เพราะ users.id เป็น BIGINT จาก $table->id()
            $table->unsignedBigInteger('user_id');
            
            $table->string('signature_name', 100);
            $table->string('signature_path', 255)->nullable();
            $table->text('signature_data')->nullable(); // base64 encoded
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('user_signatures');
    }
};