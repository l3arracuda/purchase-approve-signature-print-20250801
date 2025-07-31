<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('po_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('po_docno', 50); // อ้างอิงจาก Legacy System
            $table->unsignedBigInteger('approver_id');
            $table->integer('approval_level'); // 1=user, 2=manager, 3=gm
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('approval_date')->nullable();
            $table->text('approval_note')->nullable();
            $table->decimal('po_amount', 15, 2)->nullable(); // เก็บยอดเงิน PO สำหรับการแจ้งเตือน
            $table->timestamps();

            $table->foreign('approver_id')->references('id')->on('users');
            $table->index(['po_docno', 'approval_level']); // Index สำหรับค้นหา
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('po_approvals');
    }
};