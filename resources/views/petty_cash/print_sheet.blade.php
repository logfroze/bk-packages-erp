<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Daily Petty Cash Sheet — {{ $date }} — BK Packages</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 20px; line-height: 1.4; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-end; }
        .factory-title { font-size: 18px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title { font-size: 13px; font-weight: 600; color: #475569; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-table td { padding: 4px 8px; font-size: 11px; }
        table.ledger { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.ledger th, table.ledger td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.ledger th { background: #f1f5f9; font-weight: 600; text-transform: uppercase; font-size: 10px; color: #334155; }
        .text-right { text-align: right; }
        .num { font-family: "Courier New", monospace; font-weight: 600; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-box { width: 200px; border-top: 1px dashed #64748b; text-align: center; padding-top: 6px; font-size: 11px; font-weight: 600; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 8px;">
        <button id="btn-petty-img" onclick="downloadPettyImage()" style="padding: 8px 16px; background: #4338ca; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
            Download Image (PNG)
        </button>
        <button onclick="window.print()" style="padding: 8px 16px; background: #0f172a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
            Print / Save as PDF
        </button>
    </div>

    <div id="petty-cash-card" style="background: white; padding: 10px;">
    <div class="header">
        <div>
            <div class="factory-title">BK PACKAGES FACTORY</div>
            <div style="font-size: 11px; color: #64748b;">Plot 12-B, Industrial Area, Okara, Punjab | Ph: +92 300 0000000</div>
        </div>
        <div style="text-align: right;">
            <div class="doc-title">DAILY PETTY CASH & SAFE LEDGER</div>
            <div style="font-size: 11px; color: #0f172a; font-weight: bold;">Date: {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</div>
        </div>
    </div>

    @php
        $totalIn = $transactions->whereIn('transaction_type', ['CASH_IN', 'COUNTER_SALE'])->sum('amount');
        $totalOut = $transactions->where('transaction_type', 'CASH_OUT')->sum('amount');
    @endphp

    <table class="meta-table" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
        <tr>
            <td><strong>Total Cash Inflow Today:</strong></td>
            <td class="num text-right" style="color: #059669; font-size: 13px;">Rs. {{ number_format($totalIn, 2) }}</td>
            <td style="border-left: 1px solid #e2e8f0;"><strong>Total Cash Outflow Today:</strong></td>
            <td class="num text-right" style="color: #dc2626; font-size: 13px;">Rs. {{ number_format($totalOut, 2) }}</td>
            <td style="border-left: 1px solid #e2e8f0;"><strong>Closing Cash in Safe:</strong></td>
            <td class="num text-right" style="font-size: 14px; font-weight: bold; color: #0f172a;">Rs. {{ number_format($closingBalance, 2) }}</td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width: 70px;">Time</th>
                <th style="width: 140px;">Category</th>
                <th>Description / Narration</th>
                <th style="width: 130px;">Received From / Paid To</th>
                <th style="width: 90px;" class="text-right">Inflow (Rs.)</th>
                <th style="width: 90px;" class="text-right">Outflow (Rs.)</th>
                <th style="width: 100px;" class="text-right">Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td style="color: #64748b;">{{ $t->created_at->format('h:i A') }}</td>
                    <td style="font-weight: 600;">{{ str_replace('_', ' ', $t->category) }}</td>
                    <td>{{ $t->description }}</td>
                    <td>{{ $t->spent_by_or_paid_to ?: $t->recorded_by }}</td>
                    <td class="text-right num" style="color: #059669;">
                        {{ in_array($t->transaction_type, ['CASH_IN', 'COUNTER_SALE']) ? number_format($t->amount, 2) : '—' }}
                    </td>
                    <td class="text-right num" style="color: #dc2626;">
                        {{ $t->transaction_type === 'CASH_OUT' ? number_format($t->amount, 2) : '—' }}
                    </td>
                    <td class="text-right num" style="font-weight: bold;">
                        {{ number_format($t->running_balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">
                        No petty cash transactions recorded for {{ $date }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($transactions->isNotEmpty())
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="4" class="text-right">Daily Totals:</td>
                <td class="text-right num" style="color: #059669;">Rs. {{ number_format($totalIn, 2) }}</td>
                <td class="text-right num" style="color: #dc2626;">Rs. {{ number_format($totalOut, 2) }}</td>
                <td class="text-right num">Rs. {{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="signatures">
        <div class="sig-box">
            Mirza Sohail (Accountant)
            <div style="font-size: 9px; color: #94a3b8; font-weight: normal; margin-top: 2px;">Prepared By</div>
        </div>
        <div class="sig-box">
            Factory Cashier / Safe Custodian
            <div style="font-size: 9px; color: #94a3b8; font-weight: normal; margin-top: 2px;">Verified By</div>
        </div>
        <div class="sig-box">
            Managing Director (BK Packages)
            <div style="font-size: 9px; color: #94a3b8; font-weight: normal; margin-top: 2px;">Approved By</div>
        </div>
    </div>
    </div>

    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script>
    async function downloadPettyImage() {
        const btn = document.getElementById('btn-petty-img');
        const origText = btn.innerHTML;
        btn.innerHTML = 'Rendering...';
        btn.disabled = true;

        try {
            const card = document.getElementById('petty-cash-card');
            const canvas = await html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            const link = document.createElement('a');
            link.download = 'BK_PettyCash_{{ $date }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error('Image capture error:', e);
            alert('Could not render image. Please use Print / Save as PDF.');
        } finally {
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
