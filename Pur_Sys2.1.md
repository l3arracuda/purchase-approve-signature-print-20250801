# Phase 2: PO Management System

## 🎯 สิ่งที่เราจะทำในขั้นตอนนี้
1. สร้าง PO Service สำหรับดึงข้อมูลจาก Legacy Database
2. สร้าง PO Controller สำหรับจัดการ PO
3. สร้างหน้า PO List พร้อม Filter และ Search
4. สร้างหน้า PO Detail
5. เริ่มต้น Approval System แบบลำดับ

---

## 1️⃣ สร้าง PO Service Class

### 1.1 สร้าง PO Service:
```bash
php artisan make:class Services/PurchaseOrderService
```

สร้างไฟล์ `app/Services/PurchaseOrderService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PurchaseOrderService
{
    /**
     * ดึงรายการ PO จาก Legacy Database
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
     * ดึงข้อมูล PO โดย DocNo
     */
    public function getPurchaseOrderByDocNo($docNo)
    {
        $query = $this->buildDetailQuery() . " AND h.DOCNO = ?";
        
        $result = DB::connection('legacy')->select($query, [$docNo]);
        
        if (empty($result)) {
            return null;
        }
        
        // Group by PO Header และ Details
        return $this->groupPOData($result);
    }
    
    /**
     * ตรวจสอบว่า PO สามารถ Approve ได้หรือไม่
     */
    public function canApprove($docNo, $userId)
    {
        // ตรวจสอบว่า DocNo ขึ้นต้นด้วย PP
        if (!str_starts_with($docNo, 'PP')) {
            return [
                'can_approve' => false,
                'reason' => 'Only PO with DocNo starting with "PP" can be approved'
            ];
        }
        
        // ตรวจสอบ Approval Workflow
        $currentApprovals = DB::connection('modern')
            ->table('po_approvals')
            ->where('po_docno', $docNo)
            ->orderBy('approval_level')
            ->get();
            
        $user = DB::connection('modern')
            ->table('users')
            ->where('id', $userId)
            ->first();
            
        if (!$user) {
            return [
                'can_approve' => false,
                'reason' => 'User not found'
            ];
        }
        
        // ตรวจสอบลำดับการ Approve
        $nextLevel = $this->getNextApprovalLevel($currentApprovals);
        
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
        
        // ตรวจสอบว่าเคย Approve แล้วหรือไม่
        $existingApproval = $currentApprovals->where('approver_id', $userId)->first();
        if ($existingApproval) {
            return [
                'can_approve' => false,
                'reason' => 'You have already processed this PO'
            ];
        }
        
        return [
            'can_approve' => true,
            'next_level' => $nextLevel
        ];
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
     * สร้าง Base Query สำหรับรายการ PO
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
     * สร้าง Detail Query สำหรับ PO รายละเอียด
     */
    private function buildDetailQuery()
    {
        return "
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
                d.PDTCD as ProductNo, 
                i.pdtnam as ProductName, 
                d.QTY as QTY, 
                d.UNIT as Unit, 
                d.PRICE as Price, 
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
            JOIN [Romar1].[dbo].[POC_POD] d ON h.DOCNO = d.DOCNO
            JOIN [Romar1].[dbo].[INV_PDT] i ON d.PDTCD = i.PDTCD
            WHERE i.PDTTYP = '1' AND h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
        ";
    }
    
    /**
     * จัดกลุ่มข้อมูล PO Header และ Details
     */
    private function groupPOData($data)
    {
        if (empty($data)) return null;
        
        $header = (object)[
            'DateNo' => $data[0]->DateNo,
            'DocNo' => $data[0]->DocNo,
            'DocRef' => $data[0]->DocRef,
            'SupNo' => $data[0]->SupNo,
            'SupName' => $data[0]->SupName,
            'CreditTerm' => $data[0]->CreditTerm,
            'AddressSup' => $data[0]->AddressSup,
            'Province' => $data[0]->Province,
            'ContractSup' => $data[0]->ContractSup,
            'Phone' => $data[0]->Phone,
            'FAX' => $data[0]->FAX,
            'ZipCode' => $data[0]->ZipCode,
            'ContactName' => $data[0]->ContactName,
            'TotalAmount' => $data[0]->TotalAmount,
            'DiscountPrice' => $data[0]->DiscountPrice,
            'DiscountAmount' => $data[0]->DiscountAmount,
            'VatAmount' => $data[0]->VatAmount,
            'NetAmount' => $data[0]->NetAmount,
            'Remember' => $data[0]->Remember,
            'Note' => $data[0]->Note,
            'AppStatus' => $data[0]->AppStatus,
        ];
        
        $details = collect($data)->map(function($item) {
            return (object)[
                'ProductNo' => $item->ProductNo,
                'ProductName' => $item->ProductName,
                'QTY' => $item->QTY,
                'Unit' => $item->Unit,
                'Price' => $item->Price,
            ];
        });
        
        return (object)[
            'header' => $header,
            'details' => $details,
        ];
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
}
```

---

## 2️⃣ สร้าง PO Controller

### 2.1 สร้าง PurchaseOrderController:
```bash
php artisan make:controller PurchaseOrderController
```

แก้ไขไฟล์ `app/Http/Controllers/PurchaseOrderController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PurchaseOrderService;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    protected $poService;
    
    public function __construct(PurchaseOrderService $poService)
    {
        $this->middleware('auth');
        $this->poService = $poService;
    }
    
    /**
     * แสดงรายการ PO
     */
    public function index(Request $request)
    {
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
        
        try {
            $purchaseOrders = $this->poService->getPurchaseOrders($filters);
            
            return view('po.index', compact('purchaseOrders', 'filters'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error loading purchase orders: ' . $e->getMessage()]);
        }
    }
    
    /**
     * แสดงรายละเอียด PO
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
}
```

---

## 3️⃣ สร้างหน้า PO List

### 3.1 สร้างไฟล์ View:

สร้างไฟล์ `resources/views/po/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-file-invoice"></i>
                        Purchase Orders Management
                    </h4>
                    <small class="text-muted">Manage and approve purchase orders (PP% only)</small>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Search and Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>Search & Filter</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('po.index') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="docno" class="form-label">PO Number</label>
                                        <input type="text" class="form-control" id="docno" name="docno" 
                                               value="{{ $filters['docno'] ?? '' }}" placeholder="PP...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="supplier" class="form-label">Supplier</label>
                                        <input type="text" class="form-control" id="supplier" name="supplier" 
                                               value="{{ $filters['supplier'] ?? '' }}" placeholder="Supplier name">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               value="{{ $filters['date_from'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               value="{{ $filters['date_to'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-2">
                                        <label for="amount_from" class="form-label">Amount From</label>
                                        <input type="number" class="form-control" id="amount_from" name="amount_from" 
                                               value="{{ $filters['amount_from'] ?? '' }}" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="amount_to" class="form-label">Amount To</label>
                                        <input type="number" class="form-control" id="amount_to" name="amount_to" 
                                               value="{{ $filters['amount_to'] ?? '' }}" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('po.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-undo"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PO List Table -->
                    <div class="table-responsive">
                        @if(count($purchaseOrders) > 0)
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>PO Number</th>
                                        <th>Supplier</th>
                                        <th>Net Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseOrders as $po)
                                    <tr>
                                        <td>{{ date('d/m/Y', strtotime($po->DateNo)) }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $po->DocNo }}</strong>
                                            @if($po->Note)
                                                <br><small class="text-muted">{{ Str::limit($po->Note, 30) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $po->SupName }}</strong>
                                            <br><small class="text-muted">{{ $po->SupNo }}</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($po->NetAmount, 2) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $po->AppStatus }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('po.show', $po->DocNo) }}" 
                                                   class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if(Auth::user()->approval_level >= 1)
                                                    <a href="{{ route('po.show', $po->DocNo) }}" 
                                                       class="btn btn-outline-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>No Purchase Orders Found</h5>
                                <p>No purchase orders match your search criteria, or there might be a database connection issue.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Summary Stats -->
                    @if(count($purchaseOrders) > 0)
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <h5>{{ count($purchaseOrders) }}</h5>
                                                <small class="text-muted">POs Found</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h5>{{ number_format(collect($purchaseOrders)->sum('NetAmount'), 2) }}</h5>
                                                <small class="text-muted">Total Amount</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h5>{{ number_format(collect($purchaseOrders)->avg('NetAmount'), 2) }}</h5>
                                                <small class="text-muted">Average Amount</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h5>{{ ucfirst(Auth::user()->role) }}</h5>
                                                <small class="text-muted">Your Role</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when date fields change
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            // You can add auto-submit logic here if needed
        });
    });
});
</script>
@endsection
```

---

## 4️⃣ เพิ่ม Routes

### 4.1 แก้ไขไฟล์ routes/web.php:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseOrderController;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['register' => false]); // ปิดการ register

Route::get('/home', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// PO Management Routes
Route::middleware('auth')->group(function () {
    Route::get('/po', [PurchaseOrderController::class, 'index'])->name('po.index');
    Route::get('/po/{docNo}', [PurchaseOrderController::class, 'show'])->name('po.show');
});

// Test routes (เฉพาะในการพัฒนา)
// require __DIR__.'/test.php';
```

---

## 5️⃣ เพิ่ม Navigation ใน Layout

### 5.1 แก้ไขไฟล์ resources/views/layouts/app.blade.php:

หาส่วน Navigation และเพิ่ม Menu ใหม่:

```blade
<!-- ในส่วน <ul class="navbar-nav ms-auto"> -->
@auth
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('po.index') }}">
            <i class="fas fa-file-invoice"></i> Purchase Orders
        </a>
    </li>
@endauth
```

---

## 6️⃣ ขั้นตอนการทดสอบ

### 6.1 สร้าง Service Directory:
```bash
mkdir -p app/Services
```

### 6.2 Clear Cache:
```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### 6.3 ทดสอบการทำงาน:
```bash
php artisan serve
```

### 6.4 ทดสอบหน้าต่างๆ:

1. **Dashboard**: `http://localhost:8000/dashboard`
2. **PO List**: `http://localhost:8000/po`
3. **ทดสอบ Filter**: ใส่ค่าใน Search Form
4. **ทดสอบ PO Detail**: คลิกที่ปุ่ม Eye Icon

---

## ✅ ผลลัพธ์ที่ควรได้รับ

หาก Phase 2.1 สำเร็จ คุณจะได้:

1. ✅ PO Service ที่สามารถดึงข้อมูลจาก Legacy Database
2. ✅ หน้า PO List พร้อม Search & Filter
3. ✅ Navigation Menu ที่เชื่อมต่อหน้าต่างๆ
4. ✅ ระบบตรวจสอบ Approval Level เบื้องต้น
5. ✅ UI ที่สวยงามและใช้งานได้

---

## 📋 Checklist สำหรับ Phase 2.1

- [ ] สร้าง PurchaseOrderService สำเร็จ
- [ ] สร้าง PurchaseOrderController สำเร็จ
- [ ] สร้างหน้า PO List (index.blade.php) สำเร็จ
- [ ] เพิ่ม Routes สำเร็จ
- [ ] เพิ่ม Navigation Menu สำเร็จ
- [ ] หน้า PO List แสดงข้อมูลได้
- [ ] Search & Filter ทำงานได้
- [ ] Summary Stats แสดงผลถูกต้อง

**กรุณาทำให้เรียบร้อยแล้วแจ้งผลลัพธ์กลับมา จากนั้นเราจะไป Phase 2.2: PO Detail & Approval System ต่อครับ!**