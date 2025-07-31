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