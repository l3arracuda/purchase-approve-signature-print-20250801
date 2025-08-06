{{-- สร้างไฟล์ใหม่: resources/views/print/purchase-order.blade.php --}}

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $print_title }}</title>
    <style>
        /* Print-Optimized Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Angsana New', 'TH SarabunPSK', Tahoma;
            font-size: 20px;
            line-height: 1.4;
            background: white;
            margin: 0;
            padding: 20px;
        }
        
        /* Print Media Styles */
        @media print {
            body {
                font-size: 20px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .container {
                max-width: none;
                width: 100%;
            }
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px 20px 20px 0px;
        }
        
        /* Header Styles */
        .header {
            padding-bottom: 165px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
        
        .document-info {
            text-align: left;
            padding-top: 20px;
        }
        
        .company-info-section {
            text-align: right;
        }
        
        .company-logo {
            margin-bottom: 10px;
        }
        
        .company-logo img {
            max-height: 60px;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-name-en {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .company-info {
            font-size: 12px;
            line-height: 1.5;
        }
        
        .document-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        
        .document-title-en {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        /* PO Info Section */
        .po-info-section {
            margin-bottom: 60px;
            line-height: 1.4;
        }
        
        .po-info-grid {
            display: grid;
            grid-template-columns: 80% 20%;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .po-info-box-L {
            padding-left: 60px;
        }

        .po-info-box-R {
        }
        
        .info-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 12px;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            align-items: flex-start;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .info-value {
            flex: 1;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 5px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding-right: 20px;
            text-align: left;
            vertical-align: top;
        }
        
        .items-table th {
            font-weight: bold;
            text-align: center;
        }
        
        .items-table tbody tr:nth-child(even) {
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }        
        
        /* Notes Section */
        .notes-section {
            margin-bottom: 25px;
            padding: 15px;
        }
        
        .notes-section .note-item {
            margin-bottom: 8px;
        }
        
        .notes-section .note-label {
            font-weight: bold;
        }
        
        /* Approval Section */
        .approval-section {
            margin-top: 5px;
            margin-right: 80px;
            padding: 5px;
        }
        
        .approval-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .approval-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .approval-level {
            padding: 15px;
            text-align: center;
            min-height: 200px;
        }        
        
        .approval-level-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
            padding-bottom: 8px;
        }
        
        .signature-area {
            height: 80px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            position: relative;
        }
        
        .signature-image {
            max-width: 150px;
            max-height: 70px;
            border: none;
            object-fit: contain;
        }
        
        .signature-placeholder {
            font-style: italic;
            font-size: 12px;
            text-align: center;
        }
        
        .approver-info {
            font-size: 12px;
            margin-top: 10px;
            line-height: 1.4;
        }
        
        .approver-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .approver-username {
            margin-bottom: 3px;
        }
        
        .approval-date {
            margin-bottom: 5px;
        }
        
        .approval-note {
            font-style: italic;
            font-size: 11px;
            margin-top: 8px;
            padding: 5px;
            border-radius: 3px;
            text-align: left;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .status-approved {
        }
        
        .status-pending {
        }
        
        /* Footer */
        .footer {
            /* margin-top: 40px;
            padding-top: 20px;*/
            text-align: center; 
        }
        
        .footer-row {
            margin-bottom: 5px;
        }
        
        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .document-info {
                text-align: center;
                order: 2;
                padding-top: 15px;
            }
            
            .company-info-section {
                text-align: center;
                order: 1;
            }
            
            .po-info-grid {
                grid-template-columns: 1fr;
            }
            
            .approval-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls (แสดงเฉพาะบน screen) -->
    <div class="print-controls no-print">
        <button class="btn" onclick="window.print()">
            <i class="fas fa-print"></i> พิมพ์เอกสาร
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> ปิด
        </button>
    </div>

    <div class="container">
        <!-- Header Section -->
        <div class="header">
            
        </div>

        <!-- PO Information Section -->
        <div class="po-info-section">
            <div class="po-info-grid">
                <div class="po-info-box-L">
                    <div class="info-row">
                        <span class="info-value" style="margin-left: 60px;"><strong>{{ $po->header->SupNo }} : {{ $po->header->SupName }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-value" style="margin-left: 20px;">
                            {{ $po->header->AddressSup }}<br>
                            <span class="info-value">{{ $po->header->Province }} {{ $po->header->ZipCode }}</span>
                        </span>
                    </div>
                    @if($po->header->ContractSup)
                    <div class="info-row" style="margin-left: 20px;">
                        <span class="info-value">{{ $po->header->ContractSup }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value" style="margin-left: 85px; line-height: 1;">
                            @if($po->header->CreditTerm == 0)
                                เงินสด
                            @else
                                {{ number_format($po->header->CreditTerm, 0) }} วัน
                            @endif
                        </span>
                    </div>
                    @endif                   
                </div>
                
                <div class="po-info-box-R">
                    <div class="info-row">
                        <span class="info-value" style="text-align: right; margin-right: 80px;"><strong>{{ $po->header->DocNo }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-value" style="text-align: right; margin-right: 80px;">{{ date('d/m/Y', strtotime($po->header->DateNo)) }}</span>
                    </div>                    
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="items-section">
            <table class="items-table">
                <tbody>
                    @foreach($po->details as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->ProductNo }}</td>
                        <td>{{ $item->ProductName }}</td>
                        <td class="text-center">{{ number_format($item->QTY, 0) }}</td>
                        <td class="text-center">{{ $item->Unit }}</td>
                        <td class="text-right">{{ $item->ShipDate ? date('d/m/Y', strtotime($item->ShipDate)) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        

        @if($po->header->Remember || $po->header->Note)
        <!-- Notes Section -->
        <div class="notes-section">
            @if($po->header->Remember)
                <div class="note-item">
                    <span class="note-label"></span> {{ $po->header->Remember }}
                </div>
            @endif
            @if($po->header->Note)
                <div class="note-item">
                    <span class="note-label"></span> {{ $po->header->Note }}
                </div>
            @endif
        </div>
        @endif

        <!-- Approval Section -->
        <div class="approval-section">            
            <div class="approval-grid">
                @for($level = 1; $level <= 3; $level++)
                    @php
                        $approval = $approvals->where('approval_level', $level)->first();
                    @endphp
                    <div class="approval-level {{ $approval ? 'approved' : 'pending' }}">
                        
                        
                        <div class="signature-area">
                            @if($approval && $approval->has_signature && $approval->signature_base64)
                                <img src="{{ $approval->signature_base64 }}" alt="ลายเซ็น" class="signature-image">
                            @else
                                <div class="signature-placeholder">
                                    @if($approval)
                                        ไม่มีลายเซ็นดิจิทัล
                                    @else
                                        รออนุมัติ
                                    @endif
                                </div>                                
                            @endif
                        </div>
                        @if($approval && $approval->formatted_date)
                            <div class="approval-date">{{ $approval->formatted_date }}</div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-row">เอกสารถูกสร้างเมื่อ: {{ $generated_at->format('d/m/Y H:i:s') }} ลายเซ็นดิจิทัลมีผลตามกฎหมาย</div>
        </div>
    </div>

    <!-- JavaScript สำหรับการพิมพ์ -->
    <script>
        // Auto focus สำหรับการพิมพ์
        document.addEventListener('DOMContentLoaded', function() {
            // ถ้าเป็น popup window ให้พิมพ์อัตโนมัติ
            if (window.opener) {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        });
        
        // Handle การปิดหน้าต่างหลังพิมพ์
        window.addEventListener('afterprint', function() {
            if (window.opener) {
                window.close();
            }
        });
    </script>
</body>
</html>