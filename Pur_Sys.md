# 📋 แผนการพัฒนาระบบ Purchase System

## 🎯 ภาพรวมของระบบ
- **Legacy Database**: SQL Server 2008 (ROMA2000) - Read Only
- **Modern Database**: SQL Server 2022 (S_SERVER008) - Full Control
- **Framework**: Laravel (แนะนำ) หรือ PHP Framework อื่น
- **Frontend**: Web-based Interface

---

## 📅 Phase 1: การเตรียมพื้นฐานระบบ (1-2 วัน)

### 1.1 Database Design & Setup
**Modern Database (SQL Server 2022)**
```sql
-- สร้างตารางสำหรับระบบใหม่
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) UNIQUE NOT NULL,
    password NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(100) NOT NULL,
    email NVARCHAR(100),
    role NVARCHAR(20) NOT NULL, -- admin, user, manager, md
    is_active BIT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);

CREATE TABLE po_approvals (
    id INT IDENTITY(1,1) PRIMARY KEY,
    po_docno NVARCHAR(50) NOT NULL, -- อ้างอิงจาก Legacy System
    approver_id INT NOT NULL,
    approval_status NVARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected
    approval_date DATETIME,
    approval_note NVARCHAR(500),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (approver_id) REFERENCES users(id)
);

CREATE TABLE po_prints (
    id INT IDENTITY(1,1) PRIMARY KEY,
    po_docno NVARCHAR(50) NOT NULL,
    printed_by INT NOT NULL,
    printed_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (printed_by) REFERENCES users(id)
);
```

### 1.2 Project Setup
- สร้าง Laravel Project ใหม่
- ติดตั้ง Package สำหรับ SQL Server Connection
- Configuration Database Connections (2 databases)
- Setup Authentication System

---

## 📅 Phase 2: ระบบ Authentication (1-2 วัน)

### 2.1 User Management System
- สร้าง User Registration/Login
- สร้าง Role-based Access Control
- User Roles: Admin, User, Manager, MD
- Password Hashing & Security

### 2.2 Permission Matrix
| Role | View PO | Approve PO | Print PO | User Management |
|------|---------|------------|----------|------------------|
| User | ✅ | ❌ | ✅ | ❌ |
| Manager | ✅ | ✅ | ✅ | ❌ |
| MD | ✅ | ✅ | ✅ | ❌ |
| Admin | ✅ | ✅ | ✅ | ✅ |

---

## 📅 Phase 3: การเชื่อมต่อและอ่านข้อมูล PO (2-3 วัน)

### 3.1 Database Connection Setup
```php
// config/database.php
'legacy' => [
    'driver' => 'sqlsrv',
    'host' => '192.168.2.2',
    'port' => '1433',
    'database' => 'Romar1',
    'username' => 'sa',
    'password' => 'rt@123',
],
'modern' => [
    'driver' => 'sqlsrv',
    'host' => '192.168.2.128',
    'port' => '1433',
    'database' => 'Romar128',
    'username' => 'sa',
    'password' => 'rt@123',
]
```

### 3.2 PO Data Model & Service
- สร้าง Model สำหรับอ่านข้อมูล PO
- สร้าง Service Class สำหรับจัดการข้อมูล
- Implement SQL Query ที่ให้มา
- สร้าง Data Transfer Object (DTO)

### 3.3 ทดสอบการอ่านข้อมูล
- สร้าง Test Route เพื่อทดสอบการอ่านข้อมูล
- ตรวจสอบความถูกต้องของข้อมูล
- Performance Testing

---

## 📅 Phase 4: ระบบ Approval (3-4 วัน)

### 4.1 PO List Interface
- สร้างหน้า List PO พร้อม Checkbox
- Pagination & Search Function
- Filter by Status, Date Range, Supplier
- Mass Selection สำหรับ Approve หลายรายการ

### 4.2 PO Detail Interface
- สร้างหน้า Detail ของแต่ละ PO
- แสดงข้อมูลครบถ้วนตาม SQL Query
- ปุ่ม Approve/Reject ในหน้า Detail

### 4.3 Approval Logic
- Single Approval (จากหน้า Detail)
- Bulk Approval (จากหน้า List)
- Approval History & Audit Trail
- Email Notification (Optional)

---

## 📅 Phase 5: ระบบ Print (2-3 วัน)

### 5.1 Print Template Design
- สร้าง Template สำหรับพิมพ์ PO
- รองรับข้อมูลจาก Legacy Database
- CSS สำหรับการพิมพ์

### 5.2 Print Functionality
- PDF Generation
- Print History Tracking
- Print Preview
- Batch Printing

---

## 📅 Phase 6: UI/UX Enhancement (2-3 วัน)

### 6.1 Responsive Design
- Mobile-friendly Interface
- Bootstrap หรือ Tailwind CSS
- Loading States & Animations

### 6.2 User Experience
- Dashboard Overview
- Quick Actions
- Status Indicators
- Search & Filter Enhancement

---

## 📅 Phase 7: Testing & Deployment (2-3 วัน)

### 7.1 Testing
- Unit Testing
- Integration Testing
- User Acceptance Testing
- Performance Testing

### 7.2 Deployment
- Server Setup
- Database Migration
- Security Configuration
- Backup Strategy

---

## 🚀 Quick Start Development Order

### สัปดาห์ที่ 1: พื้นฐานระบบ
1. **วันที่ 1-2**: Database Design + Project Setup
2. **วันที่ 3-4**: Authentication System
3. **วันที่ 5-7**: PO Data Connection & Reading

### สัปดาห์ที่ 2: ฟีเจอร์หลัก
1. **วันที่ 1-4**: Approval System
2. **วันที่ 5-7**: Print System + UI Polish

### สัปดาห์ที่ 3: ปรับปรุงและ Deploy
1. **วันที่ 1-3**: Testing & Bug Fixes
2. **วันที่ 4-5**: Deployment & User Training

---

## 🔧 เครื่องมือและ Technology Stack แนะนำ

### Backend
- **Framework**: Laravel 10.x
- **Database**: SQL Server Driver for Laravel
- **Authentication**: Laravel Sanctum หรือ Session-based

### Frontend
- **CSS Framework**: Bootstrap 5 หรือ Tailwind CSS
- **JavaScript**: jQuery + Alpine.js (หรือ Vue.js)
- **Icons**: Font Awesome หรือ Heroicons

### Development Tools
- **IDE**: VS Code + PHP Extensions
- **Database Tool**: SQL Server Management Studio
- **Version Control**: Git
- **Testing**: PHPUnit

---

## ⚠️ ข้อควรระวัง

1. **Database Connection**: ทดสอบ Connection อย่างละเอียด
2. **Data Integrity**: ไม่แก้ไขข้อมูลใน Legacy Database
3. **Performance**: ใช้ Caching สำหรับข้อมูลที่อ่านบ่อย
4. **Security**: Validate Input + SQL Injection Prevention
5. **Backup**: สำรองข้อมูลก่อนทำ Testing

คุณต้องการให้ผมเริ่มจากขั้นตอนไหนก่อนครับ? หรือมีคำถามเพิ่มเติมเกี่ยวกับแผนการพัฒนานี้?