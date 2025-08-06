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
            font-size: 18px;
            line-height: 1.4;
            color: #333;
            background: white;
            margin: 0;
            padding: 20px;
        }
        
        /* Print Media Styles */
        @media print {
            body {
                margin: 0;
                padding: 15mm;
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
            padding: 20px;
        }
        
        /* Header Styles */
        .header {
            border-bottom: 3px solid #333;
            padding-top: 5px;
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
            color: #333;
        }
        
        .company-name-en {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .company-info {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }
        
        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        
        .document-title-en {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }
        
        /* PO Info Section */
        .po-info-section {
            margin-bottom: 5px;
        }
        
        .po-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 5px;
        }
        
        .po-info-box {
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fafafa;
        }
        
        .info-title {
            font-weight: bold;
            font-size: 16px;
            color: #333;
            margin-bottom: 12px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 5px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 2px solid #333;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            color: #333;
            border-bottom: 2px solid #333;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        /* Summary Section */
        .summary-section {
            margin-bottom: 10px;
            margin-top: 5px;
            display: flex;
            justify-content: flex-end;
        }
        
        .summary-table {
            width: 350px;
            border-collapse: collapse;
            border: 2px solid #333;
        }
        
        .summary-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        
        .summary-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 180px;
            text-align: right;
        }
        
        .summary-table .amount {
            text-align: right;
            width: 170px;
            font-family: monospace;
        }
        
        .summary-table .total-row td {
            background-color: #e3f2fd;
            font-weight: bold;
            border-top: 2px solid #333;
            font-size: 16px;
        }
        
        /* Notes Section */
        .notes-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-left: 4px solid #007bff;
        }
        
        .notes-section .note-item {
            margin-bottom: 8px;
        }
        
        .notes-section .note-label {
            font-weight: bold;
            color: #555;
        }
        
        /* Approval Section */
        .approval-section {
            margin-top: 5px;
            border: 3px solid #333;
            padding: 5px;
        }
        
        .approval-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .approval-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .approval-level {
            border: 2px solid #ddd;
            padding: 15px;
            text-align: center;
            min-height: 200px;
            background-color: #fafafa;
        }
        
        .approval-level.approved {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        
        .approval-level.pending {
            border-color: #ffc107;
            background-color: #fffbf0;
        }
        
        .approval-level-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }
        
        .signature-area {
            height: 80px;
            margin: 15px 0;
            border: 2px dashed #ccc;
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
            color: #999;
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
            color: #333;
        }
        
        .approver-username {
            color: #666;
            margin-bottom: 3px;
        }
        
        .approval-date {
            color: #666;
            margin-bottom: 5px;
        }
        
        .approval-note {
            font-style: italic;
            color: #555;
            font-size: 11px;
            margin-top: 8px;
            padding: 5px;
            background-color: #f0f0f0;
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
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 11px;
            color: #666;
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
            
            .summary-section {
                justify-content: stretch;
                padding-bottom: 5px;
                margin-bottom: 5px;
            }
            
            .summary-table {
                width: 100%;
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
            <!-- Left Column - Document Title -->
            <div class="document-info">
                <div class="document-title">ใบสั่งซื้อ</div>
                <div class="document-title-en">PURCHASE ORDER</div>
            </div>
            
            <!-- Right Column - Company Info -->
            <div class="company-info-section">
                @if(isset($company['logo_path']) && $company['logo_path'])
                    <div class="company-logo">
                        <img src="{{ $company['logo_path'] }}" alt="Company Logo">
                    </div>
                @endif
                
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-name-en">{{ $company['name_en'] }}</div>
                <div class="company-info">
                    {{ $company['address'] }}<br>
                    โทร: {{ $company['tel'] }} | แฟกซ์: {{ $company['fax'] }} | อีเมล: {{ $company['email'] }}
                    @if($company['tax_id'])
                        <br>เลขประจำตัวผู้เสียภาษี: {{ $company['tax_id'] }}
                    @endif
                </div>
            </div>
        </div>

        <!-- PO Information Section -->
        <div class="po-info-section">
            <div class="po-info-grid">
                <div class="po-info-box">
                    <div class="info-title">ข้อมูลใบสั่งซื้อ</div>
                    <div class="info-row">
                        <span class="info-label">เลขที่ใบสั่งซื้อ:</span>
                        <span class="info-value"><strong>{{ $po->header->DocNo }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">วันที่:</span>
                        <span class="info-value">{{ date('d/m/Y', strtotime($po->header->DateNo)) }}</span>
                    </div>
                    @if($po->header->DocRef)
                    {{-- <div class="info-row">
                        <span class="info-label">อ้างอิง:</span>
                        <span class="info-value">{{ $po->header->DocRef }}</span>
                    </div> --}}
                    @endif
                    <div class="info-row">
                        <span class="info-label">เครดิต:</span>
                        <span class="info-value">{{ $po->header->CreditTerm }} วัน</span>
                    </div>
                </div>
                
                <div class="po-info-box">
                    <div class="info-title">ข้อมูลผู้ขาย</div>
                    <div class="info-row">
                        <span class="info-label">ชื่อผู้ขาย:</span>
                        <span class="info-value"><strong>{{ $po->header->SupName }}</strong></span>
                    </div>
                    {{-- <div class="info-row">
                        <span class="info-label">รหัสผู้ขาย:</span>
                        <span class="info-value">{{ $po->header->SupNo }}</span>
                    </div> --}}
                    <div class="info-row">
                        <span class="info-label">ที่อยู่:</span>
                        <span class="info-value">
                            {{ $po->header->AddressSup }}<br>
                            {{ $po->header->Province }} {{ $po->header->ZipCode }}
                        </span>
                    </div>
                    @if($po->header->ContactName)
                    <div class="info-row">
                        <span class="info-label">ผู้ติดต่อ:</span>
                        <span class="info-value">{{ $po->header->ContactName }}</span>
                    </div>
                    @endif
                    @if($po->header->Phone)
                    <div class="info-row">
                        <span class="info-label">โทรศัพท์:</span>
                        <span class="info-value">{{ $po->header->Phone }}</span>
                    </div>
                    @endif
                    @if($po->header->FAX)
                    <div class="info-row">
                        <span class="info-label">แฟกซ์:</span>
                        <span class="info-value">{{ $po->header->FAX }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="items-section">
            <div class="section-title">รายการสินค้า</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%">ลำดับ</th>
                        <th style="width: 15%">รหัสสินค้า</th>
                        <th style="width: 35%">รายละเอียดสินค้า</th>
                        <th style="width: 10%">จำนวน</th>
                        <th style="width: 8%">หน่วย</th>
                        <th style="width: 12%">ราคาต่อหน่วย</th>
                        <th style="width: 15%">จำนวนเงิน</th>
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
                        <td class="text-right"><strong>{{ number_format($item->LineTotal, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="label">ยอดรวม:</td>
                    <td class="amount">{{ number_format($po->header->TotalAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">ส่วนลด:</td>
                    <td class="amount">{{ number_format($po->header->DiscountAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">ภาษีมูลค่าเพิ่ม 7%:</td>
                    <td class="amount">{{ number_format($po->header->VatAmount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">ยอดสุทธิ:</td>
                    <td class="amount">{{ number_format($po->header->NetAmount, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- @if($po->header->Remember || $po->header->Note)
        <!-- Notes Section -->
        <div class="notes-section">
            <div class="section-title">หมายเหตุ</div>
            @if($po->header->Remember)
                <div class="note-item">
                    <span class="note-label">บันทึก:</span> {{ $po->header->Remember }}
                </div>
            @endif
            @if($po->header->Note)
                <div class="note-item">
                    <span class="note-label">หมายเหตุ:</span> {{ $po->header->Note }}
                </div>
            @endif
        </div>
        @endif --}}

        <!-- Approval Section -->
        <div class="approval-section">
            <div class="approval-title">ลายเซ็นอนุมัติ</div>
            
            <div class="approval-grid">
                @for($level = 1; $level <= 3; $level++)
                    @php
                        $approval = $approvals->where('approval_level', $level)->first();
                    @endphp
                    <div class="approval-level {{ $approval ? 'approved' : 'pending' }}">
                        <div class="approval-level-title">
                            ระดับที่ {{ $level }} - 
                            @if($level == 1) การอนุมัติระดับผู้ใช้
                            @elseif($level == 2) การอนุมัติระดับผู้จัดการ
                            @else การอนุมัติสุดท้าย (MD)
                            @endif
                        </div>
                        
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
                        
                        @if($approval)
                            <div class="approver-info">
                                <div class="approver-name">{{ $approval->approver_name }}</div>
                                <div class="approver-username">({{ $approval->approver_username }})</div>
                                <div class="approval-date">{{ $approval->formatted_date }}</div>
                                @if($approval->approval_note)
                                    <div class="approval-note">{{ $approval->approval_note }}</div>
                                @endif
                            </div>
                            <div class="status-badge status-approved">อนุมัติแล้ว</div>
                        @else
                            <div class="approver-info">
                                <div class="status-badge status-pending">รออนุมัติ</div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-row">เอกสารถูกสร้างเมื่อ: {{ $generated_at->format('d/m/Y H:i:s') }}</div>
            <div class="footer-row">ระบบ: Purchase Approval System v2.0</div>
            <div class="footer-row">เอกสารนี้สร้างโดยระบบคอมพิวเตอร์ ลายเซ็นดิจิทัลมีผลตามกฎหมาย</div>
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