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

    protected static array $inMemoryCache = [];

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
        if (isset(self::$inMemoryCache[$documentType])) {
            return self::$inMemoryCache[$documentType];
        }

        $defaults = self::defaultPrefixes();
        $prefix = $defaults[$documentType] ?? substr($documentType, 0, 3);

        $seq = self::firstOrCreate(
            ['document_type' => $documentType],
            [
                'prefix' => $prefix,
                'next_number' => 1,
                'padding' => 4,
                'is_active' => true,
            ]
        );

        return self::$inMemoryCache[$documentType] = $seq;
    }

    public static function clearLocalCache(?string $documentType = null): void
    {
        if ($documentType) {
            unset(self::$inMemoryCache[$documentType]);
        } else {
            self::$inMemoryCache = [];
        }
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
        self::clearLocalCache($documentType);

        return $formatted;
    }
}
