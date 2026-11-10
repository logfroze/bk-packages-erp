<?php

namespace App\Services;

use App\Models\CartonProduct;
use App\Models\ConversionRun;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Worker;

class FactoryAiCopilotService
{
    public static function getSnapshotForUser(User $user): array
    {
        $isFinancial = $user->isFinancialAuthorized();

        $parties = Party::orderBy('name')->get()->map(function ($p) use ($isFinancial) {
            $item = [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'city' => $p->city,
            ];
            if ($isFinancial) {
                $item['current_balance'] = (float) $p->current_balance;
                $item['balance_note'] = $p->current_balance >= 0 ? 'Receivable (Lena Hai)' : 'Payable (Dena Hai)';
            }
            return $item;
        })->values()->all();

        $cartons = CartonProduct::orderBy('box_title')->get()->map(function ($c) use ($isFinancial) {
            $item = [
                'id' => $c->id,
                'box_title' => $c->box_title,
                'ply_type' => $c->ply_type,
                'client_id' => $c->client_id,
                'stock_pieces' => $c->stock_pieces,
            ];
            if ($isFinancial) {
                $item['unit_selling_price'] = (float) $c->unit_selling_price;
            }
            return $item;
        })->values()->all();

        $recentGates = GateRegister::with('party')
            ->latest('time_in')
            ->take(10)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'gate_pass_number' => $g->gate_pass_number,
                'movement_type' => $g->movement_type,
                'vehicle_number' => $g->vehicle_number,
                'driver_name' => $g->driver_name,
                'party_name' => $g->party->name ?? null,
                'material_summary' => $g->material_summary,
                'total_pieces' => $g->total_pieces,
                'net_weight_kg' => $g->net_weight_kg,
                'status' => $g->status,
            ])->values()->all();

        $snapshot = [
            'today_date' => now()->toDateString(),
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
                'financial_access' => $isFinancial,
            ],
            'parties' => $parties,
            'carton_products' => $cartons,
            'recent_gate_passes' => $recentGates,
        ];

        if ($isFinancial) {
            $cashIn = Voucher::where('voucher_type', 'RECEIPT_VOUCHER')->where('payment_mode', 'CASH')->sum('total_amount');
            $cashOut = Voucher::whereIn('voucher_type', ['PAYMENT_VOUCHER', 'EXPENSE_VOUCHER'])->where('payment_mode', 'CASH')->sum('total_amount');

            $snapshot['financial_summary'] = [
                'total_receivables' => (float) Party::where('type', 'CUSTOMER')->where('current_balance', '>', 0)->sum('current_balance'),
                'total_payables' => (float) abs(Party::where('type', 'SUPPLIER')->where('current_balance', '<', 0)->sum('current_balance')),
                'cash_in_hand' => max(0, (float) ($cashIn - $cashOut)),
            ];

            $snapshot['raw_materials'] = RawMaterial::all(['id', 'name', 'category', 'total_stock_qty', 'unit_of_measure'])->toArray();
            $snapshot['paper_reels'] = PaperReel::where('remaining_weight_kg', '>', 0)->get(['id', 'reel_number', 'raw_material_id', 'remaining_weight_kg', 'status'])->toArray();
            $snapshot['production_batches'] = ProductionBatch::with('cartonProduct:id,box_title', 'client:id,name')->latest()->take(8)->get()->map(fn ($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'box' => $b->cartonProduct->box_title ?? null,
                'client' => $b->client->name ?? null,
                'target_quantity' => $b->target_quantity,
                'produced_quantity' => $b->produced_quantity,
                'status' => $b->status,
            ])->values()->all();

            $snapshot['conversion_runs'] = ConversionRun::latest()->take(5)->get([
                'id', 'run_code', 'machine_name', 'total_rolls_produced', 'rolls_remaining', 'actual_sheets_cut', 'sheets_remaining', 'actual_cartons_produced', 'status'
            ])->toArray();

            $snapshot['workers'] = Worker::where('is_active', true)->get(['id', 'full_name', 'role', 'wage_type', 'rate_amount'])->toArray();
            $snapshot['workers_present_today'] = DailyAttendance::whereDate('attendance_date', today())->where('status', 'PRESENT')->count();
            $snapshot['recent_vouchers'] = Voucher::with('party:id,name')->latest()->take(8)->get()->map(fn ($v) => [
                'voucher_number' => $v->voucher_number,
                'voucher_type' => $v->voucher_type,
                'party' => $v->party->name ?? 'Factory Expense',
                'amount' => (float) $v->total_amount,
                'mode' => $v->payment_mode,
                'date' => $v->voucher_date?->toDateString(),
            ])->values()->all();
        }

        return $snapshot;
    }

    public static function ask(User $user, string $message, array $history = []): array
    {
        $snapshot = self::getSnapshotForUser($user);
        $isFinancial = $user->isFinancialAuthorized();

        $allowedActionsDoc = $isFinancial ? <<<'ACTIONS'
Available Actions you may propose in `pending_action` when the user explicitly asks to add, record, log, or update data:

1. Record Cash/Cheque Payment Received or Paid, or Factory Expense (`/khata/voucher`):
   - payload keys: `voucher_type` (`RECEIPT_VOUCHER`, `PAYMENT_VOUCHER`, `SALES_INVOICE`, `EXPENSE_VOUCHER`), `party_id` (int or null for EXPENSE_VOUCHER), `total_amount` (number), `payment_mode` (`CASH`, `CHEQUE`, `ONLINE_BANK`, `CREDIT`), `reference_number` (optional string), `description` (required string).
2. Log Vehicle Gate Entry or Delivery Challan (`/gate-register`):
   - payload keys: `movement_type` (`OUTWARD_DISPATCH` or `INWARD_RAW_MATERIAL`), `party_id` (required int), `vehicle_number` (required string), `vehicle_type` (optional string), `driver_name` (required string), `driver_phone` (optional string), `carton_product_id` (optional int), `total_pieces` (optional int), `material_summary` (optional string), `create_invoice` (optional 1 or 0).
3. Record Weigh & Exit for an active Gate Pass (`/gate-register/{id}/weigh-exit`):
   - payload keys: `gross_weight_kg` (number), `tare_weight_kg` (number).
4. Start New Production Batch (`/production`):
   - payload keys: `carton_product_id` (required int), `target_quantity` (required int), `produced_quantity` (optional int default 0), `shift` (`SHIFT_A_DAY` or `SHIFT_B_NIGHT`), `current_machine` (`CORRUGATION_LINE_1`, `CORRUGATION_LINE_2`, `FLEXO_PRINT_SLOTTER`, `AUTO_STITCHER`).
5. Add Output Pieces to Existing Batch (`/production/{id}/output`):
   - payload keys: `add_pieces` (required int), `rejected_pieces` (optional int default 0).
6. Add New Party Customer/Supplier (`/khata/party`):
   - payload keys: `name` (required string), `type` (`CUSTOMER`, `SUPPLIER`, `TRANSPORTER`), `city` (required string), `phone` (required string), `opening_balance` (optional number).
7. Record Worker Cash Advance / Peshgi (`/attendance/advance`):
   - payload keys: `worker_id` (required int), `amount` (required number), `reason` (optional string).
ACTIONS
        : <<<'GATEACTIONS'
Available Actions (GATE_OPERATOR Role Only):
1. Log Vehicle Gate Entry (`/gate-register`):
   - payload keys: `movement_type` (`OUTWARD_DISPATCH` or `INWARD_RAW_MATERIAL`), `party_id` (required int), `vehicle_number` (required string), `vehicle_type` (optional string), `driver_name` (required string), `driver_phone` (optional string), `material_summary` (optional string), `total_pieces` (optional int).
2. Record Weigh & Exit for an active Gate Pass (`/gate-register/{id}/weigh-exit`):
   - payload keys: `gross_weight_kg` (number), `tare_weight_kg` (number).
Strictly refuse any requests about financial balances, Khata payments, salaries, or production details because this account has Gate Security permissions only.
GATEACTIONS;

        $systemPrompt = <<<PROMPT
You are the built-in AI Factory Assistant for **BK Packages ERP** (a corrugated carton manufacturing factory on Depalpur Road, Okara, Pakistan).
Your user is {$user->name} (Role: {$user->role}).

Guidelines:
1. Understand English, Urdu, and Roman Urdu (e.g., "Ahmed Ghee ka baqaya kitna hai?", "Irfan Pharma se 50 hazar cash mila", "gari ki entry karni hai"). Reply in the same language style, clearly and concisely.
2. Answer operational questions using ONLY the Live Factory Database Snapshot below. Format Pakistani Rupees as `Rs. X,XXX`. Never invent IDs or numbers.
3. Guide first-time users clearly when they ask "how to use" questions (mention exact sidebar menu names: Dashboard, Production, Material Conversion, Stock & Paper Reels, Gate Register, Party Khata, Worker Attendance, Day-End Reports).
4. Whenever the user asks to perform an action (add entry, record payment, log truck, add production output, etc.), populate `pending_action` with the exact `target_path` and `payload` using real `id`s from the snapshot so the user can click **Confirm & Save**. If a required detail (like vehicle number, driver name, or amount) is missing, set `pending_action` to `null` and politely ask for the missing field!
5. Always respond with valid JSON matching this exact schema:
{
  "reply": "Clear response message for the user",
  "pending_action": null OR {
    "title": "Short confirmation card heading",
    "summary_lines": ["Field 1: Value", "Field 2: Value"],
    "target_path": "/exact/route/path",
    "payload": { ... }
  }
}

{$allowedActionsDoc}

LIVE FACTORY DATABASE SNAPSHOT:
PROMPT;
        $systemPrompt .= "\n" . json_encode($snapshot);

        $contents = [];
        foreach (array_slice($history, -6) as $turn) {
            if (! empty($turn['role']) && ! empty($turn['text'])) {
                $contents[] = [
                    'role' => $turn['role'] === 'model' ? 'model' : 'user',
                    'parts' => [['text' => (string) $turn['text']]],
                ];
            }
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $raw = GeminiKeyPool::generateContent($contents, $systemPrompt, true);
        $decoded = self::parseJsonResponse($raw);

        // Enforce strict RBAC safety on any proposed action
        if (! empty($decoded['pending_action']) && ! $isFinancial) {
            $targetPath = $decoded['pending_action']['target_path'] ?? '';
            $isGate = $targetPath === '/gate-register' || preg_match('#^/gate-register/\d+/weigh-exit$#', $targetPath);
            if (! $isGate) {
                $decoded['pending_action'] = null;
            }
        }

        return [
            'reply' => $decoded['reply'] ?? 'Sorry, I could not process that request.',
            'pending_action' => $decoded['pending_action'] ?? null,
            'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        ];
    }

    private static function parseJsonResponse(?string $raw): array
    {
        if (empty($raw)) {
            return ['reply' => 'No response received from AI service.', 'pending_action' => null];
        }

        $clean = trim($raw);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $clean);
        }

        $parsed = json_decode($clean, true);
        if (is_array($parsed) && isset($parsed['reply'])) {
            return $parsed;
        }

        return ['reply' => $raw, 'pending_action' => null];
    }
}

