@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Purchase System Dashboard') }}</h4>
                    <small class="text-muted">Welcome, {{ $user->full_name }} ({{ ucfirst($user->role) }})</small>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- User Info & Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>User Role</h5>
                                    <h2>{{ ucfirst($user->role) }}</h2>
                                    <small>Approval Level: {{ $user->approval_level }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Available POs</h5>
                                    <h2>{{ $stats['total_pos'] }}</h2>
                                    <small>Ready for Process</small>
                                </div>
                            </div>
                        </div>
                        
                        {{-- ========== NEW: PO Approved Stats (สำหรับ Manager ขึ้นไป) ========== --}}
                        @if(($user->isManager() || $user->isGM() || $user->isAdmin() || $user->isUser()) && $approvedStats)
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5>Approved POs</h5>
                                        <h2>{{ number_format($approvedStats->total_pos ?? 0) }}</h2>
                                        <small>Total Approved</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white card-hover" 
                                    style="cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease;"
                                    onclick="window.location.href='{{ route('po.approved') }}?approval_level={{ $pendingApprovals['next_level']-1 }}'">
                                    <div class="card-body" style="text-align: center;">
                                        <h5>Pending Level {{ $user->approval_level }}</h5>
                                        @if(($user->isGM()))
                                        <h2>{{ $approvedStats->manager_approved ?? 0 }}</h2>
                                        @elseif(($user->isManager()))
                                        <h2>{{ $approvedStats->user_approved ?? 0 }}</h2>
                                        @endif
                                        <small>Awaiting Your Approval</small>
                                    </div>                                    
                                </div>
                            </div>
                        @else
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5>Connection Status</h5>
                                        <h2>✓ Online</h2>
                                        <small>Both Databases</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5>System</h5>
                                        <h2>Ready</h2>
                                        <small>Version 2.0</small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ========== NEW: Approved POs Summary (Manager ขึ้นไป) ========== --}}
                    @if(($user->isManager() || $user->isGM() || $user->isAdmin() || $user->isUser()) && $approvedStats)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-check-circle"></i> Approved POs Summary                                            
                                            <a href="{{ route('po.approved') }}" class="btn btn-light btn-sm float-end">
                                                <i class="fas fa-external-link-alt"></i> View All
                                            </a>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-2">
                                                <h5 class="text-success">{{ number_format($approvedStats->total_pos ?? 0) }}</h5>
                                                <small class="text-muted">Total Approved</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="text-primary">{{ number_format($approvedStats->total_amount ?? 0, 2) }}</h5>
                                                <small class="text-muted">Total Amount</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="text-info">{{ number_format($approvedStats->avg_amount ?? 0, 2) }}</h5>
                                                <small class="text-muted">Average Amount</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="text-warning">{{ number_format($approvedStats->total_items ?? 0) }}</h5>
                                                <small class="text-muted">Total Items</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="text-secondary">{{ number_format($approvedStats->unique_customers ?? 0) }}</h5>
                                                <small class="text-muted">Customers</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="text-success">{{ number_format($approvedStats->fully_approved ?? 0) }}</h5>
                                                <small class="text-muted">Fully Approved</small>
                                            </div>
                                        </div>
                                        
                                        {{-- Approval Level Breakdown --}}
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div class="progress-bar bg-info" style="width: {{ $approvedStats->total_pos > 0 ? ($approvedStats->user_approved / $approvedStats->total_pos) * 100 : 0 }}%">
                                                        {{ $approvedStats->user_approved ?? 0 }}
                                                    </div>
                                                </div>
                                                <small class="text-muted">Level 1 (User)</small>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ $approvedStats->total_pos > 0 ? ($approvedStats->manager_approved / $approvedStats->total_pos) * 100 : 0 }}%">
                                                        {{ $approvedStats->manager_approved ?? 0 }}
                                                    </div>
                                                </div>
                                                <small class="text-muted">Level 2 (Manager)</small>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $approvedStats->total_pos > 0 ? ($approvedStats->fully_approved / $approvedStats->total_pos) * 100 : 0 }}%">
                                                        {{ $approvedStats->fully_approved ?? 0 }}
                                                    </div>
                                                </div>
                                                <small class="text-muted">Level 3 (GM)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ========== NEW: Pending Approvals (สำหรับ Manager ขึ้นไป) ========== --}}
                    @if(($user->isManager() || $user->isGM() || $user->isAdmin() || $user->isUser()) && $pendingApprovals && $pendingApprovals['count'] > 0)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">
                                            <i class="fas fa-clock"></i> POs Pending Your Approval (Level {{ $pendingApprovals['next_level'] }})
                                            <a href="{{ route('po.approved') }}?approval_level={{ $pendingApprovals['next_level']-1 }}" class="btn btn-light btn-sm float-end">
                                                <i class="fas fa-clock"></i> Pending Approvals
                                            </a>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>PO Number</th>
                                                        <th>Customer</th>
                                                        <th style="text-align: center;">Amount</th>
                                                        <th>Current Level</th>
                                                        <th>Days Waiting</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pendingApprovals['pos'] as $pendingPO)
                                                        <tr>
                                                            <td><strong>{{ $pendingPO->po_docno }}</strong></td>
                                                            <td>{{ $pendingPO->customer_name ?? 'N/A' }}</td>
                                                            <td class="text-end">{{ number_format($pendingPO->po_amount, 2) }}</td>
                                                            <td>
                                                                <span class="badge bg-info">Level {{ $pendingPO->current_level }}</span>
                                                            </td>
                                                            <td>
                                                                @if($pendingPO->days_waiting > 7)
                                                                    <span class="badge bg-danger">{{ $pendingPO->days_waiting }} days</span>
                                                                @elseif($pendingPO->days_waiting > 3)
                                                                    <span class="badge bg-warning">{{ $pendingPO->days_waiting }} days</span>
                                                                @else
                                                                    <span class="badge bg-success">{{ $pendingPO->days_waiting }} days</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('po.show', $pendingPO->po_docno) }}" class="btn btn-primary btn-sm">
                                                                    <i class="fas fa-eye"></i> Review
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Recent POs Preview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Recent Purchase Orders
                            <a href="{{ route('po.approved') }}" class="btn btn-outline-success" style="float: right;">
                                <i class="fas fa-check-circle"></i> Approved POs
                            </a>
                            <a href="{{ route('po.index') }}" class="btn btn-outline-primary" style="float: right;">
                                    <i class="fas fa-list"></i> View All POs
                                </a>
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(count($recentPOs) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>PO Number</th>
                                                <th>Supplier</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentPOs as $po)
                                            <tr>
                                                <td>{{ date('d/m/Y', strtotime($po->DateNo)) }}</td>
                                                <td><strong>{{ $po->DocNo }}</strong></td>
                                                <td>{{ $po->SupName }}</td>
                                                <td class="text-end">{{ number_format($po->NetAmout, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $po->AppStatus }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('po.show', $po->DocNo) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    No Purchase Orders found or database connection issue.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ========== NEW: Recent Approval Activities (สำหรับ Manager ขึ้นไป) ========== --}}
                    @if(($user->approval_level >= 1) && $recentActivities && count($recentActivities) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-history"></i> Recent Approval Activities (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    @foreach($recentActivities as $activity)
                                        <div class="timeline-item">
                                            <div class="timeline-marker">
                                                <span class="badge {{ $activity->level_class }}">L{{ $activity->approval_level }}</span>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <strong>{{ $activity->po_docno }}</strong> - {{ $activity->customer_name ?? 'N/A' }}
                                                        </h6>
                                                        <p class="mb-1">
                                                            <span class="badge {{ $activity->level_class }}">{{ $activity->level_name }}</span>
                                                            approved by <strong>{{ $activity->approver_name }}</strong>
                                                        </p>
                                                        <small class="text-muted">
                                                            Amount: {{ number_format($activity->po_amount, 2) }}
                                                            @if($activity->approval_note)
                                                                <br>Note: "{{ Str::limit($activity->approval_note, 50) }}"
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">
                                                            {{ $activity->formatted_date }}<br>
                                                            {{ $activity->human_date }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h5>Quick Actions</h5>
                        <div class="btn-group" role="group">
                            @if($user->approval_level >= 1)
                                <a href="{{ route('po.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> View All POs
                                </a>
                            @endif
                            @if($user->approval_level >= 1)
                                <a href="{{ route('po.approved') }}" class="btn btn-outline-success">
                                    <i class="fas fa-check-circle"></i> Approved POs
                                </a>
                            @endif
                            @if($pendingApprovals && $pendingApprovals['count'] > 0)
                                <a href="{{ route('po.approved') }}?approval_level=1" class="btn btn-outline-warning">
                                    <i class="fas fa-clock"></i> Pending Approvals ({{ $pendingApprovals['count'] }})
                                </a>
                            @endif
                            @if($user->isAdmin())
                                <button type="button" class="btn btn-outline-info" id="updateCustomerDataBtn">
                                    <i class="fas fa-sync"></i> Update Customer Data
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ========== NEW: Data Status Indicator (Admin only) ========== --}}
                    @if($user->isAdmin())
                        <div class="mt-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-database"></i> Data Status เช็คข้อมูลชื่อลูกค้าและรายการสินค้า
                                        <button type="button" class="btn btn-light btn-sm float-end" id="refreshDataStatusBtn">
                                            <i class="fas fa-refresh"></i> Refresh
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body" id="dataStatusContent">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span class="ms-2">Checking data status...</span>
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

{{-- ========== NEW: JavaScript for Dashboard Features ========== --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($user->isAdmin())
        // ตรวจสอบสถานะข้อมูลทันทีที่โหลดหน้า
        checkDataStatus();
        
        // ปุ่ม Update Customer Data
        document.getElementById('updateCustomerDataBtn').addEventListener('click', function() {
            if (confirm('This will update missing customer data for approved POs. Continue?')) {
                updateCustomerData();
            }
        });
        
        // ปุ่ม Refresh Data Status
        document.getElementById('refreshDataStatusBtn').addEventListener('click', function() {
            checkDataStatus();
        });
    @endif
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        @if($user->isAdmin())
            checkDataStatus();
        @endif
        // อาจจะเพิ่ม refresh สถิติอื่นๆ ได้ที่นี่
    }, 300000); // 5 minutes
});

@if($user->isAdmin())
// ตรวจสอบสถานะข้อมูล
function checkDataStatus() {
    const content = document.getElementById('dataStatusContent');
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Checking data status...</span>
        </div>
    `;
    
    fetch('/api/po-approved/data-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                const percentage = stats.completion_percentage;
                const progressClass = percentage >= 95 ? 'bg-success' : percentage >= 80 ? 'bg-warning' : 'bg-danger';
                
                content.innerHTML = `
                    <div class="row text-center mb-3">
                        <div class="col-md-3">
                            <h6 class="text-primary">${stats.total_records.toLocaleString()}</h6>
                            <small class="text-muted">Total Records</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">${stats.complete_records.toLocaleString()}</h6>
                            <small class="text-muted">Complete</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-warning">${stats.missing_customer.toLocaleString()}</h6>
                            <small class="text-muted">Missing Customer</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-danger">${stats.missing_items.toLocaleString()}</h6>
                            <small class="text-muted">Missing Items</small>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar ${progressClass}" style="width: ${percentage}%">
                            ${percentage.toFixed(1)}%
                        </div>
                    </div>
                    <small class="text-muted">Data Completion Rate</small>
                    ${stats.needs_update ? '<br><span class="badge bg-warning mt-2">Needs Update</span>' : '<br><span class="badge bg-success mt-2">All Data Complete</span>'}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Error: ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error checking data status:', error);
            content.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Network error occurred
                </div>
            `;
        });
}

// Update Customer Data
function updateCustomerData() {
    const btn = document.getElementById('updateCustomerDataBtn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;
    
    fetch('/api/po-approved/update-customer-data', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ limit: 100 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Update completed!\nProcessed: ${data.processed}\nUpdated: ${data.updated}\nErrors: ${data.errors}`);
            checkDataStatus(); // Refresh status
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error updating customer data:', error);
        alert('Network error occurred');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
@endif
</script>

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
    background: white;
    border: 2px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.progress {
    border-radius: 10px;
}

.card-header h6 .float-end {
    font-size: 0.875rem;
}

.card-hover {
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
    .timeline-item {
        padding-left: 40px;
    }
    
    .timeline-marker {
        width: 30px;
        height: 30px;
    }
}
</style>
@endsection