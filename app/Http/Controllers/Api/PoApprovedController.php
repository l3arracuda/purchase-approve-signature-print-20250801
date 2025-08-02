<?php
// app/Http/Controllers/Api/PoApprovedController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Services\PoApprovedService;

class PoApprovedController extends Controller
{
    protected $poApprovedService;

    public function __construct(PoApprovedService $poApprovedService)
    {
        $this->middleware('auth');
        $this->poApprovedService = $poApprovedService;
    }

    /**
     * ดึงข้อมูล PO Approved ผ่าน AJAX
     */
    public function getApprovedPOs(Request $request)
    {
        try {
            $user = Auth::user();
            
            // ตรวจสอบสิทธิ์
            if (!$user->isManager() && !$user->isGM() && !$user->isAdmin() && !$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                ], 403);
            }

            // ดึง filters จาก request
            $filters = [
                'docno' => $request->get('docno'),
                'customer' => $request->get('customer'),
                'amount_from' => $request->get('amount_from'),
                'amount_to' => $request->get('amount_to'),
                'approval_level' => $request->get('approval_level'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $page = $request->get('page', 1);
            $limit = $request->get('per_page', 20);

            $result = $this->poApprovedService->getApprovedPOs($filters, $page, $limit);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => array_filter($filters)
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in getApprovedPOs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'
            ], 500);
        }
    }

    /**
     * ดึงสถิติ PO Approved
     */
    public function getStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!`$user->isManager() && !`$user->isGM() && !`$user->isAdmin() && !`$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                ], 403);
            }

            $filters = [
                'docno' => $request->get('docno'),
                'customer' => $request->get('customer'),
                'amount_from' => $request->get('amount_from'),
                'amount_to' => $request->get('amount_to'),
                'approval_level' => $request->get('approval_level'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $result = $this->poApprovedService->getApprovedStats($filters);

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('API Error in getStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการดึงสถิติ'
            ], 500);
        }
    }

    /**
     * Export PO Approved เป็น CSV
     */
    public function exportCSV(Request $request)
    {
        try {
            $user = Auth::user();
            
            // เฉพาะ Manager ขึ้นไป
            if (!`$user->isManager() && !`$user->isGM() && !`$user->isAdmin() && !`$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์ในการ Export ข้อมูล'
                ], 403);
            }

            $filters = [
                'docno' => $request->get('docno'),
                'customer' => $request->get('customer'),
                'amount_from' => $request->get('amount_from'),
                'amount_to' => $request->get('amount_to'),
                'approval_level' => $request->get('approval_level'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $result = $this->poApprovedService->exportApprovedPOsData($filters);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

            // สร้าง CSV content
            $csvContent = '';
            foreach ($result['data'] as $row) {
                $csvContent .= '"' . implode('","', $row) . '"' . "\n";
            }

            // เพิ่ม BOM สำหรับ UTF-8
            $csvContent = "\xEF\xBB\xBF" . $csvContent;

            \Log::info('PO Approved CSV Export', [
                'user_id' => $user->id,
                'filters' => array_filter($filters),
                'rows_exported' => count($result['data']) - 1, // ลบ header row
            ]);

            return Response::make($csvContent, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            \Log::error('Export CSV Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการ Export ข้อมูล'
            ], 500);
        }
    }

    /**
     * Export PO Approved เป็น Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!`$user->isManager() && !`$user->isGM() && !`$user->isAdmin() && !`$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์ในการ Export ข้อมูล'
                ], 403);
            }

            // สำหรับตอนนี้ให้ใช้ CSV format ชั่วคราว
            // ในอนาคตสามารถเพิ่ม PhpSpreadsheet library
            return $this->exportCSV($request);

        } catch (\Exception $e) {
            \Log::error('Export Excel Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'ฟีเจอร์ Export Excel กำลังพัฒนา กรุณาใช้ CSV แทน'
            ], 500);
        }
    }

    /**
     * Update customer data สำหรับ PO เก่า (Admin only)
     */
    public function updateCustomerData(Request $request)
    {
        try {
            $user = Auth::user();
            
            // เฉพาะ Admin เท่านั้น
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์ในการ Update ข้อมูล'
                ], 403);
            }

            $limit = $request->get('limit', 100);
            
            if ($limit > 1000) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่สามารถประมวลผลเกิน 1000 รายการในครั้งเดียว'
                ], 400);
            }

            $result = $this->poApprovedService->updateMissingCustomerData($limit);

            \Log::info('Customer Data Update via API', [
                'user_id' => $user->id,
                'limit' => $limit,
                'result' => $result
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Update Customer Data Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการ Update ข้อมูล'
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะข้อมูล Customer
     */
    public function getDataStatus()
    {
        try {
            $user = Auth::user();
            
            if (!`$user->isManager() && !`$user->isGM() && !`$user->isAdmin() && !`$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                ], 403);
            }

            // นับจำนวนรายการต่างๆ
            $totalRecords = \DB::connection('modern')
                ->table('po_approvals')
                ->where('po_docno', 'LIKE', 'PP%')
                ->where('approval_status', 'approved')
                ->count();

            $missingCustomer = \DB::connection('modern')
                ->table('po_approvals')
                ->where('po_docno', 'LIKE', 'PP%')
                ->where('approval_status', 'approved')
                ->whereNull('customer_name')
                ->count();

            $missingItems = \DB::connection('modern')
                ->table('po_approvals')
                ->where('po_docno', 'LIKE', 'PP%')
                ->where('approval_status', 'approved')
                ->whereNull('item_count')
                ->count();

            $completeRecords = $totalRecords - max($missingCustomer, $missingItems);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_records' => $totalRecords,
                    'complete_records' => $completeRecords,
                    'missing_customer' => $missingCustomer,
                    'missing_items' => $missingItems,
                    'completion_percentage' => $totalRecords > 0 ? round(($completeRecords / $totalRecords) * 100, 2) : 0,
                    'needs_update' => ($missingCustomer > 0 || $missingItems > 0)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get Data Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการตรวจสอบสถานะข้อมูล'
            ], 500);
        }
    }

    /**
     * ดึงรายละเอียด PO เฉพาะสำหรับ Modal
     */
    public function getPODetail($docNo)
    {
        try {
            $user = Auth::user();
            
            if (!`$user->isManager() && !`$user->isGM() && !`$user->isAdmin() && !`$user->isUser()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                ], 403);
            }

            // ดึงข้อมูลการอนุมัติทั้งหมดของ PO นี้
            $approvals = \DB::connection('modern')
                ->table('po_approvals')
                ->join('users', 'po_approvals.approver_id', '=', 'users.id')
                ->where('po_approvals.po_docno', $docNo)
                ->where('po_approvals.approval_status', 'approved')
                ->select([
                    'po_approvals.*',
                    'users.full_name as approver_name',
                    'users.username as approver_username',
                    'users.role as approver_role'
                ])
                ->orderBy('po_approvals.approval_level', 'asc')
                ->orderBy('po_approvals.approval_date', 'asc')
                ->get();

            if ($approvals->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่พบข้อมูลการอนุมัติสำหรับ PO นี้'
                ], 404);
            }

            // format ข้อมูล
            $formattedApprovals = $approvals->map(function($approval) {
                return [
                    'id' => $approval->id,
                    'approval_level' => $approval->approval_level,
                    'approver_name' => $approval->approver_name,
                    'approver_username' => $approval->approver_username,
                    'approver_role' => $approval->approver_role,
                    'approval_date' => $approval->approval_date,
                    'approval_note' => $approval->approval_note,
                    'approval_method' => $approval->approval_method ?? 'single',
                    'formatted_date' => \Carbon\Carbon::parse($approval->approval_date)->format('d/m/Y H:i'),
                    'human_date' => \Carbon\Carbon::parse($approval->approval_date)->diffForHumans(),
                ];
            });

            $summary = $approvals->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'po_docno' => $docNo,
                    'customer_name' => $summary->customer_name,
                    'item_count' => $summary->item_count,
                    'po_amount' => $summary->po_amount,
                    'max_approval_level' => $approvals->max('approval_level'),
                    'total_approvals' => $approvals->count(),
                    'approvals' => $formattedApprovals
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get PO Detail Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการดึงรายละเอียด PO'
            ], 500);
        }
    }
}
