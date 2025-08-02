<?php
// database/migrations/2025_08_02_100000_add_customer_columns_to_po_approvals.php

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
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            // เพิ่มคอลัมน์ชื่อลูกค้า
            $table->string('customer_name', 200)->nullable()->after('po_amount');
            
            // เพิ่มคอลัมน์จำนวนรายการ
            $table->integer('item_count')->nullable()->after('customer_name');
            
            // เพิ่ม index สำหรับการค้นหา
            $table->index('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->dropIndex(['customer_name']);
            $table->dropColumn(['customer_name', 'item_count']);
        });
    }
};