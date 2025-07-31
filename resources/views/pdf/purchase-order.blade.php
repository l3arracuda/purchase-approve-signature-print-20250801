{{-- resources/views/pdf/purchase-order.blade.php --}}

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $po->header->DocNo }}</title>
    <style>
        /* PDF Styles */
        @page {
            margin: 2cm;
            size: A4;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        /* PO Info Section */
        .po-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .po-info-left,
        .po-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        
        .po-info-left {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        
        .po-info-right {
            border: 1px solid #ddd;
            border-left: none;
        }
        
        .info-title {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        /* Summary Section */
        .summary-section {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .summary-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .summary-table td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        
        .summary-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 150px;
        }
        
        .summary-table .amount {
            text-align: right;
            width: 150px;
        }
        
        .summary-table .total-row td {
            background-color: #e8f4fd;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        
        /* Approval Section */
        .approval-section {
            margin-top: 30px;
            border: 2px solid #333;
            padding: 15px;
        }
        
        .approval-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        
        .approval-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .approval-level {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
            text-align: center;
            min-height: 120px;
        }
        
        .approval-level-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .signature-area {
            height: 60px;
            margin: 10px 0;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fafafa;
        }
        
        .signature-image {
            max-width: 120px;
            max-height: 50px;
            border: none;
        }
        
        .signature-placeholder {
            color: #999;
            font-style: italic;
            font-size: 10px;
        }
        
        .approver-info {
            font-size: 10px;
            margin-top: 5px;
        }
        
        .approver-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .approval-date {
            color: #666;
            margin-bottom: 2px;
        }
        
        .approval-note {
            font-style: italic;
            color: #555;
            font-size: 9px;
            margin-top: 5px;
            padding: 3px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* Page Break */
        .page-break {
            page-break-after: always;
        }
        
        /* Print Optimization */
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            @if(isset($company['logo_path']) && $company['logo_path'])
                <img src="{{ $company['logo_path'] }}" alt="Company Logo" style="height: 50px; margin-bottom: 10px;">
            @endif
            
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-info">
                {{ $company['address'] }}<br>
                Tel: {{ $company['tel'] }} | Fax: {{ $company['fax'] }} | Email: {{ $company['email'] }}
            </div>
            
            <div class="document-title">PURCHASE ORDER</div>
        </div>

        <!-- PO Information Section -->
        <div class="po-info">
            <div class="po-info-left">
                <div class="info-title">Purchase Order Information</div>
                <div class="info-row">
                    <span class="info-label">PO Number:</span>
                    <strong>{{ $po->header->DocNo }}</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Date:</span>
                    {{ date('d/m/Y', strtotime($po->header->DateNo)) }}
                </div>
                @if($po->header->DocRef)
                <div class="info-row">
                    <span class="info-label">Reference:</span>
                    {{ $po->header->DocRef }}
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Credit Term:</span>
                    {{ $po->header->CreditTerm }} days
                </div>
            </div>
            
            <div class="po-info-right">
                <div class="info-title">Supplier Information</div>
                <div class="info-row">
                    <span class="info-label">Supplier:</span>
                    <strong>{{ $po->header->SupName }}</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Code:</span>
                    {{ $po->header->SupNo }}
                </div>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    {{ $po->header->AddressSup }}<br>
                    {{ $po->header->Province }} {{ $po->header->ZipCode }}
                </div>
                @if($po->header->ContactName)
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    {{ $po->header->ContactName }}
                </div>
                @endif
                @if($po->header->Phone)
                <div class="info-row">
                    <span class="info-label">Tel:</span>
                    {{ $po->header->Phone }}
                </div>
                @endif
                @if($po->header->FAX)
                <div class="info-row">
                    <span class="info-label">Fax:</span>
                    {{ $po->header->FAX }}
                </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">No.</th>
                    <th style="width: 15%">Product Code</th>
                    <th style="width: 35%">Description</th>
                    <th style="width: 10%">Quantity</th>
                    <th style="width: 8%">Unit</th>
                    <th style="width: 12%">Unit Price</th>
                    <th style="width: 15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($po->details as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->ProductNo }}</td>
                    <td>{{ $item->ProductName }}</td>
                    <td class="text-center">{{ number_format($item->QTY, 2) }}</td>
                    <td class="text-center">{{ $item->Unit }}</td>
                    <td class="text-right">{{ number_format($item->Price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->LineTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">{{ number_format($po->header->TotalAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Discount:</td>
                    <td class="amount">{{ number_format($po->header->DiscountAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">VAT (7%):</td>
                    <td class="amount">{{ number_format($po->header->VatAmount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Net Total:</td>
                    <td class="amount">{{ number_format($po->header->NetAmount, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($po->header->Remember || $po->header->Note)
        <!-- Notes Section -->
        <div style="margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">
            @if($po->header->Remember)
                <div style="margin-bottom: 5px;">
                    <strong>Remember:</strong> {{ $po->header->Remember }}
                </div>
            @endif
            @if($po->header->Note)
                <div>
                    <strong>Note:</strong> {{ $po->header->Note }}
                </div>
            @endif
        </div>
        @endif

        <!-- Approval Section -->
        @if($approvals->count() > 0)
        <div class="approval-section">
            <div class="approval-title">APPROVAL SIGNATURES</div>
            
            <div class="approval-grid">
                @for($level = 1; $level <= 3; $level++)
                    @php
                        $approval = $approvals->where('approval_level', $level)->first();
                    @endphp
                    <div class="approval-level">
                        <div class="approval-level-title">
                            Level {{ $level }} - 
                            @if($level == 1) User Approval
                            @elseif($level == 2) Manager Approval  
                            @else GM Final Approval
                            @endif
                        </div>
                        
                        <div class="signature-area">
                            @if($approval && $approval->signature_full_path && file_exists($approval->signature_full_path))
                                <img src="{{ $approval->signature_full_path }}" alt="Signature" class="signature-image">
                            @else
                                <div class="signature-placeholder">
                                    @if($approval)
                                        Signature Not Available
                                    @else
                                        Pending Approval
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        @if($approval)
                            <div class="approver-info">
                                <div class="approver-name">{{ $approval->approver_name }}</div>
                                <div class="approver-name">({{ $approval->approver_username }})</div>
                                <div class="approval-date">{{ $approval->formatted_date }}</div>
                                @if($approval->approval_note)
                                    <div class="approval-note">{{ $approval->approval_note }}</div>
                                @endif
                            </div>
                            <div class="status-badge status-approved">APPROVED</div>
                        @else
                            <div class="approver-info">
                                <div class="status-badge status-pending">PENDING</div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
        @else
        <!-- No Approvals Yet -->
        <div class="approval-section">
            <div class="approval-title">APPROVAL STATUS</div>
            <div style="text-align: center; padding: 20px; color: #666;">
                <div class="status-badge status-pending">PENDING INITIAL APPROVAL</div>
                <p style="margin-top: 10px; font-size: 11px;">
                    This Purchase Order is waiting for approval process to begin.
                </p>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>Document generated on: {{ $generated_at->format('d/m/Y H:i:s') }}</div>
            <div>System: Purchase Approval System v2.0</div>
            <div style="margin-top: 5px;">
                This is a computer-generated document. Digital signatures are legally binding.
            </div>
        </div>
    </div>
</body>
</html>