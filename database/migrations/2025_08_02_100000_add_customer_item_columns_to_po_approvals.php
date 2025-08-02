<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->string('customer_name', 200)->nullable()->after('po_amount');
            $table->integer('item_count')->default(0)->after('customer_name');
            $table->string('approval_method', 50)->default('single')->after('approval_note'); // 'single' หรือ 'bulk'
            $table->string('bulk_approval_batch_id', 100)->nullable()->after('approval_method');
            
            // เพิ่ม index สำหรับการค้นหา
            $table->index(['customer_name']);
            $table->index(['approval_method']);
            $table->index(['bulk_approval_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['approval_method']);
            $table->dropIndex(['bulk_approval_batch_id']);
            
            $table->dropColumn([
                'customer_name',
                'item_count',
                'approval_method',
                'bulk_approval_batch_id'
            ]);
        });
    }
};
