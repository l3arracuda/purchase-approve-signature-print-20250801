<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PoApprovedService;

class DashboardController extends Controller
{
    protected $poApprovedService;

    public function __construct(PoApprovedService $poApprovedService = null)
    {
        $this->middleware('auth');
        $this->poApprovedService = $poApprovedService ?: app(PoApprovedService::class);
    }

    public function index()
    {
        $user = Auth::user();
        
        // ดึงข้อมูล PO เบื้องต้นจาก Legacy Database (เฉพาะ PP%)
        $poQuery = "
            SELECT TOP 10 
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                h.NETAMT as NetAmout,
                h.APPSTS as AppStatus
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
            JOIN [Romar1].[dbo].[INV_PDT] i on d.PDTCD = i.PDTCD
            WHERE i.PDTTYP = '1' and h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
            GROUP BY h.DOCDAT, h.DOCNO, h.RefPoNo, h.SUPCD, s.SUPNAM, h.NETAMT, h.APPSTS
            ORDER BY h.DOCNO DESC
        ";

        try {
            $recentPOs = DB::connection('legacy')->select($poQuery);
        } catch (\Exception $e) {
            $recentPOs = [];
        }

        // สถิติเบื้องต้น
        $stats = [
            'total_pos' => count($recentPOs),
            'user_role' => $user->role,
            'approval_level' => $user->approval_level,
        ];

        // ========== NEW: เพิ่มสถิติ PO Approved (สำหรับ Manager ขึ้นไป) ==========
        $approvedStats = null;
        $pendingApprovals = null;
        
        if ($user->isManager() || $user->isGM() || $user->isAdmin()) {
            try {
                // สถิติ PO ที่อนุมัติแล้ว
                $approvedStatsResult = $this->poApprovedService->getApprovedStats();
                if ($approvedStatsResult['success']) {
                    $approvedStats = $approvedStatsResult['general'];
                }

                // PO ที่รอการอนุมัติ (ตามระดับของ user)
                $pendingApprovals = $this->getPendingApprovalsForUser($user);

            } catch (\Exception $e) {
                \Log::error('Dashboard approved stats error: ' . $e->getMessage());
            }
        }

        // ========== NEW: ข้อมูลกิจกรรมล่าสุด ==========
        $recentActivities = null;
        if ($user->approval_level >= 2) {
            try {
                $recentActivities = $this->getRecentApprovalActivities($user);
            } catch (\Exception $e) {
                \Log::error('Dashboard recent activities error: ' . $e->getMessage());
            }
        }

        return view('dashboard', compact(
            'user', 
            'recentPOs', 
            'stats', 
            'approvedStats', 
            'pendingApprovals',
            'recentActivities'
        ));
    }

    /**
     * ดึงข้อมูล PO ที่รอการอนุมัติตามระดับของ User
     */
    private function getPendingApprovalsForUser($user)
    {
        try {
            $nextLevel = $user->approval_level;
            
            // ดึง PO ที่ผ่านการอนุมัติระดับก่อนหน้าแล้ว และรอการอนุมัติระดับปัจจุบัน
            $query = "
                WITH PendingPOs AS (
                    SELECT DISTINCT
                        pa.po_docno,
                        pa.po_amount,
                        MAX(pa.customer_name) as customer_name,
                        MAX(pa.approval_date) as last_approval_date,
                        MAX(pa.approval_level) as current_level
                    FROM [Romar128].[dbo].[po_approvals] pa
                    WHERE pa.po_docno LIKE 'PP%'
                        AND pa.approval_status = 'approved'
                    GROUP BY pa.po_docno, pa.po_amount
                    HAVING MAX(pa.approval_level) = ?
                        AND MAX(pa.approval_level) < 3
                )
                SELECT TOP 5 
                    po_docno,
                    po_amount,
                    customer_name,
                    last_approval_date,
                    current_level,
                    DATEDIFF(day, last_approval_date, GETDATE()) as days_waiting
                FROM PendingPOs
                WHERE current_level < ?
                ORDER BY last_approval_date DESC
            ";
            
            $params = [$nextLevel - 1, $nextLevel];
            $pendingPOs = DB::connection('modern')->select($query, $params);
            
            return [
                'count' => count($pendingPOs),
                'pos' => $pendingPOs,
                'next_level' => $nextLevel
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error getting pending approvals: ' . $e->getMessage());
            return [
                'count' => 0,
                'pos' => [],
                'next_level' => $user->approval_level
            ];
        }
    }

    /**
     * ดึงกิจกรรมการอนุมัติล่าสุด
     */
    private function getRecentApprovalActivities($user)
    {
        try {
            $query = "
                SELECT TOP 10
                    pa.po_docno,
                    pa.po_amount,
                    pa.customer_name,
                    pa.approval_level,
                    pa.approval_date,
                    pa.approval_note,
                    u.full_name as approver_name,
                    u.role as approver_role,
                    CASE 
                        WHEN pa.approval_level = 1 THEN 'User'
                        WHEN pa.approval_level = 2 THEN 'Manager'
                        WHEN pa.approval_level = 3 THEN 'GM'
                        ELSE 'Unknown'
                    END as level_name
                FROM [Romar128].[dbo].[po_approvals] pa
                JOIN [Romar128].[dbo].[users] u ON pa.approver_id = u.id
                WHERE pa.po_docno LIKE 'PP%'
                    AND pa.approval_status = 'approved'
                    AND pa.approval_date >= DATEADD(day, -7, GETDATE())
                ORDER BY pa.approval_date DESC
            ";
            
            $activities = DB::connection('modern')->select($query);
            
            // Format activities
            foreach ($activities as $activity) {
                $activity->formatted_date = \Carbon\Carbon::parse($activity->approval_date)->format('d/m/Y H:i');
                $activity->human_date = \Carbon\Carbon::parse($activity->approval_date)->diffForHumans();
                
                // กำหนดสี badge ตาม level
                switch ($activity->approval_level) {
                    case 1:
                        $activity->level_class = 'bg-info';
                        break;
                    case 2:
                        $activity->level_class = 'bg-warning';
                        break;
                    case 3:
                        $activity->level_class = 'bg-success';
                        break;
                    default:
                        $activity->level_class = 'bg-secondary';
                }
            }
            
            return $activities;
            
        } catch (\Exception $e) {
            \Log::error('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ========== NEW: API Endpoint สำหรับ Dashboard Stats ==========
     */
    public function getDashboardStats()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $stats = [
                'user_info' => [
                    'name' => $user->full_name,
                    'role' => $user->role,
                    'approval_level' => $user->approval_level,
                ],
                'can_view_approved' => $user->isManager() || $user->isGM() || $user->isAdmin(),
            ];

            // เพิ่มสถิติสำหรับ Manager ขึ้นไป
            if ($stats['can_view_approved']) {
                $approvedStatsResult = $this->poApprovedService->getApprovedStats();
                if ($approvedStatsResult['success']) {
                    $stats['approved_pos'] = $approvedStatsResult['general'];
                }

                $stats['pending_approvals'] = $this->getPendingApprovalsForUser($user);
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard stats API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'
            ], 500);
        }
    }

    /**
     * ========== NEW: Quick Action - Mark Notifications as Read ==========
     */
    public function markNotificationsRead()
    {
        try {
            $user = Auth::user();
            
            DB::connection('modern')
                ->table('notifications')
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Mark notifications read error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error marking notifications as read'], 500);
        }
    }
}