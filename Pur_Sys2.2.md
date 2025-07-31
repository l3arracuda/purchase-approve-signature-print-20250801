# Phase 2.2: PO Detail & Approval System

## 🎯 สิ่งที่เราจะทำในขั้นตอนนี้
1. สร้างหน้า PO Detail แบบเต็ม (รายการสินค้า + ข้อมูลผู้ขาย)
2. สร้างระบบ Approval Workflow แบบลำดับ (User → Manager → GM)
3. สร้าง Approval Controller & Methods
4. สร้าง Notification Service สำหรับแจ้งเตือน
5. สร้าง Approval History & Status Tracking

---

## 1️⃣ สร้าง PO Detail View แบบเต็ม

### 1.1 สร้างไฟล์ PO Detail View ใหม่

สร้างไฟล์ `resources/views/po/show.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- PO Header Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-0">
                                <i class="fas fa-file-invoice"></i>
                                Purchase Order: {{ $po->header->DocNo }}
                            </h4>
                            <small>{{ date('d/m/Y', strtotime($po->header->DateNo)) }}</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-secondary fs-6">{{ $po->header->AppStatus }}</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <!-- PO Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Purchase Order Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="30%"><strong>PO Number:</strong></td>
                                    <td>{{ $po->header->DocNo }}</td>
                                </tr>
                                <tr>
                                    <td><strong>PO Date:</strong></td>
                                    <td>{{ date('d/m/Y', strtotime($po->header->DateNo)) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td>{{ $po->header->DocRef ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Credit Term:</strong></td>
                                    <td>{{ $po->header->CreditTerm }} days</td>
                                </tr>
                                @if($po->header->Note)
                                <tr>
                                    <td><strong>Note:</strong></td>
                                    <td>{{ $po->header->Note }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary">Supplier Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="30%"><strong>Supplier:</strong></td>
                                    <td>{{ $po->header->SupName }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Code:</strong></td>
                                    <td>{{ $po->header->SupNo }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td>
                                        {{ $po->header->AddressSup }}<br>
                                        {{ $po->header->Province }} {{ $po->header->ZipCode }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td>
                                        {{ $po->header->ContactName ?? '-' }}<br>
                                        Tel: {{ $po->header->Phone ?? '-' }}<br>
                                        Fax: {{ $po->header->FAX ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PO Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list"></i>
                        Purchase Order Items ({{ $po->details->count() }} items)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Product Code</th>
                                    <th width="35%">Product Name</th>
                                    <th width="10%" class="text-center">Quantity</th>
                                    <th width="10%" class="text-center">Unit</th>
                                    <th width="15%" class="text-end">Unit Price</th>
                                    <th width="15%" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->details as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><code>{{ $item->ProductNo }}</code></td>
                                    <td>{{ $item->ProductName }}</td>
                                    <td class="text-center">{{ number_format($item->QTY, 2) }}</td>
                                    <td class="text-center">{{ $item->Unit }}</td>
                                    <td class="text-end">{{ number_format($item->Price, 2) }}</td>
                                    <td class="text-end">
                                        <strong>{{ number_format($item->QTY * $item->Price, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PO Summary -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Approval Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-check-circle"></i>
                                Approval Status
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($approvalStatus->count() > 0)
                                <div class="timeline">
                                    @foreach($approvalStatus as $approval)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-{{ $approval->approval_status === 'approved' ? 'success' : ($approval->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                            <i class="fas fa-{{ $approval->approval_status === 'approved' ? 'check' : ($approval->approval_status === 'rejected' ? 'times' : 'clock') }}"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">
                                                Level {{ $approval->approval_level }}: {{ ucfirst($approval->approver_role) }}
                                                <span class="badge bg-{{ $approval->approval_status === 'approved' ? 'success' : ($approval->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($approval->approval_status) }}
                                                </span>
                                            </h6>
                                            <p class="mb-1"><strong>{{ $approval->approver_name }}</strong></p>
                                            @if($approval->approval_date)
                                                <small class="text-muted">{{ date('d/m/Y H:i', strtotime($approval->approval_date)) }}</small>
                                            @endif
                                            @if($approval->approval_note)
                                                <p class="mt-2 mb-1"><em>"{{ $approval->approval_note }}"</em></p>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No approval history yet. This PO is pending initial approval.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Amount Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator"></i>
                                Amount Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>Subtotal:</td>
                                    <td class="text-end">{{ number_format($po->header->TotalAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Discount:</td>
                                    <td class="text-end">{{ number_format($po->header->DiscountAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>VAT:</td>
                                    <td class="text-end">{{ number_format($po->header->VatAmount, 2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Net Total:</strong></td>
                                    <td class="text-end"><strong>{{ number_format($po->header->NetAmount, 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cog"></i>
                                Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($canApprove['can_approve'])
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>You can approve this PO</strong><br>
                                    <small>Approval Level: {{ $canApprove['next_level'] }}</small>
                                </div>
                                
                                <form action="{{ route('po.approve', $po->header->DocNo) }}" method="POST" id="approvalForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="approval_note" class="form-label">Approval Note (Optional)</label>
                                        <textarea class="form-control" id="approval_note" name="approval_note" rows="3" placeholder="Add your comment here..."></textarea>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="action" value="approve" class="btn btn-success">
                                            <i class="fas fa-check"></i> Approve PO
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Reject PO
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Cannot Approve</strong><br>
                                    <small>{{ $canApprove['reason'] }}</small>
                                </div>
                            @endif

                            <hr>

                            <div class="d-grid gap-2">
                                @if(Auth::user()->approval_level >= 1)
                                    <a href="#" class="btn btn-outline-primary">
                                        <i class="fas fa-print"></i> Print PO
                                    </a>
                                @endif
                                <a href="{{ route('po.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Timeline -->
<style>
.timeline {
    position: relative;
    padding: 0;
    margin: 0;
}

.timeline-item {
    position: relative;
    padding-left: 60px;
    margin-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 19px;
    top: 40px;
    height: calc(100% + 10px);
    width: 2px;
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.timeline-content {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalForm = document.getElementById('approvalForm');
    if (approvalForm) {
        approvalForm.addEventListener('submit', function(e) {
            const action = e.submitter.value;
            const message = action === 'approve' 
                ? 'Are you sure you want to APPROVE this Purchase Order?' 
                : 'Are you sure you want to REJECT this Purchase Order?';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endsection
```

---

## 2️⃣ เพิ่ม Approval Methods ใน Controller

### 2.1 แก้ไข PurchaseOrderController.php เพิ่ม Approval Methods:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PurchaseOrderService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected $poService;
    protected $notificationService;
    
    public function __construct(PurchaseOrderService $poService, NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->poService = $poService;
        $this->notificationService = $notificationService;
    }
    
    // ... existing methods ...
    
    /**
     * แสดงรายละเอียด PO (แก้ไขให้ใช้ view ใหม่)
     */
    public function show($docNo)
    {
        try {
            $po = $this->poService->getPurchaseOrderByDocNo($docNo);
            
            if (!$po) {
                return back()->withErrors(['error' => 'Purchase Order not found']);
            }
            
            // ดึงสถานะการ Approve
            $approvalStatus = $this->poService->getApprovalStatus($docNo);
            
            // ตรวจสอบว่า User ปัจจุบันสามารถ Approve ได้หรือไม่
            $canApprove = $this->poService->canApprove($docNo, Auth::id());
            
            return view('po.show', compact('po', 'approvalStatus', 'canApprove'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error loading purchase order: ' . $e->getMessage()]);
        }
    }
    
    /**
     * ประมวลผล Approval (Approve/Reject)
     */
    public function approve(Request $request, $docNo)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_note' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::connection('modern')->beginTransaction();
            
            // ตรวจสอบว่าสามารถ Approve ได้หรือไม่
            $canApprove = $this->poService->canApprove($docNo, Auth::id());
            
            if (!$canApprove['can_approve']) {
                return back()->withErrors(['error' => $canApprove['reason']]);
            }
            
            // ดึงข้อมูล PO
            $po = $this->poService->getPurchaseOrderByDocNo($docNo);
            if (!$po) {
                return back()->withErrors(['error' => 'Purchase Order not found']);
            }
            
            $user = Auth::user();
            $action = $request->input('action');
            $note = $request->input('approval_note');
            
            // บันทึก Approval Record
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
            
            // ส่ง Notification
            if ($action === 'approve') {
                $this->notificationService->sendApprovalNotification($docNo, $user, $canApprove['next_level']);
                $message = 'Purchase Order approved successfully!';
            } else {
                $this->notificationService->sendRejectionNotification($docNo, $user, $note);
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
```

---

## 3️⃣ สร้าง Notification Service

### 3.1 สร้าง NotificationService:

```bash
php artisan make:class Services/NotificationService
```

สร้างไฟล์ `app/Services/NotificationService.php`:

```php
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
        $nextUsers = [];
        
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
```

---

## 4️⃣ เพิ่ม Routes สำหรับ Approval

### 4.1 แก้ไขไฟล์ routes/web.php:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseOrderController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Auth::routes(['register' => false]);

Route::get('/home', [DashboardController::class, 'index'])->name('home');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/po', [PurchaseOrderController::class, 'index'])->name('po.index');
    Route::get('/po/{docNo}', [PurchaseOrderController::class, 'show'])->name('po.show');
    Route::post('/po/{docNo}/approve', [PurchaseOrderController::class, 'approve'])->name('po.approve');
});
```

---

## 5️⃣ ขั้นตอนการทดสอบ Phase 2.2

### 5.1 Clear Cache:
```bash
php artisan config:clear
php artisan view:clear
```

### 5.2 ทดสอบระบบ:

1. **เข้าหน้า PO List**: `http://localhost:8000/po`
2. **คลิกปุ่ม Eye Icon**: เพื่อดู PO Detail
3. **ทดสอบการ Approve**:
   - Login ด้วย `user001` / `user123`
   - เข้าดู PO Detail
   - ใส่ Note และกด Approve
   - Login ด้วย `manager001` / `manager123`
   - เข้าดู PO เดิม และ Approve ต่อ
   - Login ด้วย `gm001` / `gm123`
   - Approve ขั้นสุดท้าย

### 5.3 ตรวจสอบผลลัพธ์:

- ✅ หน้า PO Detail แสดงข้อมูลครบถ้วน
- ✅ Approval Timeline แสดงประวัติการ Approve
- ✅ ปุ่ม Approve/Reject ทำงานได้
- ✅ Notification บันทึกใน Database
- ✅ ไม่สามารถ Approve ข้ามขั้นได้

---

## ✅ ผลลัพธ์ที่ควรได้รับ

หาก Phase 2.2 สำเร็จ คุณจะได้:

1. ✅ หน้า PO Detail แบบเต็ม พร้อมรายการสินค้า
2. ✅ ระบบ Approval แบบลำดับที่ทำงานจริง
3. ✅ Approval History & Timeline
4. ✅ Notification System พื้นฐาน
5. ✅ ป้องกันการ Approve ข้ามขั้น

---

## 📋 Checklist สำหรับ Phase 2.2

- [ ] สร้าง PO Detail View ใหม่ (show.blade.php)
- [ ] แก้ไข PurchaseOrderController เพิ่ม approve method
- [ ] สร้าง NotificationService
- [ ] เพิ่ม Route สำหรับ approval
- [ ] ทดสอบ PO Detail แสดงข้อมูลครบถ้วน
- [ ] ทดสอบ Approval Workflow ทำงานตามลำดับ
- [ ] ทดสอบ Notification บันทึกใน Database
- [ ] ทดสอบป้องกันการ Approve ข้ามขั้น

**กรุณาทำตามขั้นตอนทีละส่วนแล้วแจ้งผลลัพธ์กลับมาครับ!**