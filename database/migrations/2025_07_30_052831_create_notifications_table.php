<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ผู้ที่จะได้รับการแจ้งเตือน
            $table->string('type', 50); // approval_required, approval_completed
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // ข้อมูลเพิ่มเติม เช่น po_docno, amount
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('notifications');
    }
};