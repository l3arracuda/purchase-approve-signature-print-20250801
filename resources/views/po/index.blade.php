{{-- resources/views/po/index.blade.php (แก้ไขแล้ว) --}}

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

                    <!-- Debug Info -->
                    @if(isset($connectionStatus))
                        <div class="alert alert-info">
                            <strong>Debug Info:</strong> {{ $connectionStatus }}<br>
                            <strong>Applied Filters:</strong> 
                            @if(!empty(array_filter($filters)))
                                {{ implode(', ', array_keys(array_filter($filters))) }}
                            @else
                                None
                            @endif
                            @if(isset($pagination))
                                <br><strong>Pagination:</strong> 
                                Page {{ $pagination->current_page }} of {{ $pagination->total_pages }} 
                                ({{ $pagination->total }} total records)
                            @endif
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

                    {{-- ========== NEW: Bulk Approval Panel ========== --}}
                    <div class="bulk-actions mb-3" id="bulkActionPanel" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-double"></i> Bulk Approval Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                @if(Auth::user()->hasActiveSignature())
                                    <form id="bulkApprovalForm" action="{{ route('po.bulk-approve') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="po_numbers" id="selectedPONumbers">
                                        
                                        <div class="row align-items-end">
                                            <div class="col-md-6">
                                                <label for="bulk_note" class="form-label">Bulk Approval Note</label>
                                                <textarea class="form-control" name="bulk_note" id="bulk_note" rows="2" placeholder="Note for all selected POs..."></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="btn-group w-100">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success">
                                                        <i class="fas fa-check"></i> Bulk Approve (<span id="selectedCount">0</span>)
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                        <i class="fas fa-times"></i> Bulk Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Digital Signature Required:</strong> 
                                        You need to <a href="{{ route('signature.manage') }}" class="alert-link">upload a digital signature</a> before you can approve POs.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- PO List Table -->
                    <div class="table-responsive">
                        @if(count($purchaseOrders) > 0)
                            <div class="alert alert-success">
                                <small>
                                    <strong>Data Source:</strong> {{ count($purchaseOrders) }} records on this page
                                    @if(isset($pagination))
                                        | <strong>Total:</strong> {{ number_format($pagination->total) }} records
                                        | <strong>Page:</strong> {{ $pagination->current_page }}/{{ $pagination->total_pages }}
                                    @endif
                                </small>
                            </div>
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        {{-- ========== NEW: Checkbox Column ========== --}}
                                        @if(Auth::user()->approval_level >= 1)
                                        <th width="3%">
                                            <input type="checkbox" id="selectAll" class="form-check-input" title="Select All">
                                        </th>
                                        @endif
                                        <th>Date</th>
                                        <th>PO Number</th>
                                        <th>Supplier</th>
                                        <th style="text-align: center;">Net Amount</th>
                                        {{-- <th>Legacy Status</th> --}}
                                        {{-- ========== FUTURE: Approval Status Column ========== --}}
                                        {{-- <th>Approval Status</th> --}}
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseOrders as $po)
                                    <tr>
                                        {{-- ========== NEW: Checkbox ========== --}}
                                        @if(Auth::user()->approval_level >= 1)
                                        <td>
                                            <input type="checkbox" name="selected_pos[]" value="{{ $po->DocNo }}" class="form-check-input po-checkbox" title="Select this PO">
                                        </td>
                                        @endif
                                        <td>{{ date('d/m/Y', strtotime($po->DateNo)) }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $po->DocNo }}</strong>
                                            @if(isset($po->Note) && $po->Note)
                                                <br><small class="text-muted">{{ Str::limit($po->Note, 30) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $po->SupName }}</strong>
                                            @if(isset($po->SupNo))
                                                <br><small class="text-muted">{{ $po->SupNo }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($po->NetAmount, 2) }}</strong>
                                        </td>
                                        {{-- <td>
                                            <span class="badge bg-secondary">{{ $po->AppStatus ?? 'P' }}</span>
                                        </td> --}}
                                        {{-- ========== FUTURE: Dynamic Approval Status ========== --}}
                                        {{-- 
                                        <td>
                                            @if(isset($po->approval_status))
                                                <span class="badge bg-{{ $po->approval_status === 'Fully Approved' ? 'success' : 
                                                    ($po->approval_status === 'Rejected' ? 'danger' : 'warning') }}">
                                                    {{ $po->approval_status }}
                                                </span>
                                                @if(isset($po->approval_progress))
                                                    <div class="progress mt-1" style="height: 4px;">
                                                        <div class="progress-bar bg-success" style="width: {{ ($po->approval_progress['level1'] * 33.33) + ($po->approval_progress['level2'] * 33.33) + ($po->approval_progress['level3'] * 33.33) }}%"></div>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        --}}
                                        <td style="text-align: center;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('po.show', $po->DocNo) }}" 
                                                   class="btn btn-outline-primary btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                @if(Auth::user()->approval_level >= 1)
                                                    <a href="{{ route('po.print', $po->DocNo) }}" 
                                                       target="_blank"
                                                       class="btn btn-outline-success btn-sm" 
                                                       title="Print PO">
                                                        <i class="fas fa-print"></i> Print
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            @if(isset($pagination) && $pagination->total_pages > 1)
                                <nav aria-label="PO Pagination">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous -->
                                        @if($pagination->has_previous)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">First</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination->previous_page]) }}">Previous</a>
                                            </li>
                                        @else
                                            <li class="page-item disabled">
                                                <span class="page-link">First</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">Previous</span>
                                            </li>
                                        @endif

                                        <!-- Page Numbers -->
                                        @for($i = max(1, $pagination->current_page - 2); $i <= min($pagination->total_pages, $pagination->current_page + 2); $i++)
                                            @if($i == $pagination->current_page)
                                                <li class="page-item active">
                                                    <span class="page-link">{{ $i }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                                </li>
                                            @endif
                                        @endfor

                                        <!-- Next -->
                                        @if($pagination->has_more)
                                            <li class="page-item">
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination->next_page]) }}">Next</a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination->total_pages]) }}">Last</a>
                                            </li>
                                        @else
                                            <li class="page-item disabled">
                                                <span class="page-link">Next</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">Last</span>
                                            </li>
                                        @endif
                                    </ul>
                                </nav>
                            @endif
                        @else
                            <div class="alert alert-warning text-center">
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
                                    <div class="card-header">
                                        <strong>Summary Statistics</strong>
                                        @if(isset($pagination))
                                            <small class="text-muted float-end">
                                                Showing {{ count($purchaseOrders) }} of {{ number_format($pagination->total) }} records
                                            </small>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <h5 class="text-primary">{{ count($purchaseOrders) }}</h5>
                                                <small class="text-muted">POs on This Page</small>
                                                @if(isset($pagination))
                                                    <br><small class="text-info">{{ number_format($pagination->total) }} Total</small>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                <h5 class="text-success">{{ number_format(collect($purchaseOrders)->sum('NetAmount'), 2) }}</h5>
                                                <small class="text-muted">Page Amount</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h5 class="text-info">{{ number_format(collect($purchaseOrders)->avg('NetAmount'), 2) }}</h5>
                                                <small class="text-muted">Page Average</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h5 class="text-warning">{{ ucfirst(Auth::user()->role) }}</h5>
                                                <small class="text-muted">Your Role</small>
                                                @if(isset($pagination))
                                                    <br><small class="text-info">{{ $pagination->per_page }}/page</small>
                                                @endif
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

{{-- ========== JavaScript for Bulk Operations ========== --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const poCheckboxes = document.querySelectorAll('.po-checkbox');
    const bulkActionPanel = document.getElementById('bulkActionPanel');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkApprovalForm = document.getElementById('bulkApprovalForm');

    // Select All functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            poCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionPanel();
        });
    }

    // Individual checkbox functionality
    poCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionPanel();
            
            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.po-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === poCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < poCheckboxes.length;
            }
        });
    });

    function updateBulkActionPanel() {
        const checkedBoxes = document.querySelectorAll('.po-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (bulkActionPanel) {
            bulkActionPanel.style.display = count > 0 ? 'block' : 'none';
        }
        
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
    }

    // Bulk approval form submission
    if (bulkApprovalForm) {
        bulkApprovalForm.addEventListener('submit', function(e) {
            e.preventDefault(); // ป้องกัน default form submission
            
            const action = e.submitter.value;
            const checkedBoxes = document.querySelectorAll('.po-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (count === 0) {
                alert('Please select at least one PO to ' + action);
                return;
            }
            
            const message = `Are you sure you want to ${action} ${count} Purchase Orders?`;
            if (!confirm(message)) {
                return;
            }
            
            // เก็บ PO numbers เป็น array
            const selectedPOs = Array.from(checkedBoxes).map(cb => cb.value);
            
            // สร้าง form data ใหม่
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('action', action);
            formData.append('bulk_note', document.getElementById('bulk_note').value || '');
            
            // เพิ่ม PO numbers เป็น array elements
            selectedPOs.forEach(poNumber => {
                formData.append('po_numbers[]', poNumber);
            });
            
            // แสดง loading state
            const submitBtn = e.submitter;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // ส่งข้อมูลด้วย fetch
            fetch('{{ route("po.bulk-approve") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // ถ้าสำเร็จให้ reload หน้า
                    window.location.reload();
                } else {
                    // ถ้า error ให้แสดง error message
                    return response.text().then(text => {
                        console.error('Server error:', text);
                        alert('Server error occurred. Please try again.');
                    });
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error occurred. Please check your connection and try again.');
            })
            .finally(() => {
                // คืนสถานะปุ่ม
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
@endsection