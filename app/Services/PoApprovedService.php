<?php
// app/Services/PoApprovedService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PoApprovedService
{
    /**
     * ดึงรายการ PO ที่อนุมัติแล้วพร้อม Filter
     */
    public function getApprovedPOs($filters = [], $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // เตรียม WHERE conditions
            $whereConditions = "1 = 1";
            $params = [];
            
            if (!empty($filters['docno'])) {
                $whereConditions .= " AND pa.po_docno LIKE ?";
                $params[] = '%' . $filters['docno'] . '%';
            }
            
            if (!empty($filters['customer'])) {
                $whereConditions .= " AND pa.customer_name LIKE ?";
                $params[] = '%' . $filters['customer'] . '%';
            }
            
            if (!empty($filters['amount_from'])) {
                $whereConditions .= " AND pa.po_amount >= ?";
                $params[] = floatval($filters['amount_from']);
            }
            
            if (!empty($filters['amount_to'])) {
                $whereConditions .= " AND pa.po_amount <= ?";
                $params[] = floatval($filters['amount_to']);
            }
            
            if (!empty($filters['approval_level'])) {
                $whereConditions .= " AND max_approval_level = ?";
                $params[] = intval($filters['approval_level']);
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions .= " AND pa.approval_date >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions .= " AND pa.approval_date <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Main Query
            $query = "
                WITH ApprovedPOs AS (
                    SELECT 
                        pa.po_docno,
                        pa.po_amount,
                        MAX(pa.approval_level) as max_approval_level,
                        MAX(pa.customer_name) as customer_name,
                        MAX(pa.item_count) as item_count,
                        MAX(pa.approval_date) as last_approval_date,
                        MIN(pa.approval_date) as first_approval_date,
                        COUNT(*) as approval_count,
                        MAX(pa.approval_note) as last_note,
                        ROW_NUMBER() OVER (ORDER BY MAX(pa.approval_date) DESC, pa.po_docno DESC) as RowNum
                    FROM [Romar128].[dbo].[po_approvals] pa
                    WHERE pa.po_docno LIKE 'PP%' 
                        AND pa.approval_status = 'approved'
                        AND ({$whereConditions})
                    GROUP BY pa.po_docno, pa.po_amount
                )
                SELECT 
                    po_docno, po_amount, max_approval_level, 
                    customer_name, item_count, last_approval_date, 
                    first_approval_date, approval_count, last_note
                FROM ApprovedPOs 
                WHERE RowNum BETWEEN ? AND ?
                ORDER BY last_approval_date DESC
            ";
            
            // Count Query
            $countQuery = "
                SELECT COUNT(DISTINCT pa.po_docno) as total
                FROM [Romar128].[dbo].[po_approvals] pa
                WHERE pa.po_docno LIKE 'PP%' 
                    AND pa.approval_status = 'approved'
                    AND ({$whereConditions})
            ";
            
            // Execute Queries
            $countParams = $params;
            $totalResult = DB::connection('modern')->select($countQuery, $countParams);
            $totalRecords = $totalResult[0]->total ?? 0;
            $totalPages = ceil($totalRecords / $limit);
            
            $queryParams = array_merge($params, [$offset + 1, $offset + $limit]);
            $approvedPOs = DB::connection('modern')->select($query, $queryParams);
            
            // เพิ่มข้อมูลสถานะ
            foreach ($approvedPOs as $po) {
                $po = $this->enhancePOData($po);
            }
            
            return [
                'success' => true,
                'data' => $approvedPOs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalRecords,
                    'total_pages' => $totalPages,
                    'has_more' => $page < $totalPages,
                    'has_previous' => $page > 1,
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in getApprovedPOs: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'pagination' => null
            ];
        }
    }
    
    /**
     * เพิ่มข้อมูลสถานะและ UI properties
     */
    private function enhancePOData($po)
    {
        // กำหนดสถานะตาม approval level
        if ($po->max_approval_level >= 3) {
            $po->status_label = 'Fully Approved';
            $po->status_class = 'success';
            $po->status_icon = 'fas fa-check-circle';
        } elseif ($po->max_approval_level >= 2) {
            $po->status_label = 'Manager Approved';
            $po->status_class = 'warning';
            $po->status_icon = 'fas fa-clock';
        } else {
            $po->status_label = 'User Approved';
            $po->status_class = 'info';
            $po->status_icon = 'fas fa-user-check';
        }
        
        // คำนวณ Progress Percentage
        $po->progress_percentage = ($po->max_approval_level / 3) * 100;
        
        // Format dates
        if ($po->last_approval_date) {
            $po->formatted_last_approval = Carbon::parse($po->last_approval_date)->format('d/m/Y H:i');
            $po->last_approval_human = Carbon::parse($po->last_approval_date)->diffForHumans();
        }
        
        if ($po->first_approval_date) {
            $po->formatted_first_approval = Carbon::parse($po->first_approval_date)->format('d/m/Y H:i');
            $po->approval_duration_days = Carbon::parse($po->first_approval_date)
                ->diffInDays(Carbon::parse($po->last_approval_date));
        }
        
        // สร้าง approval level indicators
        $po->level_indicators = [];
        for ($level = 1; $level <= 3; $level++) {
            $po->level_indicators[] = [
                'level' => $level,
                'approved' => $level <= $po->max_approval_level,
                'icon' => $level <= $po->max_approval_level ? 'fas fa-check-circle text-success' : 'fas fa-circle text-muted',
                'title' => "Level {$level} " . ($level <= $po->max_approval_level ? 'Approved' : 'Pending')
            ];
        }
        
        return $po;
    }
    
    /**
     * ดึงสถิติของ PO Approved
     */
    public function getApprovedStats($filters = [])
    {
        try {
            // เตรียม WHERE conditions (ใช้โค้ดเดียวกับ getApprovedPOs)
            $whereConditions = "1 = 1";
            $params = [];
            
            if (!empty($filters['docno'])) {
                $whereConditions .= " AND pa.po_docno LIKE ?";
                $params[] = '%' . $filters['docno'] . '%';
            }
            
            if (!empty($filters['customer'])) {
                $whereConditions .= " AND pa.customer_name LIKE ?";
                $params[] = '%' . $filters['customer'] . '%';
            }
            
            // สถิติรวม
            $statsQuery = "
                WITH ApprovedPOs AS (
                    SELECT 
                        pa.po_docno,
                        pa.po_amount,
                        MAX(pa.approval_level) as max_approval_level,
                        MAX(pa.customer_name) as customer_name,
                        MAX(pa.item_count) as item_count
                    FROM [Romar128].[dbo].[po_approvals] pa
                    WHERE pa.po_docno LIKE 'PP%' 
                        AND pa.approval_status = 'approved'
                        AND ({$whereConditions})
                    GROUP BY pa.po_docno, pa.po_amount
                )
                SELECT 
                    COUNT(*) as total_pos,
                    SUM(po_amount) as total_amount,
                    AVG(po_amount) as avg_amount,
                    SUM(item_count) as total_items,
                    COUNT(DISTINCT customer_name) as unique_customers,
                    SUM(CASE WHEN max_approval_level >= 3 THEN 1 ELSE 0 END) as fully_approved,
                    SUM(CASE WHEN max_approval_level = 2 THEN 1 ELSE 0 END) as manager_approved,
                    SUM(CASE WHEN max_approval_level = 1 THEN 1 ELSE 0 END) as user_approved
                FROM ApprovedPOs
            ";
            
            $stats = DB::connection('modern')->select($statsQuery, $params)[0] ?? null;
            
            // สถิติรายเดือน (3 เดือนล่าสุด)
            $monthlyQuery = "
                SELECT 
                    YEAR(pa.approval_date) as year,
                    MONTH(pa.approval_date) as month,
                    COUNT(DISTINCT pa.po_docno) as pos_count,
                    SUM(pa.po_amount) as total_amount
                FROM [Romar128].[dbo].[po_approvals] pa
                WHERE pa.po_docno LIKE 'PP%' 
                    AND pa.approval_status = 'approved'
                    AND pa.approval_date >= DATEADD(MONTH, -3, GETDATE())
                    AND ({$whereConditions})
                GROUP BY YEAR(pa.approval_date), MONTH(pa.approval_date)
                ORDER BY year DESC, month DESC
            ";
            
            $monthlyStats = DB::connection('modern')->select($monthlyQuery, $params);
            
            // Top Customers
            $topCustomersQuery = "
                WITH ApprovedPOs AS (
                    SELECT 
                        pa.po_docno,
                        pa.po_amount,
                        MAX(pa.customer_name) as customer_name
                    FROM [Romar128].[dbo].[po_approvals] pa
                    WHERE pa.po_docno LIKE 'PP%' 
                        AND pa.approval_status = 'approved'
                        AND pa.customer_name IS NOT NULL
                        AND ({$whereConditions})
                    GROUP BY pa.po_docno, pa.po_amount
                )
                SELECT TOP 10
                    customer_name,
                    COUNT(*) as pos_count,
                    SUM(po_amount) as total_amount,
                    AVG(po_amount) as avg_amount
                FROM ApprovedPOs
                GROUP BY customer_name
                ORDER BY total_amount DESC
            ";
            
            $topCustomers = DB::connection('modern')->select($topCustomersQuery, $params);
            
            return [
                'success' => true,
                'general' => $stats,
                'monthly' => $monthlyStats,
                'top_customers' => $topCustomers,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in getApprovedStats: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update ข้อมูล customer_name และ item_count สำหรับ PO เก่าที่ยังไม่มีข้อมูล
     */
    public function updateMissingCustomerData($limit = 100)
    {
        try {
            // ดึง PO ที่ยังไม่มีข้อมูล customer
            $missingDataPOs = DB::connection('modern')
                ->table('po_approvals')
                ->whereNull('customer_name')
                ->orWhereNull('item_count')
                ->select('id', 'po_docno')
                ->limit($limit)
                ->get();
            
            $updatedCount = 0;
            $errorCount = 0;
            
            foreach ($missingDataPOs as $poRecord) {
                try {
                    // ดึงข้อมูลจาก Legacy Database
                    $customerData = $this->getCustomerAndItemCount($poRecord->po_docno);
                    
                    // Update record
                    DB::connection('modern')
                        ->table('po_approvals')
                        ->where('id', $poRecord->id)
                        ->update([
                            'customer_name' => $customerData['customer_name'],
                            'item_count' => $customerData['item_count'],
                            'updated_at' => now(),
                        ]);
                    
                    $updatedCount++;
                    
                } catch (\Exception $e) {
                    Log::error("Error updating PO {$poRecord->po_docno}: " . $e->getMessage());
                    $errorCount++;
                }
            }
            
            Log::info("Customer data update completed", [
                'processed' => count($missingDataPOs),
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);
            
            return [
                'success' => true,
                'processed' => count($missingDataPOs),
                'updated' => $updatedCount,
                'errors' => $errorCount,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in updateMissingCustomerData: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * ดึงข้อมูล Customer และ Item Count จาก Legacy Database
     */
    private function getCustomerAndItemCount($docNo)
    {
        try {
            $query = "
                SELECT 
                    s.SUPNAM as customer_name,
                    COUNT(d.PDTCD) as item_count
                FROM [Romar1].[dbo].[POC_POH] h
                JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
                JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
                WHERE h.DOCNO = ?
                GROUP BY s.SUPNAM
            ";
            
            $result = DB::connection('legacy')->select($query, [$docNo]);
            
            if (!empty($result)) {
                return [
                    'customer_name' => $result[0]->customer_name,
                    'item_count' => $result[0]->item_count
                ];
            }
            
            return [
                'customer_name' => null,
                'item_count' => 0
            ];
            
        } catch (\Exception $e) {
            Log::error("Error getting customer data for PO {$docNo}: " . $e->getMessage());
            return [
                'customer_name' => null,
                'item_count' => 0
            ];
        }
    }
    
    /**
     * Export ข้อมูล PO Approved เป็น Array สำหรับ Excel/CSV
     */
    public function exportApprovedPOsData($filters = [])
    {
        try {
            // ดึงข้อมูลทั้งหมดโดยไม่มี pagination
            $result = $this->getApprovedPOs($filters, 1, 9999);
            
            if (!$result['success']) {
                return $result;
            }
            
            $exportData = [];
            $exportData[] = [
                'PO Number',
                'Customer Name', 
                'Item Count',
                'PO Amount',
                'Approval Level',
                'Status',
                'Progress (%)',
                'First Approval',
                'Last Approval',
                'Duration (Days)',
                'Total Approvals',
                'Last Note'
            ];
            
            foreach ($result['data'] as $po) {
                $exportData[] = [
                    $po->po_docno,
                    $po->customer_name ?? 'N/A',
                    $po->item_count ?? 0,
                    number_format($po->po_amount, 2),
                    "Level {$po->max_approval_level}/3",
                    $po->status_label,
                    number_format($po->progress_percentage, 1) . '%',
                    $po->formatted_first_approval ?? 'N/A',
                    $po->formatted_last_approval ?? 'N/A',
                    $po->approval_duration_days ?? 0,
                    $po->approval_count,
                    $po->last_note ?? ''
                ];
            }
            
            return [
                'success' => true,
                'data' => $exportData,
                'filename' => 'approved_pos_' . date('Ymd_His') . '.csv'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in exportApprovedPOsData: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}