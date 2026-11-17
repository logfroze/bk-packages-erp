<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        return Cache::remember("doc_seq_{$documentType}", 10, function () use ($documentType) {
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
        });
    }

    public function preview(): string
    {
        $num = str_pad((string) $this->next_number, (int) $this->padding, '0', STR_PAD_LEFT);
        $suffix = $this->suffix ? '-' . $this->suffix : '';

        return "{$this->prefix}-{$num}{$suffix}";
    }

    public static function previewNextNumber(string $documentType): string
    {
        $seq = self::getOrCreateSequence($documentType);
        return $seq->preview();
    }

    public static function generateNextNumber(string $documentType): string
    {
        $seq = self::getOrCreateSequence($documentType);
        $formatted = $seq->preview();
        $seq->increment('next_number');
        Cache::forget("doc_seq_{$documentType}");

        return $formatted;
    }
}
