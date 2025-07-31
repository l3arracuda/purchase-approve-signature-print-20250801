<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class NotificationService
{
    /**
     * ส่งการแจ้งเตือนเมื่อมีการ Approve
     */
    public function sendApprovalNotification($poDocNo, $approver, $currentLevel)
    {
        // หา User ที่ต้องได้รับการแจ้งเตือนในระดับถัดไป
        $nextLevel = $currentLevel + 1;
        
        // ตัวอย่าง: Level 1=User, 2=Manager, 3=GM
        $nextUsers = collect();
        
        if ($nextLevel == 2) {
            // หา Manager ทั้งหมด
            $nextUsers = User::where('role', 'manager')->where('is_active', true)->get();
        } elseif ($nextLevel == 3) {
            // หา GM ทั้งหมด
            $nextUsers = User::where('role', 'gm')->where('is_active', true)->get();
        }
        
        // สร้าง Notification สำหรับแต่ละ User
        foreach ($nextUsers as $user) {
            DB::connection('modern')->table('notifications')->insert([
                'user_id' => $user->id,
                'type' => 'approval_required',
                'title' => 'PO Approval Required',
                'message' => "Purchase Order {$poDocNo} requires your approval. Approved by {$approver->full_name} ({$approver->role})",
                'data' => json_encode([
                    'po_docno' => $poDocNo,
                    'approved_by' => $approver->full_name,
                    'approved_by_role' => $approver->role,
                    'approval_level' => $currentLevel,
                    'next_level' => $nextLevel,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        \Log::info('Approval notifications sent', [
            'po_docno' => $poDocNo,
            'approver' => $approver->full_name,
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'notified_users' => $nextUsers->count(),
        ]);
    }
    
    /**
     * ส่งการแจ้งเตือนเมื่อมีการ Reject
     */
    public function sendRejectionNotification($poDocNo, $rejector, $reason)
    {
        // หา Admin และ Manager ที่เกี่ยวข้อง
        $notifyUsers = User::whereIn('role', ['admin', 'manager', 'gm'])
            ->where('is_active', true)
            ->get();
            
        foreach ($notifyUsers as $user) {
            DB::connection('modern')->table('notifications')->insert([
                'user_id' => $user->id,
                'type' => 'approval_rejected',
                'title' => 'PO Rejected',
                'message' => "Purchase Order {$poDocNo} has been rejected by {$rejector->full_name} ({$rejector->role})",
                'data' => json_encode([
                    'po_docno' => $poDocNo,
                    'rejected_by' => $rejector->full_name,
                    'rejected_by_role' => $rejector->role,
                    'reason' => $reason,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        \Log::info('Rejection notifications sent', [
            'po_docno' => $poDocNo,
            'rejector' => $rejector->full_name,
            'reason' => $reason,
            'notified_users' => $notifyUsers->count(),
        ]);
    }
    
    /**
     * ดึงการแจ้งเตือนของ User
     */
    public function getUserNotifications($userId, $unreadOnly = false)
    {
        $query = DB::connection('modern')
            ->table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');
            
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }
        
        return $query->get();
    }
    
    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     */
    public function markAsRead($notificationId)
    {
        DB::connection('modern')
            ->table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }
}