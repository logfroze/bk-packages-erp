<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaVaultItem extends Model
{
    public const CATEGORIES = [
        'ALL' => 'All Media & Bills',
        'LOCAL_PARTY_BILL' => 'Local Shop / Party Bills',
        'GROCERY_FOOD' => 'Daily Food & Grocery Packs',
        'PETROL_FUEL' => 'Petrol & Generator Fuel',
        'HARDWARE_TOOLS' => 'Hardware & Machine Tools',
        'GATE_CHALLAN' => 'Gate Weigh / Delivery Challans',
        'RECEIPT_VOUCHER' => 'Payment Receipt Vouchers',
        'GENERAL' => 'General Receipts / Photos',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'file_path',
        'file_name',
        'mime_type',
        'file_size_bytes',
        'linked_amount',
        'linked_party_or_shop',
        'source_module',
        'source_id',
        'notes',
    ];

    protected $casts = [
        'linked_amount' => 'decimal:2',
        'file_size_bytes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'LOCAL_PARTY_BILL' => 'Local Shop / Party Bill',
            'GROCERY_FOOD' => 'Daily Food & Grocery Pack',
            'PETROL_FUEL' => 'Petrol & Generator Fuel',
            'HARDWARE_TOOLS' => 'Hardware & Machine Tools',
            'GATE_CHALLAN' => 'Gate Weigh / Delivery Challan',
            'RECEIPT_VOUCHER' => 'Payment Receipt Voucher',
            default => 'General Document / Photo',
        };
    }

    public function categoryBadgeColor(): string
    {
        return match ($this->category) {
            'LOCAL_PARTY_BILL' => 'bg-amber-100 text-amber-800 border-amber-200',
            'GROCERY_FOOD' => 'bg-orange-100 text-orange-800 border-orange-200',
            'PETROL_FUEL' => 'bg-blue-100 text-blue-800 border-blue-200',
            'HARDWARE_TOOLS' => 'bg-purple-100 text-purple-800 border-purple-200',
            'GATE_CHALLAN' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
