<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function numbering(): View
    {
        $sequences = [
            'DELIVERY_CHALLAN' => DocumentSequence::getOrCreateSequence('DELIVERY_CHALLAN'),
            'GATE_PASS' => DocumentSequence::getOrCreateSequence('GATE_PASS'),
            'SALES_INVOICE' => DocumentSequence::getOrCreateSequence('SALES_INVOICE'),
        ];

        return view('settings.numbering', compact('sequences'));
    }

    public function updateNumbering(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:DELIVERY_CHALLAN,GATE_PASS,SALES_INVOICE',
            'prefix' => 'required|string|max:20',
            'next_number' => 'required|integer|min:1',
            'padding' => 'required|integer|min:1|max:10',
            'suffix' => 'nullable|string|max:20',
        ]);

        $seq = DocumentSequence::getOrCreateSequence($validated['document_type']);
        $seq->update([
            'prefix' => strtoupper(trim($validated['prefix'])),
            'next_number' => (int) $validated['next_number'],
            'padding' => (int) $validated['padding'],
            'suffix' => $validated['suffix'] ? trim($validated['suffix']) : null,
        ]);

        return back()->with('success', "{$validated['document_type']} numbering format updated successfully (Next: " . DocumentSequence::previewNextNumber($validated['document_type']) . ").");
    }
}
