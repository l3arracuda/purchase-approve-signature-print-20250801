<?php
// แทนที่ app/Services/PDFService.php ด้วย PrintService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PrintService
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * เตรียมข้อมูลสำหรับ Print HTML
     */
    public function preparePrintData($docNo, $options = [])
    {
        try {
            // ตรวจสอบว่า PO มีอยู่จริง
            $po = $this->purchaseOrderService->getPurchaseOrderByDocNo($docNo);
            if (!$po) {
                throw new \Exception("Purchase Order {$docNo} not found");
            }

            // ดึงข้อมูล Approval History พร้อมลายเซ็น
            $approvalHistory = $this->getApprovalHistoryWithSignatures($docNo);

            // ดึงข้อมูลบริษัท
            $companyInfo = $this->getCompanyInfo();

            // เตรียมข้อมูลสำหรับ Print
            $data = [
                'po' => $po,
                'approvals' => $approvalHistory,
                'company' => $companyInfo,
                'generated_at' => Carbon::now(),
                'options' => $options,
                'print_title' => "Purchase Order - {$docNo}",
            ];

            // บันทึกประวัติการพิมพ์
            $this->recordPrintHistory($docNo, auth()->id(), 'html_print');

            \Log::info('Print Data Prepared Successfully', [
                'po_docno' => $docNo,
                'user_id' => auth()->id(),
                'approvals_count' => $approvalHistory->count(),
            ]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            \Log::error('Print Data Preparation Error', [
                'po_docno' => $docNo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงข้อมูล Approval History พร้อมลายเซ็น (แก้ไขให้แสดง signature ได้)
     */
    protected function getApprovalHistoryWithSignatures($docNo)
    {
        try {
            $approvals = DB::connection('modern')
                ->table('po_approvals')
                ->join('users', 'po_approvals.approver_id', '=', 'users.id')
                ->leftJoin('user_signatures', function($join) {
                    $join->on('users.id', '=', 'user_signatures.user_id')
                         ->where('user_signatures.is_active', '=', true);
                })
                ->where('po_approvals.po_docno', $docNo)
                ->where('po_approvals.approval_status', 'approved')
                ->select([
                    'po_approvals.*',
                    'users.full_name as approver_name',
                    'users.username as approver_username',
                    'users.role as approver_role',
                    'user_signatures.signature_path',
                    'user_signatures.signature_name',
                ])
                ->orderBy('po_approvals.approval_level')
                ->orderBy('po_approvals.approval_date')
                ->get();

            // เตรียมข้อมูลลายเซ็นสำหรับแต่ละ approval
            return $approvals->map(function($approval) {
                // ตรวจสอบและเตรียม signature path
                if ($approval->signature_path) {
                    $signaturePath = storage_path('app/public/' . $approval->signature_path);
                    
                    // ตรวจสอบว่าไฟล์มีอยู่จริง
                    if (file_exists($signaturePath)) {
                        $approval->signature_full_path = $signaturePath;
                        $approval->signature_url = asset('storage/' . $approval->signature_path);
                        
                        // สำหรับ HTML แปลงเป็น base64 เพื่อแสดงใน img tag
                        $imageData = file_get_contents($signaturePath);
                        $imageType = pathinfo($signaturePath, PATHINFO_EXTENSION);
                        $approval->signature_base64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                        $approval->has_signature = true;
                    } else {
                        $approval->signature_full_path = null;
                        $approval->signature_url = null;
                        $approval->signature_base64 = null;
                        $approval->has_signature = false;
                    }
                } else {
                    $approval->signature_full_path = null;
                    $approval->signature_url = null;
                    $approval->signature_base64 = null;
                    $approval->has_signature = false;
                }

                // Format วันที่
                $approval->formatted_date = Carbon::parse($approval->approval_date)->format('d/m/Y H:i');
                $approval->thai_date = Carbon::parse($approval->approval_date)->locale('th')->isoFormat('DD MMMM YYYY เวลา HH:mm น.');

                // Level name
                $approval->level_name = $this->getApprovalLevelName($approval->approval_level);

                return $approval;
            });

        } catch (\Exception $e) {
            \Log::error('Error getting approval history with signatures', [
                'po_docno' => $docNo,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * ดึงข้อมูลบริษัท
     */
    protected function getCompanyInfo()
    {
        return [
            'name' => 'บริษัท โรม่าอุตสาหกรรม จำกัด',
            'name_en' => 'Roma Industrial Company Limited',
            'address' => '234 ถ.พระรามที่ 2 ซอย 50 แขวงแสมดำ เขตบางขุนเทียน กรุงเทพฯ 10150',
            'address_en' => '234 Rama II Road, Soi 50, Samdam, Bangkhunthian, Bangkok 10150',
            'tel' => '02-415-0072',
            'fax' => '02-415-0244',
            'email' => 'info@romar.co.th',
            'website' => 'www.romar.co.th',
            'tax_id' => '0-1055-28037-07-6',
            'logo_path' => $this->getCompanyLogoPath(), // path ไปยังโลโก้ (ถ้ามี)
        ];
    }

    /**
     * ดึง path ของโลโก้บริษัท
     */
    protected function getCompanyLogoPath()
    {
        // ลิสต์ของโลโก้ที่เป็นไปได้ (ตามที่ผู้ใช้บอก)
        $possibleLogos = [
            'signatures/Romar100px.png', // ตำแหน่งที่ผู้ใช้บอก
            'signatures/romar100px.png', // case-insensitive
            'signatures/logo.png',
        ];

        foreach ($possibleLogos as $logoPath) {
            $fullPath = storage_path('app/public/' . $logoPath);
            
            if (file_exists($fullPath)) {
                // Return asset URL สำหรับ web
                return asset('storage/' . $logoPath);
            }
        }

        // ถ้าไม่พบโลโก้ ให้สร้างโลโก้เริ่มต้น
        $this->createDefaultLogo();
        
        // ถ้ายังไม่มี ให้ใช้ data URL placeholder
        return 'data:image/svg+xml;base64,' . base64_encode($this->getDefaultLogoSvg());
    }

    /**
     * สร้างโลโก้เริ่มต้น
     */
    private function createDefaultLogo()
    {
        $logoPath = storage_path('app/public/signatures/Romar100px.png');
        $logoDir = dirname($logoPath);
        
        // สร้างโฟลเดอร์ถ้าไม่มี
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }
        
        // สร้างโลโก้ SVG แล้วแปลงเป็น PNG
        $svg = $this->getDefaultLogoSvg();
        file_put_contents(storage_path('app/public/signatures/company_logo.svg'), $svg);
    }

    /**
     * ดึง SVG โลโก้เริ่มต้น
     */
    private function getDefaultLogoSvg()
    {
        return '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" fill="#2563eb" rx="8"/>
            <text x="50" y="35" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">ROMA</text>
            <text x="50" y="55" text-anchor="middle" fill="white" font-family="Arial" font-size="10">INDUSTRIAL</text>
            <text x="50" y="75" text-anchor="middle" fill="white" font-family="Arial" font-size="8">COMPANY</text>
        </svg>';
    }

    /**
     * บันทึกประวัติการพิมพ์
     */
    protected function recordPrintHistory($docNo, $userId, $type = 'html_print')
    {
        try {
            DB::connection('modern')->table('po_prints')->insert([
                'po_docno' => $docNo,
                'printed_by' => $userId,
                'print_type' => $type,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to record print history', [
                'po_docno' => $docNo,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * แปลง Approval Level เป็นชื่อ
     */
    protected function getApprovalLevelName($level)
    {
        switch ($level) {
            case 1: return 'การอนุมัติระดับผู้ใช้';
            case 2: return 'การอนุมัติระดับผู้จัดการ';
            case 3: return 'การอนุมัติสุดท้าย (MD)';
            default: return 'ระดับการอนุมัติไม่ทราบ';
        }
    }

    /**
     * ตรวจสอบสิทธิ์ในการพิมพ์
     */
    public function canPrint($docNo, $userId)
    {
        try {
            // ตรวจสอบว่า PO มีอยู่จริง
            $po = $this->purchaseOrderService->getPurchaseOrderByDocNo($docNo);
            if (!$po) {
                return [
                    'can_print' => false,
                    'reason' => 'ไม่พบใบสั่งซื้อนี้',
                ];
            }

            // ตรวจสอบว่า User มีสิทธิ์
            $user = DB::connection('modern')->table('users')->find($userId);
            if (!$user || !$user->is_active) {
                return [
                    'can_print' => false,
                    'reason' => 'ไม่พบผู้ใช้หรือผู้ใช้ไม่ได้ใช้งาน',
                ];
            }

            return [
                'can_print' => true,
                'po_amount' => $po->header->NetAmount,
            ];

        } catch (\Exception $e) {
            return [
                'can_print' => false,
                'reason' => 'เกิดข้อผิดพลาดระบบ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดูข้อมูลที่จะแสดงในการพิมพ์ (สำหรับ debug)
     */
    public function previewPrintData($docNo)
    {
        $result = $this->preparePrintData($docNo);
        return $result['success'] ? $result['data'] : null;
    }
}