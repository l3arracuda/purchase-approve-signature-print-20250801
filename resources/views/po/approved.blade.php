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
                            <form method="GET" action="{{ route('po.approved') }}" id="searchForm">
                                {{-- Row 1: Basic Filters --}}
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="docno" class="form-label">
                                            <i class="fas fa-hashtag"></i> PO Number
                                        </label>
                                        <input type="text" class="form-control" id="docno" name="docno" 
                                               value="{{ $filters['docno'] ?? '' }}" placeholder="PP...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="customer" class="form-label">
                                            <i class="fas fa-building"></i> Customer Name
                                        </label>
                                        <input type="text" class="form-control" id="customer" name="customer" 
                                               value="{{ $filters['customer'] ?? '' }}" placeholder="Customer name">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="approval_level" class="form-label">
                                            <i class="fas fa-layer-group"></i> Approval Level
                                        </label>
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

                                {{-- Row 2: Amount and Date Range --}}
                                <div class="row mt-3">
                                    <div class="col-md-2">
                                        <label for="amount_from" class="form-label">
                                            <i class="fas fa-dollar-sign"></i> Amount From
                                        </label>
                                        <input type="number" class="form-control" id="amount_from" name="amount_from" 
                                               value="{{ $filters['amount_from'] ?? '' }}" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="amount_to" class="form-label">
                                            <i class="fas fa-dollar-sign"></i> Amount To
                                        </label>
                                        <input type="number" class="form-control" id="amount_to" name="amount_to" 
                                               value="{{ $filters['amount_to'] ?? '' }}" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_from" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Date From
                                        </label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               value="{{ $filters['date_from'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_to" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Date To
                                        </label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               value="{{ $filters['date_to'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="quick_date" class="form-label">
                                            <i class="fas fa-clock"></i> Quick Select
                                        </label>
                                        <select class="form-select" id="quick_date" onchange="setQuickDateRange(this.value)">
                                            <option value="">Select Range</option>
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="this_week">This Week</option>
                                            <option value="last_week">Last Week</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_month">Last Month</option>
                                            <option value="last_7_days">Last 7 Days</option>
                                            <option value="last_30_days">Last 30 Days</option>
                                            <option value="last_90_days">Last 90 Days</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearDateFilters()">
                                                <i class="fas fa-calendar-times"></i> Clear Dates
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 3: Additional Info --}}
                                <div class="row mt-3">
                                    <div class="col-md-8">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>Tips:</strong> 
                                            Customer และ Item data จะแสดงจากข้อมูลที่บันทึกไว้ในระบบ | 
                                            Date range จะค้นหาตามวันที่อนุมัติ | 
                                            ใช้ Quick Select เพื่อเลือกช่วงวันที่ได้รวดเร็ว
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <small class="text-muted">
                                            <i class="fas fa-filter"></i> 
                                            Active Filters: <span id="active-filter-count">{{ count(array_filter($filters ?? [])) }}</span>
                                        </small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ========== NEW: Bulk Action Panel ========== -->
                    @if(count($approvedPOs) > 0 && (Auth::user()->isManager() || Auth::user()->isGM() || Auth::user()->isAdmin() || Auth::user()->isUser()))
                        <div class="card mb-4 border-warning" id="bulkActionPanel" style="display: none;">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-tasks"></i> Bulk Actions
                                    <span class="badge bg-primary ms-2" id="selectedCount">0</span> selected
                                    <button type="button" class="btn btn-sm btn-outline-dark float-end" onclick="clearAllSelections()">
                                        <i class="fas fa-times"></i> Clear All
                                    </button>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-success" onclick="showBulkActionModal('approve')">
                                                <i class="fas fa-check"></i> Bulk Approve
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="showBulkActionModal('reject')">
                                                <i class="fas fa-times"></i> Bulk Reject
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="exportSelectedPOs()">
                                                <i class="fas fa-download"></i> Export Selected
                                            </button>
                                            <button type="button" class="btn btn-warning" onclick="showSelectedDetails()">
                                                <i class="fas fa-eye"></i> View Selected
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <small class="text-muted">
                                            Total Amount Selected: <strong id="selectedAmount">0.00</strong><br>
                                            Average Amount: <strong id="averageAmount">0.00</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Summary Statistics -->
                    @if(count($approvedPOs) > 0)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ count($approvedPOs) }}</h4>
                                        <small>POs on this page</small>
                                        @if(isset($pagination))
                                            <br><small>{{ number_format($pagination->total) }} total</small>
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

                        {{-- ========== NEW: Date Range Statistics ========== --}}
                        @if(isset($dateRangeStats) && $dateRangeStats)
                            <div class="card mb-4 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar-alt"></i>
                                        Date Range Statistics
                                        <small class="float-end">
                                            {{ $dateRangeStats['date_from'] ? date('d/m/Y', strtotime($dateRangeStats['date_from'])) : 'All time' }}
                                            @if($dateRangeStats['date_to'])
                                                - {{ date('d/m/Y', strtotime($dateRangeStats['date_to'])) }}
                                            @endif
                                        </small>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="border-end">
                                                <h5 class="text-primary mb-1">{{ number_format($dateRangeStats['total_pos']) }}</h5>
                                                <small class="text-muted">Total POs in Range</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border-end">
                                                <h5 class="text-success mb-1">{{ number_format($dateRangeStats['total_amount'], 2) }}</h5>
                                                <small class="text-muted">Total Amount in Range</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border-end">
                                                <h5 class="text-info mb-1">{{ number_format($dateRangeStats['avg_amount'], 2) }}</h5>
                                                <small class="text-muted">Average Amount</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-warning mb-1">
                                                @if($dateRangeStats['date_from'] && $dateRangeStats['date_to'])
                                                    {{ \Carbon\Carbon::parse($dateRangeStats['date_from'])->diffInDays(\Carbon\Carbon::parse($dateRangeStats['date_to'])) + 1 }}
                                                @else
                                                    N/A
                                                @endif
                                            </h5>
                                            <small class="text-muted">Days in Range</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Approved POs Table -->
                    <div class="table-responsive">
                        @if(count($approvedPOs) > 0)
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        {{-- ========== NEW: Bulk Selection Column ========== --}}
                                        @if(Auth::user()->isManager() || Auth::user()->isGM() || Auth::user()->isAdmin() || Auth::user()->isUser())
                                            <th style="width: 50px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                                    <label class="form-check-label" for="selectAll">
                                                        <small>All</small>
                                                    </label>
                                                </div>
                                            </th>
                                        @endif
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
                                        {{-- ========== NEW: Bulk Selection Checkbox ========== --}}
                                        @if(Auth::user()->isManager() || Auth::user()->isGM() || Auth::user()->isAdmin() || Auth::user()->isUser())
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input po-checkbox" 
                                                           type="checkbox" 
                                                           value="{{ $po->po_docno }}" 
                                                           data-amount="{{ $po->po_amount }}"
                                                           data-customer="{{ $po->customer_name ?? 'N/A' }}"
                                                           onchange="updateBulkActions()">
                                                </div>
                                            </td>
                                        @endif
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
                                                    <a href="{{ route('po.print', $po->po_docno) }}" 
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

<!-- ========== NEW: Bulk Action Modal ========== -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="bulkActionModalLabel">
                    <i class="fas fa-tasks"></i> Bulk Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm" method="POST" action="{{ route('po.bulk-action') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulkAction">
                    
                    <!-- Selected POs Summary -->
                    <div class="card mb-3 border-info">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-list"></i> Selected Purchase Orders
                                <span class="badge bg-primary float-end" id="modalSelectedCount">0</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h5 class="text-primary mb-1" id="modalTotalAmount">0.00</h5>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-info mb-1" id="modalAvgAmount">0.00</h5>
                                    <small class="text-muted">Average Amount</small>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-success mb-1" id="modalUniqueCustomers">0</h5>
                                    <small class="text-muted">Unique Customers</small>
                                </div>
                            </div>
                            
                            <!-- Selected POs List -->
                            <div class="mt-3">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>PO Number</th>
                                                <th>Customer</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-center">Level</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedPOsList">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">
                            <i class="fas fa-sticky-note"></i> Notes (Optional)
                        </label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Enter any notes for this bulk action..."></textarea>
                        <div class="form-text">
                            These notes will be applied to all selected POs
                        </div>
                    </div>

                    <!-- Warning/Confirmation -->
                    <div class="alert alert-warning" id="actionWarning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> You are about to <span id="actionText">perform action</span> on <span id="warningCount">0</span> Purchase Orders. This action cannot be undone.
                    </div>

                    <!-- Signature Check -->
                    <div class="alert alert-info">
                        <i class="fas fa-signature"></i>
                        <strong>Digital Signature Required:</strong> 
                        <span id="signatureStatus">Checking signature status...</span>
                    </div>

                    <!-- Hidden inputs for selected POs -->
                    <div id="selectedPOsInputs">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" id="confirmBulkActionBtn" onclick="executeBulkAction()">
                    <i class="fas fa-check"></i> <span id="confirmBtnText">Confirm Action</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== NEW: Selected POs Details Modal ========== -->
<div class="modal fade" id="selectedDetailsModal" tabindex="-1" aria-labelledby="selectedDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="selectedDetailsModalLabel">
                    <i class="fas fa-eye"></i> Selected POs Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="selectedDetailsContent">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="exportSelectedDetails()">
                    <i class="fas fa-download"></i> Export Details
                </button>
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
// ========== NEW: Bulk Action JavaScript Functions ==========

let selectedPOs = new Map(); // Use Map for better performance

// Toggle select all checkbox
function toggleSelectAll(checkbox) {
    const poCheckboxes = document.querySelectorAll('.po-checkbox');
    poCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// Update bulk actions panel and selected statistics
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.po-checkbox:checked');
    const count = checkboxes.length;
    const panel = document.getElementById('bulkActionPanel');
    
    // Clear previous selections
    selectedPOs.clear();
    
    // Update selected POs map
    checkboxes.forEach(cb => {
        selectedPOs.set(cb.value, {
            po_docno: cb.value,
            amount: parseFloat(cb.dataset.amount) || 0,
            customer: cb.dataset.customer || 'N/A'
        });
    });
    
    // Show/hide bulk action panel
    if (count > 0) {
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        panel.style.display = 'none';
    }
    
    // Update counters and statistics
    document.getElementById('selectedCount').textContent = count;
    
    if (count > 0) {
        const totalAmount = Array.from(selectedPOs.values()).reduce((sum, po) => sum + po.amount, 0);
        const avgAmount = totalAmount / count;
        
        document.getElementById('selectedAmount').textContent = totalAmount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        document.getElementById('averageAmount').textContent = avgAmount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    } else {
        document.getElementById('selectedAmount').textContent = '0.00';
        document.getElementById('averageAmount').textContent = '0.00';
    }
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAll');
    const totalCheckboxes = document.querySelectorAll('.po-checkbox').length;
    
    if (count === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (count === totalCheckboxes) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

// Clear all selections
function clearAllSelections() {
    const checkboxes = document.querySelectorAll('.po-checkbox, #selectAll');
    checkboxes.forEach(cb => cb.checked = false);
    selectedPOs.clear();
    updateBulkActions();
    
    showBulkToast('All selections cleared', 'info');
}

// Show bulk action modal
function showBulkActionModal(action) {
    if (selectedPOs.size === 0) {
        showBulkToast('Please select at least one PO', 'warning');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('bulkActionModal'));
    const actionInput = document.getElementById('bulkAction');
    const modalTitle = document.getElementById('bulkActionModalLabel');
    const modalHeader = document.getElementById('modalHeader');
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    const actionWarning = document.getElementById('actionWarning');
    const actionText = document.getElementById('actionText');
    const confirmBtnText = document.getElementById('confirmBtnText');
    
    // Set action
    actionInput.value = action;
    
    // Update modal appearance based on action
    if (action === 'approve') {
        modalTitle.innerHTML = '<i class="fas fa-check"></i> Bulk Approve POs';
        modalHeader.className = 'modal-header bg-success text-white';
        confirmBtn.className = 'btn btn-success';
        confirmBtnText.textContent = 'Approve Selected POs';
        actionText.textContent = 'APPROVE';
        actionWarning.className = 'alert alert-success';
    } else {
        modalTitle.innerHTML = '<i class="fas fa-times"></i> Bulk Reject POs';
        modalHeader.className = 'modal-header bg-danger text-white';
        confirmBtn.className = 'btn btn-danger';
        confirmBtnText.textContent = 'Reject Selected POs';
        actionText.textContent = 'REJECT';
        actionWarning.className = 'alert alert-danger';
    }
    
    // Update statistics in modal
    updateModalStatistics();
    
    // Check signature status
    checkSignatureStatus();
    
    // Show modal
    modal.show();
}

// Update statistics in modal
function updateModalStatistics() {
    const selectedArray = Array.from(selectedPOs.values());
    const count = selectedArray.length;
    const totalAmount = selectedArray.reduce((sum, po) => sum + po.amount, 0);
    const avgAmount = count > 0 ? totalAmount / count : 0;
    const uniqueCustomers = new Set(selectedArray.map(po => po.customer)).size;
    
    document.getElementById('modalSelectedCount').textContent = count;
    document.getElementById('modalTotalAmount').textContent = totalAmount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('modalAvgAmount').textContent = avgAmount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('modalUniqueCustomers').textContent = uniqueCustomers;
    document.getElementById('warningCount').textContent = count;
    
    // Update selected POs list in modal
    const selectedPOsList = document.getElementById('selectedPOsList');
    selectedPOsList.innerHTML = '';
    
    selectedArray.forEach(po => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong class="text-primary">${po.po_docno}</strong></td>
            <td>${po.customer}</td>
            <td class="text-end">${po.amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="text-center"><span class="badge bg-info">L${Math.floor(Math.random() * 3) + 1}</span></td>
        `;
        selectedPOsList.appendChild(row);
    });
    
    // Update hidden inputs
    const selectedPOsInputs = document.getElementById('selectedPOsInputs');
    selectedPOsInputs.innerHTML = '';
    
    selectedArray.forEach(po => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'po_docnos[]';
        input.value = po.po_docno;
        selectedPOsInputs.appendChild(input);
    });
}

// Check signature status
function checkSignatureStatus() {
    const signatureStatus = document.getElementById('signatureStatus');
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    
    signatureStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking signature status...';
    
    // Make AJAX call to check signature
    fetch('/api/signature/check')
        .then(response => response.json())
        .then(data => {
            if (data.has_signature) {
                signatureStatus.innerHTML = '<i class="fas fa-check text-success"></i> Digital signature found and active';
                confirmBtn.disabled = false;
            } else {
                signatureStatus.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> No active digital signature found. Please upload a signature first.';
                confirmBtn.disabled = true;
            }
        })
        .catch(error => {
            signatureStatus.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Unable to check signature status';
            confirmBtn.disabled = true;
        });
}

// Execute bulk action
function executeBulkAction() {
    const form = document.getElementById('bulkActionForm');
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    const action = document.getElementById('bulkAction').value;
    
    // Show loading state
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Submit form
    form.submit();
}

// Show selected POs details
function showSelectedDetails() {
    if (selectedPOs.size === 0) {
        showBulkToast('Please select at least one PO', 'warning');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('selectedDetailsModal'));
    const content = document.getElementById('selectedDetailsContent');
    
    // Generate detailed content
    const selectedArray = Array.from(selectedPOs.values());
    const totalAmount = selectedArray.reduce((sum, po) => sum + po.amount, 0);
    const uniqueCustomers = new Set(selectedArray.map(po => po.customer));
    
    content.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Selection Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary">${selectedArray.length}</h4>
                                <small class="text-muted">Selected POs</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success">${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                                <small class="text-muted">Total Amount</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-info">${uniqueCustomers.size}</h4>
                                <small class="text-muted">Unique Customers</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning">${(totalAmount / selectedArray.length).toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                                <small class="text-muted">Average Amount</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>PO Number</th>
                        <th>Customer Name</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${selectedArray.map(po => `
                        <tr>
                            <td><strong class="text-primary">${po.po_docno}</strong></td>
                            <td>${po.customer}</td>
                            <td class="text-end"><strong>${po.amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                            <td class="text-center">
                                <a href="/po/${po.po_docno}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <h6>Customer Breakdown:</h6>
            <div class="row">
                ${Array.from(uniqueCustomers).map(customer => {
                    const customerPOs = selectedArray.filter(po => po.customer === customer);
                    const customerTotal = customerPOs.reduce((sum, po) => sum + po.amount, 0);
                    return `
                        <div class="col-md-6 mb-2">
                            <div class="card">
                                <div class="card-body p-2">
                                    <strong>${customer}</strong><br>
                                    <small class="text-muted">${customerPOs.length} POs, Total: ${customerTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    modal.show();
}

// Export selected POs
function exportSelectedPOs() {
    if (selectedPOs.size === 0) {
        showBulkToast('Please select at least one PO', 'warning');
        return;
    }
    
    const selectedDocnos = Array.from(selectedPOs.keys());
    const exportData = {
        po_docnos: selectedDocnos,
        timestamp: new Date().toISOString(),
        total_count: selectedDocnos.length,
        total_amount: Array.from(selectedPOs.values()).reduce((sum, po) => sum + po.amount, 0)
    };
    
    // Create and download JSON file
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = `selected_pos_${new Date().toISOString().slice(0, 10)}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
    
    showBulkToast(`Exported ${selectedDocnos.length} selected POs`, 'success');
}

// Export selected details
function exportSelectedDetails() {
    const selectedArray = Array.from(selectedPOs.values());
    const csvContent = "data:text/csv;charset=utf-8," + 
        "PO Number,Customer Name,Amount\n" +
        selectedArray.map(po => `${po.po_docno},"${po.customer}",${po.amount}`).join("\n");
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `selected_pos_details_${new Date().toISOString().slice(0, 10)}.csv`);
    link.click();
    
    showBulkToast('Details exported successfully', 'success');
}

// Show bulk toast notification
function showBulkToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('bulkToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'bulkToastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'bulkToast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 
                   type === 'warning' ? 'bg-warning' : 
                   type === 'error' ? 'bg-danger' : 'bg-info';
    
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'warning' ? 'fa-exclamation-triangle' : 
                     type === 'error' ? 'fa-times-circle' : 'fa-info-circle';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass}"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = new bootstrap.Toast(document.getElementById(toastId));
    toastElement.show();
    
    // Remove toast element after it's hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// ========== EXISTING: Export functions ==========
function showExportOptions() {
    const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
    exportModal.show();
}

function exportToExcel() {
    // Create download URL with current filters
    const currentUrl = new URL(window.location.href);
    const params = currentUrl.searchParams;
    params.set('export', 'excel');
    
    const exportUrl = '/api/po-approved/export/excel?' + params.toString();
    window.open(exportUrl, '_blank');
}

function exportToPDF() {
    alert('PDF Export feature coming soon!');
}

function exportToCSV() {
    // Create download URL with current filters
    const currentUrl = new URL(window.location.href);
    const params = currentUrl.searchParams;
    params.set('export', 'csv');
    
    const exportUrl = '/api/po-approved/export/csv?' + params.toString();
    window.open(exportUrl, '_blank');
}

// ========== EXISTING: Date Range Functions ==========

// Set quick date range
function setQuickDateRange(range) {
    const today = new Date();
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today;
            break;
            
        case 'yesterday':
            startDate = endDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
            break;
            
        case 'this_week':
            const thisWeekStart = new Date(today);
            thisWeekStart.setDate(today.getDate() - today.getDay());
            startDate = thisWeekStart;
            endDate = today;
            break;
            
        case 'last_week':
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
            startDate = lastWeekStart;
            endDate = lastWeekEnd;
            break;
            
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = today;
            break;
            
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            startDate = lastMonth;
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
            
        case 'last_7_days':
            startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            endDate = today;
            break;
            
        case 'last_30_days':
            startDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
            endDate = today;
            break;
            
        case 'last_90_days':
            startDate = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000);
            endDate = today;
            break;
            
        default:
            return; // No range selected
    }
    
    // Format dates as YYYY-MM-DD for input fields
    if (startDate) {
        dateFrom.value = formatDateForInput(startDate);
    }
    if (endDate) {
        dateTo.value = formatDateForInput(endDate);
    }
    
    // Update active filter count
    updateActiveFilterCount();
    
    // Show success message
    showDateRangeToast(`Date range set to: ${range.replace('_', ' ').toUpperCase()}`, 'success');
}

// Clear date filters
function clearDateFilters() {
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    document.getElementById('quick_date').value = '';
    
    updateActiveFilterCount();
    showDateRangeToast('Date filters cleared', 'info');
}

// Format date for input field (YYYY-MM-DD)
function formatDateForInput(date) {
    return date.toISOString().split('T')[0];
}

// Update active filter count
function updateActiveFilterCount() {
    const form = document.getElementById('searchForm');
    const formData = new FormData(form);
    let activeCount = 0;
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            activeCount++;
        }
    }
    
    const countElement = document.getElementById('active-filter-count');
    if (countElement) {
        countElement.textContent = activeCount;
        countElement.className = activeCount > 0 ? 'badge bg-primary' : '';
    }
}

// Show date range toast notification
function showDateRangeToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('dateToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'dateToastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'dateToast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'info' ? 'bg-info' : 'bg-primary';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-calendar-check"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = new bootstrap.Toast(document.getElementById(toastId));
    toastElement.show();
    
    // Remove toast element after it's hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Validate date range
function validateDateRange() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    if (dateFrom && dateTo) {
        const startDate = new Date(dateFrom);
        const endDate = new Date(dateTo);
        
        if (startDate > endDate) {
            showDateRangeToast('Start date cannot be later than end date', 'warning');
            return false;
        }
        
        // Check if date range is too wide (more than 1 year)
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 365) {
            const confirmLarge = confirm('You selected a date range longer than 1 year. This might return a large amount of data. Continue?');
            if (!confirmLarge) {
                return false;
            }
        }
    }
    
    return true;
}

// Auto-refresh every 5 minutes for real-time updates
let autoRefreshInterval;

function startAutoRefresh() {
    // Clear existing interval
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(function() {
        // Only refresh if page is visible
        if (document.visibilityState === 'visible') {
            const lastRefresh = localStorage.getItem('po_approved_last_refresh');
            const now = Date.now();
            
            // Refresh if more than 5 minutes since last refresh
            if (!lastRefresh || (now - parseInt(lastRefresh)) > 300000) {
                showDateRangeToast('Refreshing data...', 'info');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }
    }, 300000); // 5 minutes
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update active filter count on page load
    updateActiveFilterCount();
    
    // Add event listeners for date inputs
    document.getElementById('date_from').addEventListener('change', function() {
        updateActiveFilterCount();
        validateDateRange();
    });
    
    document.getElementById('date_to').addEventListener('change', function() {
        updateActiveFilterCount();
        validateDateRange();
    });
    
    // Add validation to form submission
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        if (!validateDateRange()) {
            e.preventDefault();
            return false;
        }
    });
    
    // ========== NEW: Initialize bulk actions ==========
    // Add event listeners for checkboxes
    document.querySelectorAll('.po-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    // Keyboard shortcuts for bulk actions
    document.addEventListener('keydown', function(e) {
        // Ctrl + A: Select all visible POs
        if (e.ctrlKey && e.key === 'a' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = true;
                toggleSelectAll(selectAllCheckbox);
            }
        }
        
        // Escape: Clear selections
        if (e.key === 'Escape') {
            clearAllSelections();
        }
        
        // Ctrl + Shift + A: Approve selected (for quick bulk approve)
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            if (selectedPOs.size > 0) {
                showBulkActionModal('approve');
            }
        }
        
        // Ctrl + Shift + R: Reject selected (for quick bulk reject)
        if (e.ctrlKey && e.shiftKey && e.key === 'R') {
            e.preventDefault();
            if (selectedPOs.size > 0) {
                showBulkActionModal('reject');
            }
        }
    });
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Set last refresh time
    localStorage.setItem('po_approved_last_refresh', Date.now().toString());
    
    // Initialize bulk actions visibility
    updateBulkActions();
});

// Handle visibility change
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Page became visible, restart auto-refresh
        startAutoRefresh();
    } else {
        // Page became hidden, stop auto-refresh
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    }
});
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

/* ========== NEW: Bulk Action Styles ========== */

/* Bulk action panel styling */
#bulkActionPanel {
    animation: slideInDown 0.3s ease-out;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Checkbox styling */
.form-check-input {
    width: 1.2em;
    height: 1.2em;
    cursor: pointer;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.form-check-input:indeterminate {
    background-color: #ffc107;
    border-color: #ffc107;
}

/* Selected row highlighting */
.po-checkbox:checked {
    + td, ~ td {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
}

tr:has(.po-checkbox:checked) {
    background-color: rgba(0, 123, 255, 0.05) !important;
    border-left: 3px solid #007bff;
}

/* Bulk action buttons */
.btn-group .btn {
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Modal enhancements */
.modal-xl {
    max-width: 95%;
}

.modal-lg .modal-content {
    border-radius: 15px;
    overflow: hidden;
}

.modal-header {
    border-bottom: 2px solid rgba(255,255,255,0.2);
}

/* Statistics cards in modal */
.card.border-info {
    border-width: 2px !important;
}

/* Selected POs table in modal */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.sticky-top {
    background-color: #f8f9fa !important;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Toast enhancements */
.toast-container .toast {
    margin-bottom: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    border-radius: 10px;
}

.toast.show {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Badge counters */
.badge {
    animation: pulse 0.5s ease-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* ========== EXISTING: Date Range Styles ========== */

/* Date input styling */
input[type="date"] {
    position: relative;
    padding-right: 40px;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    color: #007bff;
    cursor: pointer;
    font-size: 1.1em;
}

/* Quick date selector styling */
#quick_date {
    border-color: #17a2b8;
    color: #17a2b8;
}

#quick_date:focus {
    border-color: #138496;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}

/* Active filter count badge */
#active-filter-count.badge {
    animation: pulse 1s infinite;
    margin-left: 5px;
}

/* Date range statistics card */
.border-primary {
    border-color: #007bff !important;
    border-width: 2px !important;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

/* Form icons */
.form-label i {
    color: #6c757d;
    margin-right: 5px;
    width: 12px;
    text-align: center;
}

/* Hover effects for form controls */
.form-control:hover {
    border-color: #80bdff;
    transition: border-color 0.15s ease-in-out;
}

.form-select:hover {
    border-color: #80bdff;
    transition: border-color 0.15s ease-in-out;
}

/* Date range quick select button */
.btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

/* Search form enhancements */
#searchForm {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

#searchForm .form-control,
#searchForm .form-select {
    border-radius: 8px;
    border: 1.5px solid #ced4da;
}

#searchForm .form-control:focus,
#searchForm .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Button enhancements */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Statistics cards hover effects */
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* Date range statistics special styling */
.card.border-primary .card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.card.border-primary .card-body h5 {
    font-weight: 700;
    font-size: 1.5rem;
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
    
    /* Mobile date inputs */
    input[type="date"] {
        font-size: 14px;
    }
    
    /* Mobile search form */
    #searchForm {
        padding: 15px;
    }
    
    /* Mobile statistics cards */
    .card .card-body h4 {
        font-size: 1.2rem;
    }
    
    .border-end {
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        margin-bottom: 10px;
        padding-bottom: 10px;
    }
    
    /* Mobile bulk actions */
    #bulkActionPanel .btn-group {
        flex-direction: column;
    }
    
    #bulkActionPanel .btn-group .btn {
        margin-bottom: 5px;
    }
    
    /* Mobile modals */
    .modal-lg, .modal-xl {
        max-width: 95%;
    }
    
    .modal-body {
        padding: 1rem;
    }
}

/* Print friendly styles */
@media print {
    .btn, .card-header, .pagination, .modal, #bulkActionPanel {
        display: none !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    .card {
        border: 1px solid #000;
        break-inside: avoid;
    }
    
    /* Hide checkboxes in print */
    .form-check {
        display: none !important;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .card {
        border: 2px solid #000;
    }
    
    .btn {
        border-width: 2px;
    }
    
    .table th {
        border: 2px solid #000;
    }
    
    .form-check-input {
        border-width: 2px;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Focus accessibility */
.form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

.btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

/* Dark mode support (if implemented) */
@media (prefers-color-scheme: dark) {
    /* This would be extended when dark mode is implemented */
}
</style>
@endsection