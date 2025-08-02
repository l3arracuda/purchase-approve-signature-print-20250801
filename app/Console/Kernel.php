<?php
// app/Console/Kernel.php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========== NEW: Schedule Commands ==========
        
        // อัปเดตข้อมูล Customer Data ทุกวันเที่ยงคืน
        $schedule->command('po:update-customer-data --limit=500')
                 ->daily()
                 ->at('00:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/customer-data-update.log'));

        // ทำความสะอาด Log files เก่า (เก็บ 30 วัน)
        $schedule->command('log:clear --days=30')
                 ->weekly()
                 ->sundays()
                 ->at('01:00');

        // ตรวจสอบ Database Connections ทุก 6 ชั่วโมง
        $schedule->call(function () {
            $this->checkDatabaseConnections();
        })->everyFourHours();

        // สร้าง Backup ข้อมูล Approval ทุกสัปดาห์
        $schedule->call(function () {
            $this->backupApprovalData();
        })->weekly()
          ->sundays()
          ->at('02:00');

        // ส่งรายงานสถิติรายสัปดาห์ (ถ้าต้องการ)
        // $schedule->command('po:weekly-report')
        //          ->weekly()
        //          ->mondays()
        //          ->at('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     */
    private function checkDatabaseConnections()
    {
        try {
            // ทดสอบ Modern Database
            \DB::connection('modern')->select('SELECT 1 as test');
            \Log::info('Database Health Check: Modern DB - OK');

            // ทดสอบ Legacy Database  
            \DB::connection('legacy')->select('SELECT 1 as test');
            \Log::info('Database Health Check: Legacy DB - OK');

            return true;

        } catch (\Exception $e) {
            \Log::error('Database Health Check Failed: ' . $e->getMessage());
            
            // อาจจะส่ง notification หรือ email แจ้งเตือน Admin
            $this->notifyAdminAboutDatabaseIssue($e->getMessage());
            
            return false;
        }
    }

    /**
     * Backup ข้อมูล Approval
     */
    private function backupApprovalData()
    {
        try {
            $filename = 'po_approvals_backup_' . date('Ymd_His') . '.json';
            $backupPath = storage_path('backups/' . $filename);

            // สร้าง directory ถ้าไม่มี
            if (!file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            // ดึงข้อมูล Approval ล่าสุด (30 วันย้อนหลัง)
            $approvals = \DB::connection('modern')
                ->table('po_approvals')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->get();

            // บันทึกเป็น JSON
            file_put_contents($backupPath, $approvals->toJson(JSON_PRETTY_PRINT));

            // ลบ backup เก่าที่เก็บไว้เกิน 3 เดือน
            $this->cleanOldBackups();

            \Log::info("Approval data backup completed: {$filename}", [
                'records_backed_up' => $approvals->count(),
                'file_size' => filesize($backupPath) . ' bytes'
            ]);

        } catch (\Exception $e) {
            \Log::error('Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * ลบ backup files เก่า
     */
    private function cleanOldBackups()
    {
        try {
            $backupDir = storage_path('backups');
            if (!is_dir($backupDir)) {
                return;
            }

            $files = glob($backupDir . '/po_approvals_backup_*.json');
            $cutoffTime = time() - (90 * 24 * 60 * 60); // 90 วันก่อน

            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    \Log::info('Old backup file removed: ' . basename($file));
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error cleaning old backups: ' . $e->getMessage());
        }
    }

    /**
     * แจ้งเตือน Admin เมื่อมีปัญหา Database
     */
    private function notifyAdminAboutDatabaseIssue($error)
    {
        try {
            // ในอนาคตอาจจะส่ง Email, Slack, หรือ SMS
            // Mail::to(config('app.admin_email'))->send(new DatabaseIssueNotification($error));
            
            // สำหรับตอนนี้ให้ log แจ้งเตือนไว้ก่อน
            \Log::critical('ADMIN ALERT: Database connection issue requires attention', [
                'error' => $error,
                'timestamp' => now()->toISOString(),
                'server' => request()->server('SERVER_NAME') ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to notify admin: ' . $e->getMessage());
        }
    }
}