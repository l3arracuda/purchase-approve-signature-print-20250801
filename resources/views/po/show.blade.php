@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

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
                            <span class="badge bg-light text-dark fs-6">{{ $po->header->AppStatus }}</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- PO Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="fas fa-info-circle"></i> Purchase Order Information
                            </h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="30%"><strong>PO Number:</strong></td>
                                    <td>{{ $po->header->DocNo }}</td>
                                </tr>
                                <tr>
                                    <td><strong>PO Date:</strong></td>
                                    <td>{{ date('d/m/Y', strtotime($po->header->DateNo)) }}</td>
                                </tr>
                                {{-- <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td>{{ $po->header->DocRef ?? '-' }}</td>
                                </tr> --}}
                                <tr>
                                    <td><strong>Credit Term:</strong></td>
                                    <td>{{ $po->header->CreditTerm }} days</td>
                                </tr>
                                @if($po->header->Note)
                                <tr>
                                    <td><strong>Remember:</strong></td>
                                    <td>{{ $po->header->Remember }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Note:</strong></td>
                                    <td>{{ $po->header->Note }}</td>
                                </tr>                                
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="fas fa-building"></i> Supplier Information
                            </h6>
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
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Product Code</th>
                                    <th width="35%">Product Name</th>
                                    <th width="10%" class="text-center">Quantity</th>
                                    <th width="10%" class="text-center">Unit</th>
                                    <th width="15%" class="text-end">Unit Price</th>
                                    <th width="15%" class="text-end">Line Total</th>
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
                                        <strong>{{ number_format($item->LineTotal, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Row -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Approval Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-check-circle"></i>
                                Approval Status & History
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
                                    <strong>No approval history yet.</strong> This PO is pending initial approval.
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
                                
                                <hr>
                            @else
                                @if($canApprove['reason'] !== 'This PO must be approved by level 1 first' && $canApprove['reason'] !== 'This PO must be approved by level 2 first')
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Cannot Approve</strong><br>
                                        <small>{{ $canApprove['reason'] }}</small>
                                    </div>
                                    <hr>
                                @endif
                            @endif

                            {{-- ========== UPDATED: Print Document Section (แทน PDF) ========== --}}
                            <div class="print-section">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-print"></i> เอกสารพิมพ์
                                </h6>
                                
                                {{-- Print Status Check --}}
                                <div id="printStatusInfo" class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status" id="printStatusSpinner">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <small class="text-muted">กำลังตรวจสอบสถานะ...</small>
                                    </div>
                                </div>
                                
                                {{-- Print Action Buttons --}}
                                <div class="d-grid gap-2" id="printActionButtons" style="display: none;">
                                    {{-- Print in Same Window --}}
                                    <a href="{{ route('po.print', $po->header->DocNo) }}" 
                                    target="_blank" 
                                    class="btn btn-primary btn-sm"
                                    id="printDirectBtn">
                                        <i class="fas fa-print"></i> พิมพ์เอกสาร
                                    </a>
                                    
                                    {{-- Print in Popup --}}
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm"
                                            onclick="printInPopup('{{ $po->header->DocNo }}')"
                                            id="printPopupBtn">
                                        <i class="fas fa-external-link-alt"></i> พิมพ์ (หน้าต่างใหม่)
                                    </button>
                                    
                                    {{-- Print History Button (for authorized users) --}}
                                    @if(Auth::user()->approval_level >= 2)
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-sm" 
                                                onclick="showPrintHistory('{{ $po->header->DocNo }}')"
                                                id="printHistoryBtn">
                                            <i class="fas fa-history"></i> ประวัติการพิมพ์
                                        </button>
                                    @endif
                                    
                                    {{-- Export Data Button (Manager+ only) --}}
                                    @if(Auth::user()->approval_level >= 2)
                                        <a href="{{ route('po.print.export', $po->header->DocNo) }}" 
                                        class="btn btn-outline-info btn-sm"
                                        download>
                                            <i class="fas fa-download"></i> Export ข้อมูล (JSON)
                                        </a>
                                    @endif
                                    
                                    {{-- Debug Button (Admin only) --}}
                                    @if(Auth::user()->isAdmin())
                                        <a href="{{ route('po.print.debug', $po->header->DocNo) }}" 
                                        target="_blank" 
                                        class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-bug"></i> Debug ข้อมูล
                                        </a>
                                    @endif
                                </div>
                                
                                {{-- Print Info Display --}}
                                <div id="printInfoDisplay" class="mt-3" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body p-2">
                                            <small class="text-muted">
                                                <div><i class="fas fa-check-circle text-success"></i> <strong>การอนุมัติ:</strong> <span id="approvalCount">-</span> ระดับ</div>
                                                <div><i class="fas fa-print text-info"></i> <strong>พิมพ์แล้ว:</strong> <span id="printCount">-</span> ครั้ง</div>
                                                <div><i class="fas fa-signature text-warning"></i> <strong>มีลายเซ็น:</strong> <span id="hasSignatures">-</span></div>
                                                <div><i class="fas fa-money-bill text-primary"></i> <strong>ยอดเงิน:</strong> ฿<span id="printAmount">-</span></div>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                            </div>

                            {{-- General Actions --}}
                            <div class="d-grid gap-2">
                                <a href="{{ route('po.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> กลับไปรายการ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== Print History Modal ========== --}}
                <div class="modal fade" id="printHistoryModal" tabindex="-1" aria-labelledby="printHistoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="printHistoryModalLabel">
                                    <i class="fas fa-history"></i> ประวัติการพิมพ์ - {{ $po->header->DocNo }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="printHistoryContent">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">กำลังโหลดประวัติการพิมพ์...</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== JavaScript for Print Features ========== --}}
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Check print status when page loads
                    checkPrintStatus();
                    
                    // Existing approval form script
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

                // Check print status
                function checkPrintStatus() {
                    const statusInfo = document.getElementById('printStatusInfo');
                    const actionButtons = document.getElementById('printActionButtons');
                    const infoDisplay = document.getElementById('printInfoDisplay');
                    const spinner = document.getElementById('printStatusSpinner');
                    
                    fetch(`{{ route('po.print.status', $po->header->DocNo) }}`)
                        .then(response => response.json())
                        .then(data => {
                            spinner.style.display = 'none';
                            
                            if (data.success) {
                                if (data.can_print) {
                                    // Show print buttons
                                    statusInfo.innerHTML = `
                                        <div class="alert alert-success alert-sm py-2">
                                            <i class="fas fa-check-circle"></i>
                                            <small><strong>พร้อมพิมพ์:</strong> สามารถพิมพ์เอกสารใบสั่งซื้อนี้ได้</small>
                                        </div>
                                    `;
                                    actionButtons.style.display = 'block';
                                    
                                    // Show print info
                                    document.getElementById('approvalCount').textContent = data.approval_count;
                                    document.getElementById('printCount').textContent = data.print_count;
                                    document.getElementById('hasSignatures').textContent = data.has_signatures ? 'มี' : 'ไม่มี';
                                    document.getElementById('printAmount').textContent = Number(data.po_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
                                    infoDisplay.style.display = 'block';
                                    
                                } else {
                                    // Show reason why can't print
                                    statusInfo.innerHTML = `
                                        <div class="alert alert-warning alert-sm py-2">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <small><strong>ไม่สามารถพิมพ์ได้:</strong> ${data.reason || 'ไม่ทราบสาเหตุ'}</small>
                                        </div>
                                    `;
                                }
                            } else {
                                statusInfo.innerHTML = `
                                    <div class="alert alert-danger alert-sm py-2">
                                        <i class="fas fa-times-circle"></i>
                                        <small><strong>เกิดข้อผิดพลาด:</strong> ${data.error}</small>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error checking print status:', error);
                            spinner.style.display = 'none';
                            statusInfo.innerHTML = `
                                <div class="alert alert-danger alert-sm py-2">
                                    <i class="fas fa-times-circle"></i>
                                    <small><strong>ข้อผิดพลาดเครือข่าย:</strong> ไม่สามารถตรวจสอบสถานะการพิมพ์ได้</small>
                                </div>
                            `;
                        });
                }

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

                // Show print history modal
                function showPrintHistory(docNo) {
                    const modal = new bootstrap.Modal(document.getElementById('printHistoryModal'));
                    const content = document.getElementById('printHistoryContent');
                    
                    // Reset content
                    content.innerHTML = `
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">กำลังโหลดประวัติการพิมพ์...</div>
                        </div>
                    `;
                    
                    modal.show();
                    
                    // Load print history
                    fetch(`/po/${docNo}/print-history`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.history.length > 0) {
                                let tableHtml = `
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>วันที่/เวลา</th>
                                                    <th>ผู้พิมพ์</th>
                                                    <th>ประเภท</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;
                                
                                data.history.forEach(record => {
                                    const date = new Date(record.created_at).toLocaleString('th-TH');
                                    const typeLabel = record.print_type === 'html_print' ? 'HTML Print' : record.print_type.toUpperCase();
                                    tableHtml += `
                                        <tr>
                                            <td>
                                                <strong>${date}</strong>
                                            </td>
                                            <td>
                                                <strong>${record.printed_by_name}</strong><br>
                                                <small class="text-muted">(${record.printed_by_username})</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">${typeLabel}</span>
                                            </td>
                                        </tr>
                                    `;
                                });
                                
                                tableHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                `;
                                
                                content.innerHTML = tableHtml;
                            } else {
                                content.innerHTML = `
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                                        <h6>ไม่มีประวัติการพิมพ์</h6>
                                        <p class="mb-0">ใบสั่งซื้อนี้ยังไม่เคยถูกพิมพ์</p>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading print history:', error);
                            content.innerHTML = `
                                <div class="alert alert-danger text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h6>เกิดข้อผิดพลาดในการโหลดข้อมูล</h6>
                                    <p class="mb-0">กรุณาลองใหม่อีกครั้ง</p>
                                </div>
                            `;
                        });
                }
                </script>
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>
@endsection

