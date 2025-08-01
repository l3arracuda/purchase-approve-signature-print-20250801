<?php
// database/migrations/2025_01_31_000002_enhance_po_approvals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // เพิ่ม columns ใน po_approvals table
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->string('signature_path', 255)->nullable()->after('approval_note');
            $table->text('signature_data')->nullable()->after('signature_path');
            $table->string('approval_method', 20)->default('single')->after('signature_data');
            $table->string('bulk_approval_batch_id', 50)->nullable()->after('approval_method');
        });

        // เพิ่ม indexes
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->index('bulk_approval_batch_id');
            $table->index('approval_method');
        });

        // ลบ View เก่าก่อน (ถ้ามี)
        DB::connection('modern')->statement("DROP VIEW IF EXISTS v_po_approval_status");

        // สร้าง View สำหรับ PO Approval Status
        DB::connection('modern')->statement("
            CREATE VIEW v_po_approval_status AS
            SELECT 
                po_docno,
                -- แสดงสถานะแต่ละ Level
                MAX(CASE WHEN approval_level = 1 AND approval_status = 'approved' THEN 1 ELSE 0 END) as level1_approved,
                MAX(CASE WHEN approval_level = 2 AND approval_status = 'approved' THEN 1 ELSE 0 END) as level2_approved,
                MAX(CASE WHEN approval_level = 3 AND approval_status = 'approved' THEN 1 ELSE 0 END) as level3_approved,
                
                -- สถานะรวม
                CASE 
                    WHEN MAX(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) = 1 THEN 'Rejected'
                    WHEN MAX(CASE WHEN approval_level = 3 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 'Fully Approved'
                    WHEN MAX(CASE WHEN approval_level = 2 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 'Manager Approved'
                    WHEN MAX(CASE WHEN approval_level = 1 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 'User Approved'
                    ELSE 'Pending'
                END as overall_status,
                
                -- ข้อมูลเพิ่มเติม
                COUNT(*) as approval_count,
                MAX(approval_date) as last_approval_date,
                MAX(approval_level) as highest_level_reached,
                
                -- Progress Percentage (0-100)
                CASE 
                    WHEN MAX(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) = 1 THEN 0
                    WHEN MAX(CASE WHEN approval_level = 3 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 100
                    WHEN MAX(CASE WHEN approval_level = 2 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 67
                    WHEN MAX(CASE WHEN approval_level = 1 AND approval_status = 'approved' THEN 1 ELSE 0 END) = 1 THEN 33
                    ELSE 0
                END as progress_percentage
                
            FROM po_approvals 
            GROUP BY po_docno
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบ View
        DB::connection('modern')->statement("DROP VIEW IF EXISTS v_po_approval_status");

        // ลบ columns
        Schema::connection('modern')->table('po_approvals', function (Blueprint $table) {
            $table->dropIndex(['bulk_approval_batch_id']);
            $table->dropIndex(['approval_method']);
            $table->dropColumn([
                'signature_path',
                'signature_data', 
                'approval_method',
                'bulk_approval_batch_id'
            ]);
        });
    }
};