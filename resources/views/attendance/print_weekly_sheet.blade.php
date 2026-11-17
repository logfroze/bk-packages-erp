<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Thursday Labor Payroll Sheet ({{ $cycleStart }} to {{ $cycleEnd }}) — BK Packages</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 11px; color: #0f172a; margin: 20px; line-height: 1.3; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }
        .factory-title { font-size: 18px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title { font-size: 12px; font-weight: 600; color: #475569; }
        table.payroll { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        table.payroll th, table.payroll td { border: 1px solid #94a3b8; padding: 6px 8px; text-align: left; }
        table.payroll th { background: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 9.5px; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .num { font-family: "Courier New", monospace; font-weight: 600; }
        .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-box { width: 180px; border-top: 1px dashed #64748b; text-align: center; padding-top: 5px; font-size: 10px; font-weight: 600; }
        .thumb-col { width: 110px; height: 35px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0f172a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
            Print Wage Roster / Slips
        </button>
    </div>

    <div class="header">
        <div>
            <div class="factory-title">BK PACKAGES FACTORY</div>
            <div style="font-size: 10px; color: #64748b;">Industrial Area, Okara, Punjab | Weekly Thursday Labor Payment Sheet</div>
        </div>
        <div style="text-align: right;">
            <div class="doc-title">WEEKLY WAGE ROSTER & RECEIPT SLIP</div>
            <div style="font-size: 11px; color: #0f172a; font-weight: bold;">
                Cycle: {{ \Carbon\Carbon::parse($cycleStart)->format('d M') }} – {{ \Carbon\Carbon::parse($cycleEnd)->format('d M Y') }} (6 Days)
            </div>
        </div>
    </div>

    <table class="payroll">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">Sr.</th>
                <th style="width: 65px;">Emp Code</th>
                <th>Worker Name</th>
                <th>Role / Machine</th>
                <th style="width: 70px;">Wage Type</th>
                <th style="width: 45px;" class="text-center">Days</th>
                <th style="width: 45px;" class="text-center">OT (Hr)</th>
                <th style="width: 75px;" class="text-right">Gross Wage</th>
                <th style="width: 75px;" class="text-right">Advance</th>
                <th style="width: 85px;" class="text-right">Net Payable</th>
                <th class="thumb-col text-center">Worker Signature / Thumb</th>
            </tr>
        </thead>
        <tbody>
            @php $sr = 1; @endphp
            @foreach($summary as $row)
                @php $w = $row['worker']; @endphp
                <tr>
                    <td class="text-center" style="color: #64748b;">{{ $sr++ }}</td>
                    <td class="num" style="font-size: 10px;">{{ $w->worker_code }}</td>
                    <td style="font-weight: 700;">{{ $w->full_name }}</td>
                    <td style="color: #334155;">{{ $w->role }}</td>
                    <td style="font-size: 9.5px; color: #64748b;">{{ str_replace('_', ' ', $w->wage_type) }}</td>
                    <td class="text-center num">{{ $row['present_days'] }}</td>
                    <td class="text-center num">{{ $row['overtime_hours'] ?: '—' }}</td>
                    <td class="text-right num">Rs. {{ number_format($row['gross_wage']) }}</td>
                    <td class="text-right num" style="color: #b91c1c;">{{ $row['unpaid_advances'] > 0 ? '-' . number_format($row['unpaid_advances']) : '—' }}</td>
                    <td class="text-right num" style="font-weight: 800; font-size: 12px;">Rs. {{ number_format($row['net_payable']) }}</td>
                    <td class="thumb-col" style="background: #fafafa;"></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: 800; font-size: 11px;">
                <td colspan="7" class="text-right uppercase">Total Thursday Cash Distributed:</td>
                <td class="text-right num">Rs. {{ number_format($summary->sum('gross_wage')) }}</td>
                <td class="text-right num" style="color: #b91c1c;">-Rs. {{ number_format($summary->sum('unpaid_advances')) }}</td>
                <td class="text-right num" style="font-size: 13px;">Rs. {{ number_format($totalNet) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-box">
            Mirza Sohail
            <div style="font-size: 9px; color: #64748b; font-weight: normal; margin-top: 2px;">Factory Accountant</div>
        </div>
        <div class="sig-box">
            Labor Foreman / Supervisor
            <div style="font-size: 9px; color: #64748b; font-weight: normal; margin-top: 2px;">Attendance Verified</div>
        </div>
        <div class="sig-box">
            Managing Director (BK Packages)
            <div style="font-size: 9px; color: #64748b; font-weight: normal; margin-top: 2px;">Cash Disbursed & Signed</div>
        </div>
    </div>
</body>
</html>
