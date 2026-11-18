<?php

namespace App\Http\Controllers;

use App\Models\CustomErpCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomCardController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (is_string($request->column_headers)) {
            $request->merge(['column_headers' => json_decode($request->column_headers, true)]);
        }
        if (is_string($request->grid_data)) {
            $request->merge(['grid_data' => json_decode($request->grid_data, true)]);
        }
        if (is_string($request->field_pairs)) {
            $request->merge(['field_pairs' => json_decode($request->field_pairs, true)]);
        }

        if ($request->has('field_labels') && $request->has('field_values')) {
            $labels = (array) $request->field_labels;
            $values = (array) $request->field_values;
            $pairs = [];
            foreach ($labels as $idx => $label) {
                if (trim($label) !== '') {
                    $pairs[trim($label)] = $values[$idx] ?? '';
                }
            }
            $request->merge(['field_pairs' => $pairs]);
        }

        if ($request->card_type === 'GRID_TABLE') {
            $request->merge(['card_type' => 'DYNAMIC_GRID']);
        } elseif ($request->card_type === 'FIELD_PAIRS') {
            $request->merge(['card_type' => 'KEY_VALUE']);
        }

        $validated = $request->validate([
            'page_key' => 'required|string|max:60',
            'title' => 'required|string|max:150',
            'subtitle' => 'nullable|string|max:250',
            'card_type' => 'required|in:DYNAMIC_GRID,KEY_VALUE,METRIC,GRID_TABLE,FIELD_PAIRS',
            'rows_count' => 'nullable|integer|min:1|max:50',
            'columns_count' => 'nullable|integer|min:1|max:20',
            'column_headers' => 'nullable|array',
            'grid_data' => 'nullable|array',
            'field_pairs' => 'nullable|array',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:30',
        ]);

        $card = CustomErpCard::create([
            'page_key' => $validated['page_key'],
            'title' => trim($validated['title']),
            'subtitle' => !empty($validated['subtitle']) ? trim($validated['subtitle']) : null,
            'card_type' => $validated['card_type'] === 'GRID_TABLE' ? 'DYNAMIC_GRID' : ($validated['card_type'] === 'FIELD_PAIRS' ? 'KEY_VALUE' : $validated['card_type']),
            'rows_count' => (int) ($validated['rows_count'] ?? 3),
            'columns_count' => (int) ($validated['columns_count'] ?? 4),
            'column_headers' => $validated['column_headers'] ?? null,
            'grid_data' => $validated['grid_data'] ?? null,
            'field_pairs' => $validated['field_pairs'] ?? null,
            'badge_text' => $validated['badge_text'] ?? null,
            'badge_color' => $validated['badge_color'] ?? 'blue',
            'sort_order' => CustomErpCard::where('page_key', $validated['page_key'])->count() + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'card' => $card]);
        }

        return back()->with('success', "Custom card '{$card->title}' created successfully.");
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $card = CustomErpCard::findOrFail($id);

        if (is_string($request->column_headers)) {
            $request->merge(['column_headers' => json_decode($request->column_headers, true)]);
        }
        if (is_string($request->grid_data)) {
            $request->merge(['grid_data' => json_decode($request->grid_data, true)]);
        }
        if (is_string($request->field_pairs)) {
            $request->merge(['field_pairs' => json_decode($request->field_pairs, true)]);
        }

        if ($request->has('field_labels') && $request->has('field_values')) {
            $labels = (array) $request->field_labels;
            $values = (array) $request->field_values;
            $pairs = [];
            foreach ($labels as $idx => $label) {
                if (trim($label) !== '') {
                    $pairs[trim($label)] = $values[$idx] ?? '';
                }
            }
            $request->merge(['field_pairs' => $pairs]);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'subtitle' => 'nullable|string|max:250',
            'rows_count' => 'nullable|integer|min:1|max:50',
            'columns_count' => 'nullable|integer|min:1|max:20',
            'column_headers' => 'nullable|array',
            'grid_data' => 'nullable|array',
            'field_pairs' => 'nullable|array',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:30',
        ]);

        $card->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'card' => $card]);
        }

        return back()->with('success', "Custom card '{$card->title}' updated successfully.");
    }

    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $card = CustomErpCard::findOrFail($id);
        $title = $card->title;
        $card->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Card deleted']);
        }

        return back()->with('success', "Custom card '{$title}' removed.");
    }
}
