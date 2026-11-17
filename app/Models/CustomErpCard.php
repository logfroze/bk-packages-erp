<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomErpCard extends Model
{
    protected $fillable = [
        'page_key',
        'title',
        'subtitle',
        'card_type',
        'rows_count',
        'columns_count',
        'column_headers',
        'grid_data',
        'field_pairs',
        'badge_text',
        'badge_color',
        'sort_order',
    ];

    protected $casts = [
        'column_headers' => 'array',
        'grid_data' => 'array',
        'field_pairs' => 'array',
        'rows_count' => 'integer',
        'columns_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function forPage(string $pageKey)
    {
        return self::where('page_key', $pageKey)->orderBy('sort_order')->orderBy('id')->get();
    }
}
