<?php
// app/Models/User.php (อัปเดตเพิ่มเติม)

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
        'approval_level' => 'integer',
    ];

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

    // ========== NEW: Digital Signature Relationships ==========
    public function signatures()
    {
        return $this->hasMany(UserSignature::class, 'user_id');
    }

    public function activeSignatures()
    {
        return $this->hasMany(UserSignature::class, 'user_id')->where('is_active', true);
    }

    public function currentSignature()
    {
        return $this->hasOne(UserSignature::class, 'user_id')
            ->where('is_active', true)
            ->latest();
    }

    // ========== NEW: Signature Helper Methods ==========
    public function hasActiveSignature()
    {
        return $this->activeSignatures()->exists();
    }

    public function getActiveSignature()
    {
        return $this->activeSignatures()->latest()->first();
    }

    public function getSignatureUrl()
    {
        $signature = $this->getActiveSignature();
        return $signature ? $signature->signature_url : null;
    }

    public function getSignaturePath()
    {
        $signature = $this->getActiveSignature();
        return $signature ? $signature->signature_full_path : null;
    }

    // ========== NEW: Bulk Approval Helper ==========
    public function canBulkApprove()
    {
        return $this->approval_level >= 1; // ทุก level สามารถทำ bulk approve ได้
    }

    public function getApprovalLevelName()
    {
        switch ($this->approval_level) {
            case 1: return 'User Level';
            case 2: return 'Manager Level';
            case 3: return 'GM Level';
            case 99: return 'Admin Level';
            default: return 'Unknown Level';
        }
    }

    public function getRoleBadgeClass()
    {
        switch ($this->role) {
            case 'admin': return 'bg-danger';
            case 'gm': return 'bg-success';
            case 'manager': return 'bg-warning';
            case 'user': return 'bg-primary';
            default: return 'bg-secondary';
        }
    }

    // ========== NEW: Statistics Methods ==========
    public function getApprovalStats()
    {
        return [
            'total_approvals' => $this->approvals()->count(),
            'approved_count' => $this->approvals()->where('approval_status', 'approved')->count(),
            'rejected_count' => $this->approvals()->where('approval_status', 'rejected')->count(),
            'bulk_approvals' => $this->approvals()->where('approval_method', 'bulk')->count(),
            'this_month_approvals' => $this->approvals()
                ->whereMonth('approval_date', now()->month)
                ->whereYear('approval_date', now()->year)
                ->count(),
        ];
    }
}