{{-- resources/views/po/approved.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-check-circle text-success"></i>
                        Approved Purchase Orders
                    </h4>
                    <small class="text-muted">รายการ Purchase Orders ที่ได้รับการอนุมัติแล้ว</small>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Search and Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>
                                <i class="fas fa-search"></i> Search & Filter
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('po.approved') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="docno" class="form-label">PO Number</label>
                                        <input type="text" class="form-control" id="docno" name="docno" 
                                               value="{{ $filters['docno'] ?? '' }}" placeholder="PP...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="customer" class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" id="customer" name="customer" 
                                               value="{{ $filters['customer'] ?? '' }}" placeholder="Customer name">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="amount_from" class="form-label">Amount From</label>
                                        <input type="number" class="form-control" id="amount_from" name="amount_from" 
                                               value="{{ $filters['amount_from'] ?? '' }}" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="amount_to" class="form-label">Amount To</label>
                                        <input type="number" class="form-control" id="amount_to" name="amount_to" 
                                            value="{{ $filters['amount_to'] ?? '' }}" step="0.01" placeholder="0.00">
                                    </div>                                    
                                    <div class="col-md-2">
                                        <label for="approval_level" class="form-label">Approval Level</label>
                                        <select class="form-select" id="approval_level" name="approval_level">
                                            <option value="">All Levels</option>
                                            <option value="1" {{ ($filters['approval_level'] ?? '') == '1' ? 'selected' : '' }}>Level 1 (User)</option>
                                            <option value="2" {{ ($filters['approval_level'] ?? '') == '2' ? 'selected' : '' }}>Level 2 (Manager)</option>
                                            <option value="3" {{ ($filters['approval_level'] ?? '') == '3' ? 'selected' : '' }}>Level 3 (GM)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('po.approved') }}" class="btn btn-secondary">
                                                <i class="fas fa-undo"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Statistics -->
                    @if(count($approvedPOs) > 0)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ count($approvedPOs) }}</h4>
                                        <small>POs on this page</small>
                                        @if(isset($pagination))
                                            <small>{{ number_format($pagination->total) }} total</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format(collect($approvedPOs)->sum('po_amount'), 2) }}</h4>
                                        <small>Total Amount (Page)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ collect($approvedPOs)->where('max_approval_level', 3)->count() }}</h4>
                                        <small>Fully Approved</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ number_format(collect($approvedPOs)->avg('po_amount'), 2) }}</h4>
                                        <small>Average Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ========== NEW: Bulk Actions Bar ========== --}}
                    <div class="card mb-4 d-none" id="bulk-actions-card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-check-square"></i> 
                                Bulk Actions - <span id="selected-count">0</span> รายการที่เลือก
                                <button type="button" class="btn btn-light btn-sm float-end" id="clear-selection">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="bulk-action-form" method="POST" action="{{ route('po.bulk-action') }}">
                                @csrf
                                
                                <div class="row align-items-end">
                                    <div class="col-md-3">
                                        <label for="bulk-action" class="form-label">Action</label>
                                        <select class="form-select" id="bulk-action" name="action" required>
                                            <option value="">เลือกการดำเนินการ</option>
                                            <option value="approve" selected>Approve Selected POs</option>
                                            {{-- <option value="reject">Reject Selected POs</option> --}}
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="bulk-notes" class="form-label">Note (Optional)</label>
                                        <input type="text" class="form-control" id="bulk-notes" name="notes" 
                                               placeholder="Add a note for this bulk action">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Execute Bulk Action
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Select POs and choose action
                                        </small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Approved POs Table -->
                    <div class="table-responsive">
                        @if(count($approvedPOs) > 0)
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select-all" title="Select All" style="transform: scale(1.5);">
                                                <label class="form-check-label" for="select-all"></label>
                                            </div>
                                        </th>
                                        <th>PO Number</th>
                                        <th>Customer Name</th>
                                        <th style="text-align: center;">Items</th>
                                        <th style="text-align: center;">Amount</th>
                                        <th style="text-align: center;">Approval Level</th>
                                        <th style="text-align: center;">Status</th>
                                        <th style="text-align: center;">Progress</th>
                                        <th>Last Approved</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvedPOs as $po)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input po-checkbox" 
                                                       type="checkbox" 
                                                       value="{{ $po->po_docno }}" 
                                                       id="po_{{ $loop->index }}"
                                                       style="transform: scale(1.5);">
                                                <label class="form-check-label" for="po_{{ $loop->index }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $po->po_docno }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ $po->customer_name ?? 'N/A' }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $po->item_count ?? 0 }} items</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($po->po_amount, 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center">
                                                @for($level = 1; $level <= 3; $level++)
                                                    @if($level <= $po->max_approval_level)
                                                        <i class="fas fa-check-circle text-success mx-1" title="Level {{ $level }} Approved"></i>
                                                    @else
                                                        <i class="fas fa-circle text-muted mx-1" title="Level {{ $level }} Pending"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                            <small class="text-muted">Level {{ $po->max_approval_level }}/3</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $po->status_class }}">
                                                {{ $po->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 10px; min-width: 80px;">
                                                <div class="progress-bar bg-{{ $po->status_class }}" 
                                                     style="width: {{ $po->progress_percentage }}%"
                                                     title="{{ number_format($po->progress_percentage, 1) }}%">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ number_format($po->progress_percentage, 0) }}%</small>
                                        </td>
                                        <td>
                                            @if($po->last_approval_date)
                                                {{ date('d/m/Y H:i', strtotime($po->last_approval_date)) }}
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($po->last_approval_date)->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('po.show', $po->po_docno) }}" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                @if(Auth::user()->approval_level >= 1)
                                                    <button type="button" 
                                                        class="btn btn-outline-success btn-sm"
                                                        onclick="printInPopup('{{ $po->po_docno }}')"
                                                        id="printPopupBtn">
                                                        <i class="fas fa-print"></i> Print
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            @if(isset($pagination) && $pagination->total_pages > 1)
                                <nav aria-label="Approved PO Pagination">
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

                            <!-- Detail Summary -->
                            <div class="card mt-4 bg-light">
                                <div class="card-header">
                                    <strong>Approval Summary</strong>
                                    @if(isset($pagination))
                                        <small class="text-muted float-end">
                                            Showing {{ count($approvedPOs) }} of {{ number_format($pagination->total) }} approved POs
                                        </small>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-2">
                                            <h6 class="text-success">{{ collect($approvedPOs)->where('max_approval_level', 1)->count() }}</h6>
                                            <small class="text-muted">Level 1 Only</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h6 class="text-warning">{{ collect($approvedPOs)->where('max_approval_level', 2)->count() }}</h6>
                                            <small class="text-muted">Level 2 (Manager)</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h6 class="text-info">{{ collect($approvedPOs)->where('max_approval_level', 3)->count() }}</h6>
                                            <small class="text-muted">Level 3 (GM)</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h6 class="text-primary">{{ number_format(collect($approvedPOs)->sum('approval_count')) }}</h6>
                                            <small class="text-muted">Total Approvals</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h6 class="text-secondary">{{ number_format(collect($approvedPOs)->sum('item_count')) }}</h6>
                                            <small class="text-muted">Total Items</small>
                                        </div>
                                        <div class="col-md-2">
                                            <h6 class="text-dark">{{ collect($approvedPOs)->unique('customer_name')->count() }}</h6>
                                            <small class="text-muted">Unique Customers</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @else
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>No Approved Purchase Orders Found</h5>
                                <p>ไม่มี Purchase Orders ที่ได้รับการอนุมัติตามเงื่อนไขที่ค้นหา</p>
                                @if(!empty(array_filter($filters)))
                                    <a href="{{ route('po.approved') }}" class="btn btn-primary">
                                        <i class="fas fa-list"></i> แสดงทั้งหมด
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h6>Quick Actions</h6>
                        <div class="btn-group" role="group">
                            <a href="{{ route('po.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> All POs
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            @if(Auth::user()->isManager() || Auth::user()->isGM() || Auth::user()->isAdmin() || Auth::user()->isUser())
                                <button type="button" class="btn btn-outline-info" onclick="showExportOptions()">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Options Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download"></i> Export Approved POs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>เลือกรูปแบบการ Export ข้อมูล:</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="exportToCSV()">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
// Export functions
function showExportOptions() {
    const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
    exportModal.show();
}

function exportToExcel() {
    alert('Excel Export feature coming soon!');
}

function exportToPDF() {
    alert('PDF Export feature coming soon!');
}

function exportToCSV() {
    alert('CSV Export feature coming soon!');
}

// Auto-refresh every 5 minutes for real-time updates
setInterval(function() {
    // Uncomment if you want auto-refresh
    // window.location.reload();
}, 300000); // 5 minutes
</script>

<!-- Custom CSS for better presentation -->
<style>
.progress {
    border-radius: 10px;
}

.badge {
    font-size: 0.75em;
}

.table th {
    background-color: #343a40;
    color: white;
    font-weight: 600;
    border: none;
}

.table td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.card-header h6 {
    margin-bottom: 0;
    font-weight: 600;
}

.text-muted {
    color: #6c757d !important;
}

/* Custom progress bar colors */
.progress-bar.bg-success {
    background-color: #28a745 !important;
}

.progress-bar.bg-warning {
    background-color: #ffc107 !important;
}

.progress-bar.bg-info {
    background-color: #17a2b8 !important;
}

/* Responsive table improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.15rem 0.3rem;
        font-size: 0.7rem;
    }
}
</style>
@endsection

@push('scripts')
<script>

    // Print in popup window
                function printInPopup(docNo) {
                    const popupUrl = `/po/${docNo}/print/popup`;
                    const popup = window.open(popupUrl, 'printPO', 'width=1000,height=800,scrollbars=yes,resizable=yes');
                    
                    if (popup) {
                        popup.focus();
                    } else {
                        alert('กรุณาอนุญาตให้เว็บไซต์เปิดหน้าต่างใหม่ได้');
                    }
                }
// Test if jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded!');
    alert('jQuery is not loaded! Bulk actions will not work.');
} else {
    console.log('jQuery version:', jQuery.fn.jquery);
}

$(document).ready(function() {
    console.log('Bulk approval script loaded');
    console.log('Document ready event fired');
    
    // Test if elements exist
    console.log('Number of PO checkboxes found:', $('.po-checkbox').length);
    console.log('Select all checkbox found:', $('#select-all').length);
    console.log('Bulk actions card found:', $('#bulk-actions-card').length);
    
    // Toggle bulk actions card visibility
    function toggleBulkActionsVisibility() {
        const checkedCount = $('.po-checkbox:checked').length;
        console.log('Checked count:', checkedCount);
        
        if (checkedCount > 0) {
            $('#bulk-actions-card').removeClass('d-none');
            $('#selected-count').text(checkedCount);
            console.log('Showing bulk actions card');
        } else {
            $('#bulk-actions-card').addClass('d-none');
            console.log('Hiding bulk actions card');
        }
    }

    // Select All checkbox functionality
    $('#select-all').change(function() {
        const isChecked = $(this).is(':checked');
        console.log('Select all clicked:', isChecked);
        $('.po-checkbox').prop('checked', isChecked);
        toggleBulkActionsVisibility();
    });

    // Individual checkbox functionality
    $(document).on('change', '.po-checkbox', function() {
        console.log('Individual checkbox changed');
        const totalCheckboxes = $('.po-checkbox').length;
        const checkedCheckboxes = $('.po-checkbox:checked').length;
        console.log('Total:', totalCheckboxes, 'Checked:', checkedCheckboxes);
        
        // Update Select All checkbox state
        if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all').prop('checked', true);
        } else {
            $('#select-all').prop('checked', false);
        }
        
        toggleBulkActionsVisibility();
    });

    // Test click event directly
    $('.po-checkbox').click(function() {
        console.log('Direct click event fired on checkbox');
    });

    // Bulk action form submission
    $('#bulk-action-form').submit(function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        const selectedPOs = $('.po-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        console.log('Selected POs:', selectedPOs);

        if (selectedPOs.length === 0) {
            alert('กรุณาเลือกรายการที่ต้องการดำเนินการ');
            return;
        }

        const action = $('#bulk-action').val();
        if (!action) {
            alert('กรุณาเลือกการดำเนินการ');
            return;
        }

        const notes = $('#bulk-notes').val();
        
        // คำนวณยอดรวมของรายการที่เลือก
        let totalAmount = 0;
        $('.po-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const amountText = row.find('td:nth-child(5)').text().replace(/,/g, '');
            const amount = parseFloat(amountText) || 0;
            totalAmount += amount;
        });

        const formattedAmount = new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(totalAmount);

        if (confirm(`คุณต้องการ${action === 'approve' ? 'อนุมัติ' : 'ปฏิเสธ'} ${selectedPOs.length} รายการ\nยอดรวม: ${formattedAmount} บาทหรือไม่?`)) {
            // Create form with selected POs
            const form = $('<form>', {
                method: 'POST',
                action: '{{ route("po.bulk-action") }}'
            });

            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: '{{ csrf_token() }}'
            }));

            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: action
            }));

            form.append($('<input>', {
                type: 'hidden',
                name: 'notes',
                value: notes
            }));

            selectedPOs.forEach(poDocno => {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'po_docnos[]',
                    value: poDocno
                }));
            });

            console.log('Submitting form with data:', {
                action: action,
                notes: notes,
                po_docnos: selectedPOs
            });

            $('body').append(form);
            form.submit();
        }
    });

    // Clear selection button
    $('#clear-selection').click(function() {
        console.log('Clear selection clicked');
        $('.po-checkbox').prop('checked', false);
        $('#select-all').prop('checked', false);
        toggleBulkActionsVisibility();
    });
});
</script>
@endpush