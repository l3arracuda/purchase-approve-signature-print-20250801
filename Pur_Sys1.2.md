# Phase 1.2: User Seeder & Basic Authentication

## 🎯 สิ่งที่เราจะทำในขั้นตอนนี้
1. สร้าง User Model สำหรับ Modern Database
2. สร้าง Seeder สำหรับ User ทดสอบ
3. ติดตั้งและตั้งค่า Laravel UI สำหรับ Authentication
4. ปรับแต่ง Authentication ให้ใช้ Modern Database
5. สร้างหน้า Login/Dashboard เบื้องต้น

---

## 1️⃣ สร้าง User Model

### 1.1 สร้าง User Model ใหม่:
```bash
php artisan make:model User --force
```

แก้ไขไฟล์ `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ใช้ Modern Database
    protected $connection = 'modern';
    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'email',
        'role',
        'approval_level',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ใช้ username แทน email สำหรับ login
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    // Helper Methods สำหรับ Role Management
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isGM()
    {
        return $this->role === 'gm';
    }

    public function canApprove($level)
    {
        return $this->approval_level >= $level;
    }

    // Relationships
    public function approvals()
    {
        return $this->hasMany(PoApproval::class, 'approver_id');
    }

    public function prints()
    {
        return $this->hasMany(PoPrint::class, 'printed_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
```

---

## 2️⃣ สร้าง Models เพิ่มเติม

### 2.1 สร้าง PoApproval Model:
```bash
php artisan make:model PoApproval
```

แก้ไขไฟล์ `app/Models/PoApproval.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoApproval extends Model
{
    use HasFactory;

    protected $connection = 'modern';
    protected $table = 'po_approvals';

    protected $fillable = [
        'po_docno',
        'approver_id',
        'approval_level',
        'approval_status',
        'approval_date',
        'approval_note',
        'po_amount',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
        'po_amount' => 'decimal:2',
    ];

    // Relationships
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Helper Methods
    public function isPending()
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved()
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected()
    {
        return $this->approval_status === 'rejected';
    }
}
```

### 2.2 สร้าง PoPrint Model:
```bash
php artisan make:model PoPrint
```

แก้ไขไฟล์ `app/Models/PoPrint.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoPrint extends Model
{
    use HasFactory;

    protected $connection = 'modern';
    protected $table = 'po_prints';

    protected $fillable = [
        'po_docno',
        'printed_by',
        'print_type',
    ];

    // Relationships
    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
```

---

## 3️⃣ สร้าง Database Seeder

### 3.1 สร้าง UserSeeder:
```bash
php artisan make:seeder UserSeeder
```

แก้ไขไฟล์ `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'full_name' => 'System Administrator',
            'email' => 'admin@company.com',
            'role' => 'admin',
            'approval_level' => 99,
            'is_active' => true,
        ]);

        // GM User
        User::create([
            'username' => 'gm001',
            'password' => Hash::make('gm123'),
            'full_name' => 'General Manager',
            'email' => 'gm@company.com',
            'role' => 'gm',
            'approval_level' => 3,
            'is_active' => true,
        ]);

        // Manager User
        User::create([
            'username' => 'manager001',
            'password' => Hash::make('manager123'),
            'full_name' => 'Department Manager',
            'email' => 'manager@company.com',
            'role' => 'manager',
            'approval_level' => 2,
            'is_active' => true,
        ]);

        // Regular User
        User::create([
            'username' => 'user001',
            'password' => Hash::make('user123'),
            'full_name' => 'Regular User',
            'email' => 'user@company.com',
            'role' => 'user',
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Test Users เพิ่มเติม
        User::create([
            'username' => 'manager002',
            'password' => Hash::make('manager123'),
            'full_name' => 'Second Manager',
            'email' => 'manager2@company.com',
            'role' => 'manager',
            'approval_level' => 2,
            'is_active' => true,
        ]);

        User::create([
            'username' => 'user002',
            'password' => Hash::make('user123'),
            'full_name' => 'Second User',
            'email' => 'user2@company.com',
            'role' => 'user',
            'approval_level' => 1,
            'is_active' => true,
        ]);
    }
}
```

### 3.2 แก้ไข DatabaseSeeder:

แก้ไขไฟล์ `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
```

### 3.3 รัน Seeder:
```bash
php artisan db:seed --database=modern
```

---

## 4️⃣ ติดตั้ง Laravel UI สำหรับ Authentication

### 4.1 ติดตั้ง Laravel UI:
```bash
composer require laravel/ui
php artisan ui bootstrap --auth
npm install && npm run build
```

### 4.2 ปรับแต่ง Auth Configuration:

แก้ไขไฟล์ `config/auth.php`:

```php
<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

---

## 5️⃣ ปรับแต่งการ Login ให้ใช้ Username

### 5.1 แก้ไข LoginController:

แก้ไขไฟล์ `app/Http/Controllers/Auth/LoginController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // ใช้ username แทน email
    public function username()
    {
        return 'username';
    }

    // ปรับแต่งการ validation
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    // เพิ่มการตรวจสอบ is_active
    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true]
        );
    }
}
```

---

## 6️⃣ ปรับแต่งหน้า Login

### 6.1 แก้ไขไฟล์ Login View:

แก้ไขไฟล์ `resources/views/auth/login.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Purchase System - Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Username') }}</label>

                            <div class="col-md-6">
                                <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autocomplete="username" autofocus>

                                @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Test Users Information -->
                    <div class="mt-4">
                        <small class="text-muted">
                            Test Users:<br>
                            Admin: admin / admin123<br>
                            GM: gm001 / gm123<br>
                            Manager: manager001 / manager123<br>
                            User: user001 / user123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## 7️⃣ สร้างหน้า Dashboard เบื้องต้น

### 7.1 สร้าง DashboardController:
```bash
php artisan make:controller DashboardController
```

แก้ไขไฟล์ `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // ดึงข้อมูล PO เบื้องต้นจาก Legacy Database (เฉพาะ PP%)
        $poQuery = "
            SELECT TOP 10 
                h.DOCDAT as DateNo, 
                h.DOCNO as DocNo, 
                h.RefPoNo as DocRef, 
                h.SUPCD as SupNo,
                s.SUPNAM as SupName, 
                h.NETAMT as NetAmout,
                h.APPSTS as AppStatus
            FROM [Romar1].[dbo].[POC_POH] h
            JOIN [Romar1].[dbo].[APC_SUP] s ON h.SUPCD = s.SUPCD
            WHERE h.APPSTS <> 'C' AND h.DOCNO LIKE 'PP%'
            ORDER BY h.DOCDAT DESC
        ";

        try {
            $recentPOs = DB::connection('legacy')->select($poQuery);
        } catch (\Exception $e) {
            $recentPOs = [];
        }

        // สถิติเบื้องต้น
        $stats = [
            'total_pos' => count($recentPOs),
            'user_role' => $user->role,
            'approval_level' => $user->approval_level,
        ];

        return view('dashboard', compact('user', 'recentPOs', 'stats'));
    }
}
```

### 7.2 สร้างไฟล์ Dashboard View:

สร้างไฟล์ `resources/views/dashboard.blade.php`:

```blade
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
                                    <small>Phase 1.2 Complete</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent POs Preview -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Purchase Orders (PP% only)</h5>
                        </div>
                        <div class="card-body">
                            @if(count($recentPOs) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>PO Number</th>
                                                <th>Reference</th>
                                                <th>Supplier</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentPOs as $po)
                                            <tr>
                                                <td>{{ $po->DateNo }}</td>
                                                <td><strong>{{ $po->DocNo }}</strong></td>
                                                <td>{{ $po->DocRef }}</td>
                                                <td>{{ $po->SupName }}</td>
                                                <td class="text-end">{{ number_format($po->NetAmout, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $po->AppStatus }}</span>
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

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h5>Quick Actions</h5>
                        <div class="btn-group" role="group">
                            @if($user->approval_level >= 1)
                                <a href="#" class="btn btn-outline-primary">View All POs</a>
                            @endif
                            @if($user->approval_level >= 2)
                                <a href="#" class="btn btn-outline-success">Pending Approvals</a>
                            @endif
                            @if($user->isAdmin())
                                <a href="#" class="btn btn-outline-warning">User Management</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## 8️⃣ ปรับแต่ง Routes

### 8.1 แก้ไข routes/web.php:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['register' => false]); // ปิดการ register

Route::get('/home', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Test routes (เฉพาะในการพัฒนา)
require __DIR__.'/test.php';
```

---

## 9️⃣ ขั้นตอนการทดสอบ

### 9.1 รัน Migration และ Seeder:
```bash
php artisan migrate:refresh --database=modern --seed
```

### 9.2 เริ่ม Server และทดสอบ:
```bash
php artisan serve
```

### 9.3 ทดสอบการ Login:

เข้าที่ `http://localhost:8000` แล้วทดสอบ Login ด้วย:

1. **Admin**: username = `admin`, password = `admin123`
2. **GM**: username = `gm001`, password = `gm123`  
3. **Manager**: username = `manager001`, password = `manager123`
4. **User**: username = `user001`, password = `user123`

---

## ✅ ผลลัพธ์ที่ควรได้รับ

หาก Phase 1.2 สำเร็จ คุณจะได้:

1. ✅ ระบบ Login ที่ใช้ Username
2. ✅ User Role-based Access Control
3. ✅ Dashboard ที่แสดงข้อมูล PO (เฉพาะ PP%)
4. ✅ การเชื่อมต่อระหว่าง Auth และ Database ทั้ง 2 ตัว
5. ✅ UI เบื้องต้นสำหรับระบบ

---

## 📋 Checklist สำหรับ Phase 1.2

- [ ] สร้าง User Model สำเร็จ
- [ ] สร้าง PoApproval และ PoPrint Models สำเร็จ
- [ ] รัน UserSeeder สำเร็จ (ได้ 6 test users)
- [ ] ติดตั้ง Laravel UI สำเร็จ
- [ ] Login ด้วย Username ได้สำเร็จ
- [ ] Dashboard แสดงข้อมูล PO (PP%) ได้
- [ ] ทดสอบ Login ทุก Role สำเร็จ
- [ ] หน้า Dashboard แสดงข้อมูลถูกต้อง

**กรุณาทำให้เรียบร้อยแล้วแจ้งผลลัพธ์กลับมา จากนั้นเราจะไป Phase 2: PO Management System ต่อครับ!**

## PHP Fatal error:  Trait "Laravel\Sanctum\HasApiTokens" not found

อ๊ะ! เจอปัญหาแล้วครับ Laravel Sanctum ยังไม่ได้ติดตั้ง แต่เราใช้ HasApiTokens trait
มาแก้กันเลยครับ มี 2 วิธี:
วิธีที่ 1: ลบ HasApiTokens ออก (แนะนำสำหรับตอนนี้)
แก้ไขไฟล์ app/Models/User.php:
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ใช้ Modern Database
    protected $connection = 'modern';
    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'email',
        'role',
        'approval_level',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ใช้ username แทน email สำหรับ login
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    // Helper Methods สำหรับ Role Management
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isGM()
    {
        return $this->role === 'gm';
    }

    public function canApprove($level)
    {
        return $this->approval_level >= $level;
    }

    // Relationships
    public function approvals()
    {
        return $this->hasMany(PoApproval::class, 'approver_id');
    }

    public function prints()
    {
        return $this->hasMany(PoPrint::class, 'printed_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

### Class "App\Providers\RouteServiceProvider" not found
- แก้ไขไฟล์ app/Http/Controllers/Auth/LoginController.php

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    // เปลี่ยนจาก RouteServiceProvider::HOME เป็น /dashboard
    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // ใช้ username แทน email
    public function username()
    {
        return 'username';
    }

    // ปรับแต่งการ validation
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    // เพิ่มการตรวจสอบ is_active
    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true]
        );
    }

    // Override redirectTo method for dynamic redirect
    protected function redirectTo()
    {
        return '/dashboard';
    }
}



- แก้ไขไฟล์ app/Http/Controllers/Auth/RegisterController.php

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    // เปลี่ยนจาก RouteServiceProvider::HOME เป็น /dashboard
    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}