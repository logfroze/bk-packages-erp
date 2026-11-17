<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'prefix',
        'next_number',
        'padding',
        'suffix',
        'is_active',
    ];

    public static function defaultPrefixes(): array
    {
        return [
            'DELIVERY_CHALLAN' => 'DC',
            'GATE_PASS' => 'GP',
            'SALES_INVOICE' => 'INV',
        ];
    }

    public static function getOrCreateSequence(string $documentType): self
    {
        $defaults = self::defaultPrefixes();
        $prefix = $defaults[$documentType] ?? substr($documentType, 0, 3);

        return self::firstOrCreate(
            ['document_type' => $documentType],
            [
                'prefix' => $prefix,
                'next_number' => 1,
                'padding' => 4,
                'is_active' => true,
            ]
        );
    }

    public static function previewNextNumber(string $documentType): string
    {
        $seq = self::getOrCreateSequence($documentType);
        $num = str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
        $suffix = $seq->suffix ? '-' . $seq->suffix : '';

        return "{$seq->prefix}-{$num}{$suffix}";
    }

    public static function generateNextNumber(string $documentType): string
    {
        $seq = self::getOrCreateSequence($documentType);
        $formatted = self::previewNextNumber($documentType);
        $seq->increment('next_number');

        return $formatted;
    }
}
