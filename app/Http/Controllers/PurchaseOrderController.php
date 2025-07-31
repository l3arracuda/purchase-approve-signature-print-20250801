<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PurchaseOrderService;
use App\Services\NotificationService;
use App\Services\PrintService;


class PurchaseOrderController extends Controller
{
    protected $poService;
    protected $notificationService;
    protected $printService;
    
    public function __construct(PurchaseOrderService $poService, PrintService $printService = null)
    {
        $this->middleware('auth');
        $this->poService = $poService;
        $this->printService = $printService ?: app(PrintService::class);
    }

    /**
     * แสดงหน้าพิมพ์ Purchase Order (HTML)
     */
    public function printPO(Request $request, $docNo)
    {
        try {
            $user = Auth::user();
            
            // ตรวจสอบสิทธิ์ในการพิมพ์
            $canPrint = $this->printService->canPrint($docNo, $user->id);
            
            if (!$canPrint['can_print']) {
                return back()->withErrors(['error' => $canPrint['reason']]);
            }
            
            // เตรียมข้อมูลสำหรับการพิมพ์
            $result = $this->printService->preparePrintData($docNo, [
                'show_signatures' => true,
                'user_id' => $user->id,
            ]);
            
            if (!$result['success']) {
                return back()->withErrors(['error' => $result['error']]);
            }
            
            \Log::info('Print Page Accessed', [
                'po_docno' => $docNo,
                'user_id' => $user->id,
                'user_name' => $user->full_name,
            ]);
            
            // แสดงหน้าพิมพ์
            return view('print.purchase-order', $result['data']);
            
        } catch (\Exception $e) {
            \Log::error('Print Page Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาดในการเตรียมเอกสาร: ' . $e->getMessage()]);
        }
    }

    /**
     * เปิดหน้าพิมพ์ใน Popup Window
     */
    public function printPopup(Request $request, $docNo)
    {
        try {
            $user = Auth::user();
            
            // ตรวจสอบสิทธิ์
            $canPrint = $this->printService->canPrint($docNo, $user->id);
            
            if (!$canPrint['can_print']) {
                return response()->json([
                    'success' => false,
                    'error' => $canPrint['reason']
                ], 403);
            }
            
            // เตรียมข้อมูล
            $result = $this->printService->preparePrintData($docNo, [
                'show_signatures' => true,
                'popup_mode' => true,
                'user_id' => $user->id,
            ]);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
            
            // แสดงหน้าพิมพ์แบบ popup
            return view('print.purchase-order', $result['data']);
            
        } catch (\Exception $e) {
            \Log::error('Print Popup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการเตรียมเอกสาร'
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะการพิมพ์
     */
    public function checkPrintStatus($docNo)
    {
        try {
            $user = Auth::user();
            
            $canPrint = $this->printService->canPrint($docNo, $user->id);
            
            // ดึงข้อมูล approval count
            $approvalCount = DB::connection('modern')
                ->table('po_approvals')
                ->where('po_docno', $docNo)
                ->where('approval_status', 'approved')
                ->count();
            
            // ดึงข้อมูล print history count
            $printCount = DB::connection('modern')
                ->table('po_prints')
                ->where('po_docno', $docNo)
                ->count();
                
            // ตรวจสอบว่ามี signature หรือไม่
            $hasSignatures = DB::connection('modern')
                ->table('po_approvals')
                ->join('users', 'po_approvals.approver_id', '=', 'users.id')
                ->join('user_signatures', function($join) {
                    $join->on('users.id', '=', 'user_signatures.user_id')
                        ->where('user_signatures.is_active', '=', true);
                })
                ->where('po_approvals.po_docno', $docNo)
                ->where('po_approvals.approval_status', 'approved')
                ->exists();
            
            return response()->json([
                'success' => true,
                'can_print' => $canPrint['can_print'],
                'reason' => $canPrint['reason'] ?? null,
                'approval_count' => $approvalCount,
                'print_count' => $printCount,
                'has_signatures' => $hasSignatures,
                'po_amount' => $canPrint['po_amount'] ?? 0,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงรายการ Print History
     */
    public function printHistory($docNo)
    {
        try {
            $user = Auth::user();
            
            // ตรวจสอบสิทธิ์
            if (!$user->isAdmin() && !$user->isManager() && !$user->isGM()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                ], 403);
            }
            
            $history = DB::connection('modern')
                ->table('po_prints')
                ->join('users', 'po_prints.printed_by', '=', 'users.id')
                ->where('po_prints.po_docno', $docNo)
                ->select([
                    'po_prints.*',
                    'users.full_name as printed_by_name',
                    'users.username as printed_by_username',
                ])
                ->orderBy('po_prints.created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดูข้อมูลที่จะแสดงในการพิมพ์ (สำหรับ Debug)
     */
    public function debugPrint($docNo)
    {
        try {
            $user = Auth::user();
            
            // เฉพาะ Admin เท่านั้น
            if (!$user->isAdmin()) {
                abort(403, 'Access denied');
            }
            
            $data = $this->printService->previewPrintData($docNo);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดาวน์โหลดข้อมูล Print เป็น JSON (สำหรับ backup)
     */
    public function exportPrintData($docNo)
    {
        try {
            $user = Auth::user();
            
            // ตรวจสอบสิทธิ์
            if (!$user->isAdmin() && !$user->isManager()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์ในการ export ข้อมูล'
                ], 403);
            }
            
            $result = $this->printService->preparePrintData($docNo);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
            
            $filename = "PO_Data_{$docNo}_" . date('Ymd_His') . ".json";
            
            return response()->json($result['data'], 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * แสดงรายการ PO (Method เดิมจาก Phase 2.1)
     */
    public function index(Request $request)
    {
        // Debug: ตรวจสอบ Database Connection ก่อน
        try {
            $testConnection = DB::connection('legacy')->select('SELECT TOP 1 @@VERSION as version');
            $connectionStatus = 'Connected to Legacy DB';
        } catch (\Exception $e) {
            $connectionStatus = 'Legacy DB Error: ' . $e->getMessage();
        }
        
        $filters = [
            'docno' => $request->get('docno'),
            'supplier' => $request->get('supplier'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'amount_from' => $request->get('amount_from'),
            'amount_to' => $request->get('amount_to'),
            'limit' => 20,
            'offset' => ($request->get('page', 1) - 1) * 20,
        ];
        
        // Debug: แสดงค่า filters
        \Log::info('PO Filters:', $filters);
        
        try {
            // กำหนด Pagination
            $page = $request->get('page', 1);
            $limit = 20; // แสดง 20 รายการต่อหน้า
            $offset = ($page - 1) * $limit;
            
            // สร้าง WHERE conditions สำหรับ Filters
            $whereConditions = "i.pdttyp = '1' AND h.DOCNO LIKE 'PP%' AND h.APPSTS <> 'C'";

            if (!empty($filters['docno'])) {
                $whereConditions .= " AND h.DOCNO LIKE '%" . $filters['docno'] . "%'";
            }
            
            if (!empty($filters['supplier'])) {
                $whereConditions .= " AND s.SUPNAM LIKE '%" . $filters['supplier'] . "%'";
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions .= " AND h.DOCDAT >= '" . $filters['date_from'] . "'";
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions .= " AND h.DOCDAT <= '" . $filters['date_to'] . "'";
            }
            
            if (!empty($filters['amount_from'])) {
                $whereConditions .= " AND h.NETAMT >= " . floatval($filters['amount_from']);
            }
            
            if (!empty($filters['amount_to'])) {
                $whereConditions .= " AND h.NETAMT <= " . floatval($filters['amount_to']);
            }
            
            // SQL Server 2008 Compatible Pagination using ROW_NUMBER()
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
                    JOIN [Romar1].[dbo].[INV_PDT] i ON d.PDTCD = i.PDTCD
                    WHERE {$whereConditions}
                    GROUP BY h.DOCNO, h.DOCDAT, s.SUPNAM, h.NETAMT, h.APPSTS, h.INTDES, s.SUPCD
                )
                SELECT DocNo, DateNo, SupName, NetAmount, AppStatus, Note, SupNo
                FROM PaginatedResults 
                WHERE RowNum BETWEEN " . ($offset + 1) . " AND " . ($offset + $limit);
            
            // Count Query สำหรับ Total Records
            $countQuery = "
                SELECT COUNT(DISTINCT h.DOCNO) as total
                FROM [Romar1].[dbo].[POC_POH] h
                JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
                JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
                JOIN [Romar1].[dbo].[INV_PDT] i ON d.PDTCD = i.PDTCD
                WHERE {$whereConditions}
            ";
            
            // Execute Queries
            $totalResult = DB::connection('legacy')->select($countQuery);
            $totalRecords = $totalResult[0]->total ?? 0;
            $totalPages = ceil($totalRecords / $limit);
            
            // Debug: Log the final query และ pagination info
            \Log::info('SQL Server 2008 Compatible Query:', [
                'limit' => $limit, 
                'offset' => $offset, 
                'page' => $page,
                'total_records' => $totalRecords,
                'row_range' => ($offset + 1) . ' - ' . ($offset + $limit)
            ]);
            
            $purchaseOrders = DB::connection('legacy')->select($query);
            
            // ดึงข้อมูล approval level แยกต่างหาก
            if (!empty($purchaseOrders)) {
                $poNumbers = collect($purchaseOrders)->pluck('DocNo')->toArray();
                $approvalLevels = DB::connection('modern')->table('po_approvals')
                    ->select('po_docno', DB::raw('MAX(approval_level) as max_approval_level'))
                    ->whereIn('po_docno', $poNumbers)
                    ->groupBy('po_docno')
                    ->get()
                    ->keyBy('po_docno');
                    
                // เพิ่มข้อมูล approval level เข้าไปใน purchase orders
                foreach ($purchaseOrders as $po) {
                    $po->max_approval_level = $approvalLevels->get($po->DocNo)->max_approval_level ?? 0;
                }
            }
            
            \Log::info('Paginated PO Query Result:', [
                'count' => count($purchaseOrders), 
                'filters_applied' => array_filter($filters),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalRecords,
                    'total_pages' => $totalPages
                ]
            ]);
            
            // ข้อมูล Pagination สำหรับ View
            $pagination = (object)[
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
                'has_previous' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
            ];
            
            return view('po.index', compact('purchaseOrders', 'filters', 'connectionStatus', 'pagination'));
            
        } catch (\Exception $e) {
            \Log::error('Error in PO Index: ' . $e->getMessage());
            
            // ถ้า Error ให้แสดง Error Message และข้อมูล Empty
            $purchaseOrders = [];
            $connectionStatus = 'Database Error: ' . $e->getMessage();
            $pagination = (object)[
                'current_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'total_pages' => 0,
                'has_more' => false,
                'has_previous' => false,
                'next_page' => null,
                'previous_page' => null,
            ];
            
            return view('po.index', compact('purchaseOrders', 'filters', 'connectionStatus', 'pagination'))
                ->withErrors(['error' => 'Error loading purchase orders: ' . $e->getMessage()]);
        }
    }

    /**
     * แสดงรายละเอียด PO (แบบเต็มรูปแบบ)
     */
    public function show($docNo)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->withErrors(['error' => 'Please login first']);
            }
            
            \Log::info('PO Show Full Detail:', [
                'user_id' => $user->id,
                'username' => $user->username,
                'docno' => $docNo
            ]);
            
            // ดึงข้อมูล PO แบบเต็ม
            $po = $this->poService->getPurchaseOrderByDocNo($docNo);
            
            if (!$po) {
                return back()->withErrors(['error' => 'Purchase Order not found or access denied']);
            }
            
            // ดึงสถานะการ Approve
            $approvalStatus = $this->poService->getApprovalStatus($docNo);
            
            // ตรวจสอบว่า User ปัจจุบันสามารถ Approve ได้หรือไม่
            $canApprove = $this->poService->canApprove($docNo, $user->id);
            
            \Log::info('PO Detail Loaded:', [
                'po_docno' => $po->header->DocNo,
                'po_amount' => $po->header->NetAmount,
                'items_count' => $po->details->count(),
                'can_approve' => $canApprove['can_approve'],
                'approval_history_count' => $approvalStatus->count()
            ]);
            
            return view('po.show', compact('po', 'approvalStatus', 'canApprove'));
            
        } catch (\Exception $e) {
            \Log::error('Error in PO Show: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            return back()->withErrors(['error' => 'Error loading purchase order: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk Approval Method (แก้ไขแล้ว)
     */
    public function bulkApprove(Request $request)
    {
        // แก้ไข validation rules
        $request->validate([
            'po_numbers' => 'required|array|min:1',  // เปลี่ยนจาก string เป็น array
            'po_numbers.*' => 'required|string',     // แต่ละ element ต้องเป็น string
            'action' => 'required|in:approve,reject',
            'bulk_note' => 'nullable|string|max:500',
        ], [
            'po_numbers.required' => 'Please select at least one PO',
            'po_numbers.array' => 'Invalid PO selection format',
            'po_numbers.min' => 'Please select at least one PO',
            'action.required' => 'Please select an action (approve/reject)',
            'action.in' => 'Invalid action selected',
        ]);
        
        try {
            $user = Auth::user();
            
            // ตรวจสอบว่ามี Digital Signature หรือไม่
            if (!$user->hasActiveSignature()) {
                return back()->withErrors(['error' => 'You need to upload a digital signature before approving POs.']);
            }
            
            // ดึง PO numbers จาก request (ตอนนี้เป็น array แล้ว)
            $poNumbers = $request->po_numbers;
            
            if (empty($poNumbers)) {
                return back()->withErrors(['error' => 'No PO numbers selected.']);
            }
            
            \Log::info('Bulk Approval Started', [
                'user_id' => $user->id,
                'po_count' => count($poNumbers),
                'action' => $request->action,
                'po_numbers' => $poNumbers
            ]);
            
            DB::connection('modern')->beginTransaction();
            
            $results = [];
            $successCount = 0;
            $batchId = 'BULK_' . date('YmdHis') . '_' . $user->id;
            
            foreach ($poNumbers as $docNo) {
                try {
                    // ตรวจสอบสิทธิ์การ Approve สำหรับแต่ละ PO
                    $canApprove = $this->poService->canApprove($docNo, $user->id);
                    
                    if ($canApprove['can_approve']) {
                        // ดึงข้อมูล PO เพื่อเอา Amount
                        $po = $this->poService->getPurchaseOrderByDocNo($docNo);
                        $poAmount = $po ? $po->header->NetAmount : 0;
                        
                        // บันทึก Approval Record
                        DB::connection('modern')->table('po_approvals')->insert([
                            'po_docno' => $docNo,
                            'approver_id' => $user->id,
                            'approval_level' => $canApprove['next_level'],
                            'approval_status' => $request->action === 'approve' ? 'approved' : 'rejected',
                            'approval_date' => now(),
                            'approval_note' => $request->bulk_note,
                            'po_amount' => $poAmount,
                            'approval_method' => 'bulk',
                            'bulk_approval_batch_id' => $batchId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        $results[$docNo] = 'success';
                        $successCount++;
                        
                        \Log::info('PO approved in bulk', [
                            'po_docno' => $docNo,
                            'approver_id' => $user->id,
                            'approval_level' => $canApprove['next_level'],
                            'batch_id' => $batchId
                        ]);
                        
                    } else {
                        $results[$docNo] = $canApprove['reason'];
                        \Log::warning('PO cannot be approved in bulk', [
                            'po_docno' => $docNo,
                            'reason' => $canApprove['reason']
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $results[$docNo] = 'Error: ' . $e->getMessage();
                    \Log::error('Error processing PO in bulk approval', [
                        'po_docno' => $docNo,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::connection('modern')->commit();
            
            // ส่ง Notification (ถ้ามี NotificationService)
            try {
                if (class_exists('App\Services\NotificationService')) {
                    $notificationService = new \App\Services\NotificationService();
                    
                    if ($request->action === 'approve') {
                        // ส่ง notification สำหรับ bulk approval (เฉพาะที่สำเร็จ)
                        foreach (array_keys(array_filter($results, fn($r) => $r === 'success')) as $docNo) {
                            try {
                                $canApprove = $this->poService->canApprove($docNo, $user->id);
                                $notificationService->sendApprovalNotification($docNo, $user, $canApprove['next_level'] ?? 1);
                            } catch (\Exception $e) {
                                \Log::warning('Failed to send notification for PO: ' . $docNo);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error sending bulk approval notifications: ' . $e->getMessage());
            }
            
            // สร้าง message ตอบกลับ
            $action = $request->action === 'approve' ? 'approved' : 'rejected';
            $message = "Bulk {$action} completed: {$successCount} POs {$action} successfully";
            
            if ($successCount < count($poNumbers)) {
                $failedCount = count($poNumbers) - $successCount;
                $message .= " ({$failedCount} POs could not be processed)";
                
                // แสดงรายละเอียด errors สำหรับ POs ที่ไม่สำเร็จ
                $failedDetails = [];
                foreach ($results as $docNo => $result) {
                    if ($result !== 'success') {
                        $failedDetails[] = "{$docNo}: {$result}";
                    }
                }
                
                if (!empty($failedDetails)) {
                    $message .= "\n\nDetails:\n" . implode("\n", array_slice($failedDetails, 0, 5));
                    if (count($failedDetails) > 5) {
                        $message .= "\n... and " . (count($failedDetails) - 5) . " more";
                    }
                }
            }
            
            \Log::info('Bulk Approval Completed', [
                'user_id' => $user->id,
                'batch_id' => $batchId,
                'total_pos' => count($poNumbers),
                'successful' => $successCount,
                'failed' => count($poNumbers) - $successCount
            ]);
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::connection('modern')->rollBack();
            \Log::error('Bulk Approval Error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'po_numbers' => $request->po_numbers ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Error processing bulk approval: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload Digital Signature
     */
    public function uploadSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:1024', // 1MB max
            'signature_name' => 'required|string|max:100',
        ]);
        
        $user = Auth::user();
        
        // Save signature file
        $path = $request->file('signature')->store('signatures', 'public');
        
        // Save to database
        DB::connection('modern')->table('user_signatures')->insert([
            'user_id' => $user->id,
            'signature_name' => $request->signature_name,
            'signature_path' => $path,
            'created_at' => now(),
        ]);
        
        return back()->with('success', 'Digital signature uploaded successfully');
    }
    
    /**
     * ประมวลผล Approval (Approve/Reject) - Method ใหม่
     */
    public function approve(Request $request, $docNo)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_note' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::connection('modern')->beginTransaction();
            
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->withErrors(['error' => 'Please login first']);
            }
            
            // ใช้ user->id แทน Auth::id()
            $canApprove = $this->poService->canApprove($docNo, $user->id);
            
            if (!$canApprove['can_approve']) {
                return back()->withErrors(['error' => $canApprove['reason']]);
            }
            
            // ดึงข้อมูล PO
            $po = $this->poService->getPurchaseOrderByDocNo($docNo);
            if (!$po) {
                return back()->withErrors(['error' => 'Purchase Order not found']);
            }
            
            $action = $request->input('action');
            $note = $request->input('approval_note');
            
            // บันทึก Approval Record - ใช้ user->id
            $approvalId = DB::connection('modern')->table('po_approvals')->insertGetId([
                'po_docno' => $docNo,
                'approver_id' => $user->id,
                'approval_level' => $canApprove['next_level'],
                'approval_status' => $action === 'approve' ? 'approved' : 'rejected',
                'approval_date' => now(),
                'approval_note' => $note,
                'po_amount' => $po->header->NetAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // ส่ง Notification (สร้าง instance แบบ manual)
            $notificationService = new \App\Services\NotificationService();
            
            if ($action === 'approve') {
                $notificationService->sendApprovalNotification($docNo, $user, $canApprove['next_level']);
                $message = 'Purchase Order approved successfully!';
            } else {
                $notificationService->sendRejectionNotification($docNo, $user, $note);
                $message = 'Purchase Order rejected successfully!';
            }
            
            DB::connection('modern')->commit();
            
            return redirect()->route('po.show', $docNo)->with('success', $message);
            
        } catch (\Exception $e) {
            DB::connection('modern')->rollBack();
            \Log::error('Approval Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error processing approval: ' . $e->getMessage()]);
        }
    }
}