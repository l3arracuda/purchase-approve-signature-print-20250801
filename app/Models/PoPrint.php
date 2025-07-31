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