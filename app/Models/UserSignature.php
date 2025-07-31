<?php
// app/Models/UserSignature.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserSignature extends Model
{
    use HasFactory;

    protected $connection = 'modern';
    protected $table = 'user_signatures';

    protected $fillable = [
        'user_id',
        'signature_name',
        'signature_path',
        'signature_data',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helper Methods
    public function getSignatureUrlAttribute()
    {
        if ($this->signature_path) {
            return Storage::url($this->signature_path);
        }
        return null;
    }

    public function getSignatureFullPathAttribute()
    {
        if ($this->signature_path) {
            return storage_path('app/public/' . $this->signature_path);
        }
        return null;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Static Methods
    public static function getActiveSignatureForUser($userId)
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public static function createUserSignature($userId, $signatureName, $signaturePath, $signatureData = null)
    {
        // Deactivate old signatures
        static::where('user_id', $userId)->update(['is_active' => false]);

        // Create new signature
        return static::create([
            'user_id' => $userId,
            'signature_name' => $signatureName,
            'signature_path' => $signaturePath,
            'signature_data' => $signatureData,
            'is_active' => true,
        ]);
    }

    // File Operations
    public function deleteSignatureFile()
    {
        if ($this->signature_path && Storage::exists('public/' . $this->signature_path)) {
            Storage::delete('public/' . $this->signature_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Auto delete file when model is deleted
        static::deleting(function ($signature) {
            $signature->deleteSignatureFile();
        });
    }
}