# Phase 1.1: Database Design & Laravel Setup

## 🎯 สิ่งที่เราจะทำในขั้นตอนนี้
1. สร้าง Laravel Project ใหม่
2. ติดตั้ง SQL Server Driver
3. สร้าง Database Schema สำหรับ Modern Database (SQL Server 2022)
4. ตั้งค่า Multiple Database Connections
5. สร้าง Migration Files

---

## 1️⃣ สร้าง Laravel Project

### 1.1 เปิด Command Prompt/Terminal แล้วรันคำสั่ง:
```bash
composer create-project laravel/laravel purchase-system
cd purchase-system
```

### 1.2 ติดตั้ง SQL Server Driver สำหรับ Laravel:
```bash
composer require doctrine/dbal
```

---

## 2️⃣ แก้ไขไฟล์ config/database.php

เปิดไฟล์ `config/database.php` แล้วแก้ไขส่วน `connections` ให้เป็นดังนี้:

```php
<?php

return [
    'default' => env('DB_CONNECTION', 'modern'),

    'connections' => [
        
        // Modern Database (SQL Server 2022) - สำหรับระบบใหม่
        'modern' => [
            'driver' => 'sqlsrv',
            'host' => env('MODERN_DB_HOST', '192.168.2.128'),
            'port' => env('MODERN_DB_PORT', '1433'),
            'database' => env('MODERN_DB_DATABASE', 'Romar128'),
            'username' => env('MODERN_DB_USERNAME', 'sa'),
            'password' => env('MODERN_DB_PASSWORD', 'rt@123'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', true),
        ],

        // Legacy Database (SQL Server 2008) - อ่านอย่างเดียว
        'legacy' => [
            'driver' => 'sqlsrv',
            'host' => env('LEGACY_DB_HOST', '192.168.2.2'),
            'port' => env('LEGACY_DB_PORT', '1433'),
            'database' => env('LEGACY_DB_DATABASE', 'Romar1'),
            'username' => env('LEGACY_DB_USERNAME', 'sa'),
            'password' => env('LEGACY_DB_PASSWORD', 'rt@123'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', true),
        ],

    ],
];
```

---

## 3️⃣ แก้ไขไฟล์ .env

เปิดไฟล์ `.env` แล้วแก้ไขส่วน Database Configuration:

```env
APP_NAME="Purchase System"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Modern Database (SQL Server 2022) - ระบบใหม่
DB_CONNECTION=modern
MODERN_DB_HOST=192.168.2.128
MODERN_DB_PORT=1433
MODERN_DB_DATABASE=Romar128
MODERN_DB_USERNAME=sa
MODERN_DB_PASSWORD=rt@123

# Legacy Database (SQL Server 2008) - ระบบเก่า (Read Only)
LEGACY_DB_HOST=192.168.2.2
LEGACY_DB_PORT=1433
LEGACY_DB_DATABASE=Romar1
LEGACY_DB_USERNAME=sa
LEGACY_DB_PASSWORD=rt@123

# Database Encryption Settings
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

---

## 4️⃣ สร้าง Database Schema สำหรับ Modern Database

เรามาสร้าง Migration Files สำหรับตารางที่จำเป็น:

### 4.1 สร้าง Migration สำหรับ Users Table:
```bash
php artisan make:migration create_users_table
```

แก้ไขไฟล์ `database/migrations/xxxx_xx_xx_create_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('full_name', 100);
            $table->string('email', 100)->nullable();
            $table->enum('role', ['admin', 'user', 'manager', 'gm'])->default('user');
            $table->integer('approval_level')->default(1); // 1=user, 2=manager, 3=gm, 99=admin
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('users');
    }
};
```

### 4.2 สร้าง Migration สำหรับ PO Approvals:
```bash
php artisan make:migration create_po_approvals_table
```

แก้ไขไฟล์ `database/migrations/xxxx_xx_xx_create_po_approvals_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('po_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('po_docno', 50); // อ้างอิงจาก Legacy System
            $table->unsignedBigInteger('approver_id');
            $table->integer('approval_level'); // 1=user, 2=manager, 3=gm
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('approval_date')->nullable();
            $table->text('approval_note')->nullable();
            $table->decimal('po_amount', 15, 2)->nullable(); // เก็บยอดเงิน PO สำหรับการแจ้งเตือน
            $table->timestamps();

            $table->foreign('approver_id')->references('id')->on('users');
            $table->index(['po_docno', 'approval_level']); // Index สำหรับค้นหา
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('po_approvals');
    }
};
```

### 4.3 สร้าง Migration สำหรับ PO Prints:
```bash
php artisan make:migration create_po_prints_table
```

แก้ไขไฟล์ `database/migrations/xxxx_xx_xx_create_po_prints_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('po_prints', function (Blueprint $table) {
            $table->id();
            $table->string('po_docno', 50);
            $table->unsignedBigInteger('printed_by');
            $table->string('print_type', 20)->default('pdf'); // pdf, excel
            $table->timestamps();

            $table->foreign('printed_by')->references('id')->on('users');
            $table->index('po_docno');
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('po_prints');
    }
};
```

### 4.4 สร้าง Migration สำหรับ Notifications:
```bash
php artisan make:migration create_notifications_table
```

แก้ไขไฟล์ `database/migrations/xxxx_xx_xx_create_notifications_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ผู้ที่จะได้รับการแจ้งเตือน
            $table->string('type', 50); // approval_required, approval_completed
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // ข้อมูลเพิ่มเติม เช่น po_docno, amount
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('notifications');
    }
};
```

---

## 5️⃣ รัน Migration และทดสอบ Connection

### 5.1 รัน Migration:
```bash
php artisan migrate --database=modern
```

### 5.2 สร้างไฟล์ทดสอบการเชื่อมต่อ Database

สร้างไฟล์ `routes/test.php` เพื่อทดสอบ:

```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// ทดสอบการเชื่อมต่อ Modern Database
Route::get('/test-modern-db', function() {
    try {
        $result = DB::connection('modern')->select('SELECT @@VERSION as version');
        return response()->json([
            'status' => 'success',
            'database' => 'modern',
            'version' => $result[0]->version
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'modern',
            'message' => $e->getMessage()
        ]);
    }
});

// ทดสอบการเชื่อมต่อ Legacy Database
Route::get('/test-legacy-db', function() {
    try {
        $result = DB::connection('legacy')->select('SELECT @@VERSION as version');
        return response()->json([
            'status' => 'success',
            'database' => 'legacy',
            'version' => $result[0]->version
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'legacy',
            'message' => $e->getMessage()
        ]);
    }
});

// ทดสอบดึงข้อมูล PO จาก Legacy Database
Route::get('/test-po-data', function() {
    try {
        $pos = DB::connection('legacy')->select("
            SELECT TOP 5 
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                h.NETAMT as NetAmout
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            WHERE h.APPSTS <> 'C'
            ORDER BY h.DOCDAT DESC
        ");
        
        return response()->json([
            'status' => 'success',
            'count' => count($pos),
            'data' => $pos
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});
```

### 5.3 เพิ่ม Route ทดสอบใน web.php:

แก้ไขไฟล์ `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// เพิ่ม routes ทดสอบ
require __DIR__.'/test.php';
```

---

## 6️⃣ ขั้นตอนการทดสอบ

### 6.1 เริ่ม Laravel Development Server:
```bash
php artisan serve
```

### 6.2 ทดสอบการเชื่อมต่อใน Browser:

1. **ทดสอบ Modern Database**: http://localhost:8000/test-modern-db
2. **ทดสอบ Legacy Database**: http://localhost:8000/test-legacy-db  
3. **ทดสอบข้อมูล PO**: http://localhost:8000/test-po-data

---

## ✅ ผลลัพธ์ที่ควรได้รับ

หาก Phase 1.1 สำเร็จ คุณจะได้:

1. ✅ Laravel Project ที่สามารถเชื่อมต่อ 2 Database ได้
2. ✅ Database Schema ที่รองรับ Approval Workflow แบบลำดับ
3. ✅ Notification System พื้นฐาน
4. ✅ การทดสอบเบื้องต้นผ่าน

---

## 📋 Checklist สำหรับ Phase 1.1

- [ ] สร้าง Laravel Project สำเร็จ
- [ ] ติดตั้ง SQL Server Driver สำเร็จ
- [ ] แก้ไขไฟล์ config/database.php
- [ ] แก้ไขไฟล์ .env
- [ ] สร้าง Migration Files ครบทั้ง 4 ไฟล์
- [ ] รัน Migration สำเร็จ
- [ ] ทดสอบ Modern Database Connection สำเร็จ
- [ ] ทดสอบ Legacy Database Connection สำเร็จ
- [ ] ทดสอบดึงข้อมูล PO จาก Legacy Database สำเร็จ

**กรุณาทำทุกขั้นตอนให้เรียบร้อยแล้วแจ้งผลลัพธ์กลับมา จากนั้นเราจะไป Phase 1.2 ต่อครับ!**