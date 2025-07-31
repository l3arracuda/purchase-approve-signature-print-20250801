<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PurchaseOrderService
{
    /**
     * ดึงรายการ PO จาก Legacy Database (method เดิม - ไม่ต้องแก้)
     */
    public function getPurchaseOrders($filters = [])
    {
        $query = $this->buildBaseQuery();
        
        // Apply filters
        if (!empty($filters['docno'])) {
            $query .= " AND h.DOCNO LIKE '%{$filters['docno']}%'";
        }
        
        if (!empty($filters['supplier'])) {
            $query .= " AND s.SUPNAM LIKE '%{$filters['supplier']}%'";
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND h.DOCDAT >= '{$filters['date_from']}'";
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND h.DOCDAT <= '{$filters['date_to']}'";
        }
        
        if (!empty($filters['amount_from'])) {
            $query .= " AND h.NETAMT >= {$filters['amount_from']}";
        }
        
        if (!empty($filters['amount_to'])) {
            $query .= " AND h.NETAMT <= {$filters['amount_to']}";
        }
        
        // Pagination
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        
        $query .= " ORDER BY h.DOCDAT DESC OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        
        return DB::connection('legacy')->select($query);
    }
    
    /**
     * ดึงข้อมูล PO โดย DocNo (ปรับปรุงใหม่ - ครบถ้วน)
     */
    public function getPurchaseOrderByDocNo($docNo)
    {
        try {
            // Query สำหรับ PO Header และ Supplier Info
            $headerQuery = "
                SELECT 
                    h.DOCDAT as DateNo, 
                    h.DOCNO as DocNo, 
                    h.RefPoNo as DocRef, 
                    h.SUPCD as SupNo,
                    s.SUPNAM as SupName, 
                    s.CRTERM as CreditTerm, 
                    s.ADDR1 as AddressSup, 
                    s.ADDR2 as Province, 
                    s.ADDR3 as ContractSup, 
                    s.TEL as Phone, 
                    s.FAX as FAX, 
                    s.ZIPCD as ZipCode, 
                    s.CONNAM as ContactName,
                    h.TLTAMT as TotalAmount, 
                    h.DISPCT as DiscountPrice, 
                    h.DISAMT as DiscountAmount, 
                    h.VATAMT as VatAmount, 
                    h.NETAMT as NetAmount,
                    h.REM as Remember, 
                    h.INTDES as Note,
                    h.APPSTS as AppStatus
                FROM [Romar1].[dbo].[POC_POH] h
                JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
                WHERE h.DOCNO = ? AND h.DOCNO LIKE 'PP%' AND h.APPSTS <> 'C'
            ";
            
            $headerResult = DB::connection('legacy')->select($headerQuery, [$docNo]);
            
            if (empty($headerResult)) {
                return null;
            }
            
            $header = $headerResult[0];
            
            // Query สำหรับ PO Details (รายการสินค้า)
            $detailQuery = "
                SELECT 
                    d.PDTCD as ProductNo, 
                    i.pdtnam as ProductName, 
                    d.QTY as QTY, 
                    d.UNIT as Unit, 
                    d.PRICE as Price,
                    (d.QTY * d.PRICE) as LineTotal
                FROM [Romar1].[dbo].[POC_POD] d
                JOIN [Romar1].[dbo].[INV_PDT] i ON d.PDTCD = i.PDTCD
                WHERE d.DOCNO = ? AND i.PDTTYP = '1'
                ORDER BY d.PDTCD
            ";
            
            $detailResult = DB::connection('legacy')->select($detailQuery, [$docNo]);
            
            // จัดรูปแบบข้อมูล
            $details = collect($detailResult)->map(function($item) {
                return (object)[
                    'ProductNo' => $item->ProductNo,
                    'ProductName' => $item->ProductName,
                    'QTY' => $item->QTY,
                    'Unit' => $item->Unit,
                    'Price' => $item->Price,
                    'LineTotal' => $item->LineTotal,
                ];
            });
            
            return (object)[
                'header' => $header,
                'details' => $details,
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error in getPurchaseOrderByDocNo: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ตรวจสอบว่า PO สามารถ Approve ได้หรือไม่ (ปรับปรุงใหม่)
     */
    public function canApprove($docNo, $userId)
    {
        try {
            // ตรวจสอบว่า DocNo ขึ้นต้นด้วย PP
            if (!str_starts_with($docNo, 'PP')) {
                return [
                    'can_approve' => false,
                    'reason' => 'Only PO with DocNo starting with "PP" can be approved'
                ];
            }
            
            // ตรวจสอบ User
            $user = DB::connection('modern')
                ->table('users')
                ->where('id', $userId)
                ->where('is_active', true)
                ->first();
                
            if (!$user) {
                return [
                    'can_approve' => false,
                    'reason' => 'User not found or inactive'
                ];
            }
            
            // ดึงประวัติการ Approve ของ PO นี้
            $currentApprovals = DB::connection('modern')
                ->table('po_approvals')
                ->where('po_docno', $docNo)
                ->orderBy('approval_level')
                ->get();
            
            // หาระดับถัดไปที่ต้อง Approve
            $nextLevel = $this->getNextApprovalLevel($currentApprovals);
            
            // ตรวจสอบว่า User มีสิทธิ์ Approve ระดับนี้ไหม
            if ($user->approval_level < $nextLevel) {
                return [
                    'can_approve' => false,
                    'reason' => "This PO requires approval level {$nextLevel}. Your level is {$user->approval_level}"
                ];
            }
            
            if ($user->approval_level > $nextLevel) {
                return [
                    'can_approve' => false,
                    'reason' => "This PO must be approved by level {$nextLevel} first"
                ];
            }
            
            // ตรวจสอบว่า User นี้เคย Approve PO นี้แล้วหรือไม่
            $existingApproval = $currentApprovals->where('approver_id', $user->id)->first();
            if ($existingApproval) {
                return [
                    'can_approve' => false,
                    'reason' => 'You have already processed this PO'
                ];
            }
            
            return [
                'can_approve' => true,
                'next_level' => $nextLevel,
                'user_id' => $user->id
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error in canApprove: ' . $e->getMessage());
            return [
                'can_approve' => false,
                'reason' => 'System error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * หาระดับการ Approve ถัดไป
     */
    private function getNextApprovalLevel($approvals)
    {
        $approvedLevels = $approvals->where('approval_status', 'approved')
            ->pluck('approval_level')
            ->toArray();
            
        // ลำดับการ Approve: 1=User, 2=Manager, 3=GM
        $requiredLevels = [1, 2, 3];
        
        foreach ($requiredLevels as $level) {
            if (!in_array($level, $approvedLevels)) {
                return $level;
            }
        }
        
        return 4; // ทุกขั้นตอนเสร็จแล้ว
    }
    
    /**
     * สร้าง Base Query สำหรับรายการ PO (method เดิม)
     */
    private function buildBaseQuery()
    {
        return "
            SELECT DISTINCT
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                s.CRTERM as CreditTerm,
                h.TLTAMT as TotalAmount, 
                h.DISPCT as DiscountPrice, 
                h.DISAMT as DiscountAmount, 
                h.VATAMT as VatAmount, 
                h.NETAMT as NetAmount,
                h.REM as Remember, 
                h.INTDES as Note,
                h.APPSTS as AppStatus
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            WHERE h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
        ";
    }
    
    /**
     * ดึงสถานะการ Approve ของ PO
     */
    public function getApprovalStatus($docNo)
    {
        return DB::connection('modern')
            ->table('po_approvals')
            ->join('users', 'po_approvals.approver_id', '=', 'users.id')
            ->where('po_docno', $docNo)
            ->select([
                'po_approvals.*',
                'users.full_name as approver_name',
                'users.role as approver_role'
            ])
            ->orderBy('approval_level')
            ->get();
    }

    /**
     * Bulk Approval สำหรับหลาย PO
     */
    public function bulkApprove($poDocNos, $userId, $action, $note = null)
    {
        DB::connection('modern')->beginTransaction();
        
        try {
            $user = DB::connection('modern')->table('users')->find($userId);
            $results = [];
            $batchId = 'BULK_' . date('YmdHis') . '_' . $userId;
            
            foreach ($poDocNos as $docNo) {
                $canApprove = $this->canApprove($docNo, $userId);
                
                if ($canApprove['can_approve']) {
                    // บันทึก Approval
                    DB::connection('modern')->table('po_approvals')->insert([
                        'po_docno' => $docNo,
                        'approver_id' => $userId,
                        'approval_level' => $canApprove['next_level'],
                        'approval_status' => $action,
                        'approval_date' => now(),
                        'approval_note' => $note,
                        'approval_method' => 'bulk',
                        'bulk_approval_batch_id' => $batchId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $results[$docNo] = 'success';
                } else {
                    $results[$docNo] = $canApprove['reason'];
                }
            }
            
            DB::connection('modern')->commit();
            return ['success' => true, 'results' => $results, 'batch_id' => $batchId];
            
        } catch (\Exception $e) {
            DB::connection('modern')->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ดึง PO Status สำหรับแสดงในหน้า List
     */
    public function getPOsWithApprovalStatus($filters = [])
    {
        // Original PO Query + JOIN กับ approval status
        $query = "
            WITH PaginatedResults AS (
                SELECT 
                    h.DOCNO as DocNo,
                    h.DOCDAT as DateNo,
                    s.SUPNAM as SupName,
                    h.NETAMT as NetAmount,
                    h.APPSTS as AppStatus,
                    h.INTDES as Note,
                    s.SUPCD as SupNo,
                    ROW_NUMBER() OVER (ORDER BY h.DOCDAT DESC, h.DOCNO DESC) as RowNum
                FROM [Romar1].[dbo].[POC_POH] h
                JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
                JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
                WHERE h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
                GROUP BY h.DOCNO, h.DOCDAT, s.SUPNAM, h.NETAMT, h.APPSTS, h.INTDES, s.SUPCD
            )
            SELECT * FROM PaginatedResults 
            WHERE RowNum BETWEEN 1 AND 20
        ";
        
        $pos = DB::connection('legacy')->select($query);
        
        // ดึง Approval Status จาก Modern DB
        $poDocNos = collect($pos)->pluck('DocNo')->toArray();
        $approvalStatuses = DB::connection('modern')
            ->table('v_po_approval_status')
            ->whereIn('po_docno', $poDocNos)
            ->get()
            ->keyBy('po_docno');
        
        // รวมข้อมูล
        return collect($pos)->map(function($po) use ($approvalStatuses) {
            $po->approval_status = $approvalStatuses[$po->DocNo]->overall_status ?? 'Pending';
            $po->approval_progress = [
                'level1' => $approvalStatuses[$po->DocNo]->level1_approved ?? 0,
                'level2' => $approvalStatuses[$po->DocNo]->level2_approved ?? 0,
                'level3' => $approvalStatuses[$po->DocNo]->level3_approved ?? 0,
            ];
            return $po;
        });
    }
}