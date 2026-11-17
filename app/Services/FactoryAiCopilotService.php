<?php

namespace App\Services;

use App\Models\CartonProduct;
use App\Models\ConversionRun;
use App\Models\CorrugationRun;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\PettyCashTransaction;
use App\Models\PrintedTopSheet;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Worker;
use Illuminate\Support\Facades\Cache;

class FactoryAiCopilotService
{
    public static function clearSnapshotCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("ai_snapshot_v2_{$userId}_ADMIN");
            Cache::forget("ai_snapshot_v2_{$userId}_MANAGER");
            Cache::forget("ai_snapshot_v2_{$userId}_OWNER");
            Cache::forget("ai_snapshot_v2_{$userId}_GATE_OPERATOR");
            Cache::forget("ai_snapshot_v2_{$userId}_ACCOUNTANT");
        }
    }

    public static function getSnapshotForUser(User $user, bool $fresh = false): array
    {
        $cacheKey = "ai_snapshot_v2_{$user->id}_{$user->role}";
        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return self::buildSnapshot($user);
        });
    }

    private static function buildSnapshot(User $user): array
    {
        $isFinancial = $user->isFinancialAuthorized();
        $isFullAdmin = $user->isFullAdmin();
        $isManager = $user->isManager();
        $isOwner = $user->isOwner();

        $parties = Party::select(['id', 'name', 'type', 'classification', 'city', 'phone', 'current_balance'])
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($isFinancial) {
                $item = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => $p->type,
                    'classification' => $p->classification,
                    'city' => $p->city,
                    'phone' => $p->phone,
                ];
                if ($isFinancial) {
                    $item['current_balance'] = (float) $p->current_balance;
                    $item['balance_note'] = $p->current_balance >= 0 ? 'Receivable (Lena Hai)' : 'Payable (Dena Hai)';
                }
                return $item;
            })->values()->all();

        $cartons = CartonProduct::select(['id', 'box_title', 'ply_type', 'client_id', 'stock_pieces', 'unit_selling_price'])
            ->orderBy('box_title')
            ->get()
            ->map(function ($c) use ($isFinancial) {
                $item = [
                    'id' => $c->id,
                    'box_title' => $c->box_title,
                    'ply_type' => $c->ply_type,
                    'client_id' => $c->client_id,
                    'stock_pieces' => (int) $c->stock_pieces,
                ];
                if ($isFinancial) {
                    $item['unit_selling_price'] = (float) $c->unit_selling_price;
                }
                return $item;
            })->values()->all();

        $recentGates = GateRegister::with('party:id,name')
            ->select(['id', 'gate_pass_number', 'movement_type', 'vehicle_number', 'driver_name', 'party_id', 'material_summary', 'total_pieces', 'net_weight_kg', 'status', 'time_in', 'created_at'])
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
                'created_at' => $g->created_at?->toIso8601String(),
            ])->values()->all();

        $snapshot = [
            'today_date' => now()->toDateString(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'financial_access' => $isFinancial,
                'is_full_admin' => $isFullAdmin,
                'is_manager' => $isManager,
                'is_owner' => $isOwner,
            ],
            'parties' => $parties,
            'carton_products' => $cartons,
            'recent_gate_passes' => $recentGates,
        ];

        if ($isFinancial) {
            $voucherStats = Voucher::selectRaw("
                SUM(CASE WHEN voucher_type='RECEIPT_VOUCHER' AND payment_mode='CASH' THEN total_amount ELSE 0 END) as cash_in,
                SUM(CASE WHEN voucher_type IN ('PAYMENT_VOUCHER','EXPENSE_VOUCHER') AND payment_mode='CASH' THEN total_amount ELSE 0 END) as cash_out
            ")->first();

            $partyStats = Party::selectRaw("
                SUM(CASE WHEN type='CUSTOMER' AND current_balance > 0 THEN current_balance ELSE 0 END) as receivables,
                SUM(CASE WHEN type='SUPPLIER' AND current_balance < 0 THEN ABS(current_balance) ELSE 0 END) as payables
            ")->first();

            $pettySafeBalance = PettyCashTransaction::currentCashBalance();
            $pettyStats = PettyCashTransaction::whereDate('transaction_date', today())->selectRaw("
                SUM(CASE WHEN transaction_type='CASH_OUT' THEN amount ELSE 0 END) as cash_out,
                SUM(CASE WHEN transaction_type IN ('CASH_IN','COUNTER_SALE') THEN amount ELSE 0 END) as cash_in
            ")->first();

            $snapshot['financial_summary'] = [
                'total_receivables' => (float) ($partyStats->receivables ?? 0),
                'total_payables' => (float) ($partyStats->payables ?? 0),
                'ledger_cash_in_hand' => max(0, (float) (($voucherStats->cash_in ?? 0) - ($voucherStats->cash_out ?? 0))),
                'petty_cash_safe_balance' => (float) $pettySafeBalance,
                'today_petty_expenses' => (float) ($pettyStats->cash_out ?? 0),
                'today_petty_inflow' => (float) ($pettyStats->cash_in ?? 0),
            ];

            // 1. Printed Imports Context (Pre-printed outsourced sheets)
            $totalPrintedStockPcs = (int) PrintedTopSheet::sum('stock_pieces');
            $totalPrintedValuation = (float) (PrintedTopSheet::selectRaw('SUM(stock_pieces * unit_cost) as val')->value('val') ?? 0);
            $printedRecent = PrintedTopSheet::with(['supplier:id,name', 'cartonProduct:id,box_title'])
                ->select(['id', 'item_code', 'item_name', 'sourcing_city', 'stock_pieces', 'unit_cost', 'supplier_id', 'carton_product_id', 'minimum_threshold'])
                ->latest('id')
                ->take(10)
                ->get()
                ->map(fn ($pts) => [
                    'id' => $pts->id,
                    'item_code' => $pts->item_code,
                    'item_name' => $pts->item_name,
                    'sourcing_city' => $pts->sourcing_city,
                    'stock_pieces' => (int) $pts->stock_pieces,
                    'unit_cost' => (float) $pts->unit_cost,
                    'supplier' => $pts->supplier->name ?? null,
                    'carton_box' => $pts->cartonProduct->box_title ?? null,
                    'minimum_threshold' => (int) $pts->minimum_threshold,
                ])->values()->all();

            $snapshot['printed_imports'] = [
                'total_stock_pieces' => $totalPrintedStockPcs,
                'total_inventory_valuation_rs' => $totalPrintedValuation,
                'recent_items' => $printedRecent,
            ];

            // 2. Corrugations / Nali Runs Context
            $totalNalisProduced = (int) CorrugationRun::sum('nalis_produced');
            $totalSheetsProduced = (int) CorrugationRun::sum('completed_sheets_produced');
            $recentCorrugations = CorrugationRun::select(['id', 'run_code', 'board_type', 'deckle_size_inches', 'sheet_size', 'nalis_produced', 'completed_sheets_produced', 'operator_name', 'shift', 'created_at'])
                ->latest('id')
                ->take(8)
                ->get()
                ->map(fn ($cr) => [
                    'id' => $cr->id,
                    'run_code' => $cr->run_code,
                    'board_type' => $cr->board_type,
                    'deckle_size_inches' => (float) $cr->deckle_size_inches,
                    'sheet_size' => $cr->sheet_size,
                    'nalis_produced' => (int) $cr->nalis_produced,
                    'completed_sheets_produced' => (int) $cr->completed_sheets_produced,
                    'operator_name' => $cr->operator_name,
                    'shift' => $cr->shift,
                    'created_at' => $cr->created_at?->format('d M Y, H:i'),
                ])->values()->all();

            $snapshot['corrugation_runs'] = [
                'total_nalis_produced' => $totalNalisProduced,
                'total_sheets_produced' => $totalSheetsProduced,
                'recent_runs' => $recentCorrugations,
            ];

            // 3. Petty Cash Safe Transactions Context
            $recentPettyCash = PettyCashTransaction::select(['id', 'transaction_type', 'category', 'amount', 'spent_by_or_paid_to', 'description', 'transaction_date'])
                ->latest('id')
                ->take(8)
                ->get()
                ->map(fn ($pct) => [
                    'id' => $pct->id,
                    'type' => $pct->transaction_type,
                    'category' => $pct->category,
                    'amount' => (float) $pct->amount,
                    'spent_by_or_paid_to' => $pct->spent_by_or_paid_to,
                    'description' => $pct->description,
                    'date' => $pct->transaction_date?->toDateString(),
                ])->values()->all();

            $snapshot['petty_cash_transactions'] = $recentPettyCash;

            // 4. Day-End Roznamcha Live Indicators
            $gateStats = GateRegister::whereDate('time_in', today())->selectRaw("
                SUM(CASE WHEN movement_type='OUTWARD_DISPATCH' THEN 1 ELSE 0 END) as dispatch_count,
                SUM(CASE WHEN movement_type='OUTWARD_DISPATCH' THEN total_pieces ELSE 0 END) as dispatch_pieces,
                SUM(CASE WHEN movement_type='INWARD_RAW_MATERIAL' THEN 1 ELSE 0 END) as inward_count,
                SUM(CASE WHEN movement_type='INWARD_RAW_MATERIAL' THEN net_weight_kg ELSE 0 END) as inward_weight
            ")->first();

            $snapshot['today_roznamcha_overview'] = [
                'dispatches_count' => (int) ($gateStats->dispatch_count ?? 0),
                'dispatched_cartons_pieces' => (int) ($gateStats->dispatch_pieces ?? 0),
                'inwards_count' => (int) ($gateStats->inward_count ?? 0),
                'inward_raw_material_kg' => (float) ($gateStats->inward_weight ?? 0),
                'workers_present_today' => DailyAttendance::whereDate('attendance_date', today())->where('status', 'PRESENT')->count(),
            ];

            // Standard inventory and production snapshots
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
        $isFullAdmin = $user->isFullAdmin();
        $isManager = $user->isManager();
        $isOwner = $user->isOwner();

        // Build role-specific actionable permission instructions
        if ($isOwner) {
            $allowedActionsDoc = "ROLE: OWNER (Read-Only Overview)\n" .
                "The user is the Company Owner (bkpackges@gmail.com). He has complete visibility of all data, finances, profits, and stock, but STRICT READ-ONLY ACCESS.\n" .
                "STRICT RULE: Do NOT propose any write actions in `pending_action`. Always set `pending_action: null`.";
        } elseif ($isManager) {
            $allowedActionsDoc = "ROLE: MANAGER (Dr. Ghulam Abbas - Limited Write)\n" .
                "The Manager has permission to record daily petty cash expenses only.\n" .
                "Available Action:\n" .
                "1. Record Petty Cash Expense in Factory Safe (`/petty-cash`):\n" .
                "   - payload keys: `transaction_type` ('CASH_OUT'), `category` (one of: 'TEA_REFRESHMENT', 'PETROL_TRANSPORT', 'HARDWARE_PURCHASE', 'OFFICE_SUPPLIES', 'SPARE_PARTS', 'MISCELLANEOUS'), `amount` (number), `spent_by_or_paid_to` (string), `description` (required string).\n" .
                "For any other modification (e.g. Khata balances, customer editing, system settings), politely explain that Mirza Sohail (Admin) has direct authority for those operations.";
        } elseif ($isFullAdmin) {
            $allowedActionsDoc = "ROLE: FULL ADMIN (Mirza Sohail)\n" .
                "Available Actions you may propose in `pending_action` when the user explicitly asks to add, record, log, or update data:\n\n" .
                "1. Record Cash/Cheque Payment Received or Paid, or Ledger Expense (`/khata/voucher`):\n" .
                "   - payload keys: `voucher_type` ('RECEIPT_VOUCHER', 'PAYMENT_VOUCHER', 'SALES_INVOICE', 'EXPENSE_VOUCHER'), `party_id` (int or null for EXPENSE_VOUCHER), `total_amount` (number), `payment_mode` ('CASH', 'CHEQUE', 'ONLINE_BANK', 'CREDIT'), `reference_number` (optional string), `description` (required string).\n" .
                "2. Record Petty Cash Safe Transaction (`/petty-cash`):\n" .
                "   - payload keys: `transaction_type` ('CASH_IN', 'CASH_OUT', 'COUNTER_SALE'), `category` ('TEA_REFRESHMENT', 'PETROL_TRANSPORT', 'HARDWARE_PURCHASE', 'OFFICE_SUPPLIES', 'SPARE_PARTS', 'CHEMICAL_SALE', 'ROLL_SALE', 'OPENING_FLOAT', 'MISCELLANEOUS'), `amount` (number), `spent_by_or_paid_to` (optional string), `description` (required string).\n" .
                "3. Record Pre-Printed Top Sheet Import (`/production/printed-imports`):\n" .
                "   - payload keys: `item_code` (unique string e.g. 'PTS-101'), `item_name` (string), `sourcing_city` ('Faisalabad', 'Multan', 'Lahore', 'Rahim Yar Khan', 'Jumber'), `stock_pieces` (int), `minimum_threshold` (int), `unit_cost` (number), `supplier_id` (optional int), `carton_product_id` (optional int).\n" .
                "4. Record Corrugation / Nali Run (`/production/corrugations`):\n" .
                "   - payload keys: `run_code` (unique string e.g. 'CR-201'), `board_type` ('SINGLE_FACE', 'SINGLE_WALL', 'DOUBLE_WALL', 'TRIPLE_WALL'), `deckle_size_inches` (number between 15 and 50), `sheet_size` (optional string e.g. '30x40'), `nalis_produced` (optional int), `completed_sheets_produced` (optional int), `shift` ('DAY' or 'NIGHT'), `operator_name` (optional string).\n" .
                "5. Log Vehicle Gate Entry (`/gate-register`):\n" .
                "   - payload keys: `movement_type` ('OUTWARD_DISPATCH' or 'INWARD_RAW_MATERIAL'), `party_id` (required int), `vehicle_number` (required string), `driver_name` (required string), `driver_phone` (optional string), `carton_product_id` (optional int), `total_pieces` (optional int), `material_summary` (optional string).\n" .
                "6. Record Weigh & Exit for Gate Pass (`/gate-register/{id}/weigh-exit`):\n" .
                "   - payload keys: `gross_weight_kg` (number), `tare_weight_kg` (number).\n" .
                "7. Start Production Batch (`/production`):\n" .
                "   - payload keys: `carton_product_id` (required int), `target_quantity` (required int), `produced_quantity` (optional int default 0), `shift` ('SHIFT_A_DAY' or 'SHIFT_B_NIGHT'), `current_machine` ('CORRUGATION_LINE_1', 'CORRUGATION_LINE_2', 'FLEXO_PRINT_SLOTTER', 'AUTO_STITCHER').\n" .
                "8. Add Output Pieces to Batch (`/production/{id}/output`):\n" .
                "   - payload keys: `add_pieces` (required int), `rejected_pieces` (optional int default 0).\n" .
                "9. Add New Party Customer/Supplier (`/khata/party`):\n" .
                "   - payload keys: `name` (required string), `type` ('CUSTOMER', 'SUPPLIER', 'TRANSPORTER'), `city` (required string), `phone` (required string), `opening_balance` (optional number).\n" .
                "10. Record Worker Cash Advance / Peshgi (`/attendance/advance`):\n" .
                "    - payload keys: `worker_id` (required int), `amount` (required number), `reason` (optional string).";
        } else {
            $allowedActionsDoc = "ROLE: GATE OPERATOR (Gate Security Only)\n" .
                "Available Actions:\n" .
                "1. Log Vehicle Gate Entry (`/gate-register`):\n" .
                "   - payload keys: `movement_type` ('OUTWARD_DISPATCH' or 'INWARD_RAW_MATERIAL'), `party_id` (required int), `vehicle_number` (required string), `driver_name` (required string), `material_summary` (optional string), `total_pieces` (optional int).\n" .
                "2. Record Weigh & Exit (`/gate-register/{id}/weigh-exit`):\n" .
                "   - payload keys: `gross_weight_kg` (number), `tare_weight_kg` (number).\n" .
                "Strictly refuse requests about Khata, production sheets, or salaries.";
        }

        $promptParts = [];
        $promptParts[] = "You are the built-in AI Factory Assistant for **BK Packages ERP** (a corrugated carton manufacturing factory on Depalpur Road, Okara, Pakistan).";
        $promptParts[] = "Current user: {$user->name} (Email: {$user->email}, Role: {$user->role}).";
        $promptParts[] = "";
        $promptParts[] = "FACTORY OPERATIONS DOMAIN KNOWLEDGE:";
        $promptParts[] = "- 'Nali': The corrugated fluting layer made on the corrugator by bonding 1 plain liner reel + 1 wavy/fluted paper reel (Single Face 2-ply).";
        $promptParts[] = "- 3-Ply (Single Wall): 1 Nali + 1 outer plain top sheet.";
        $promptParts[] = "- 5-Ply (Double Wall): 2 Nalis + 1 plain liner for heavy-duty boxes.";
        $promptParts[] = "- 7-Ply (Triple Wall): 3 Nalis + 1 plain liner for export/bulk boxes.";
        $promptParts[] = "- Printed Imports (Outsourced Top Sheets): Multi-color pre-printed litho/offset sheets sourced in pieces (pcs) from printing hubs (Faisalabad, Multan, Lahore, Rahim Yar Khan, Jumber) pasted onto corrugated sheets.";
        $promptParts[] = "- Petty Cash Safe: Factory daily office cash for tea/refreshments (Chai/Khana), diesel/fuel, small machine repairs, hardware, and spot counter sales.";
        $promptParts[] = "- Day-End Roznamcha: Daily factory closing summary of dispatches, vehicle gate passes, raw material received, and cashflow.";
        $promptParts[] = "- Khata: Customer and supplier ledgers. Positive balance is 'Lena Hai' (Receivable), Negative balance is 'Dena Hai' (Payable).";
        $promptParts[] = "";
        $promptParts[] = "GUIDELINES:";
        $promptParts[] = "1. Understand English, Urdu, and Roman Urdu (e.g. 'printed sheets kitni parin hain?', 'aaj ka safe ka kharcha kitna hai?', 'corrugation me nali kitni bani hai?', 'Irfan pharma ka baqaya kitna hai?'). Reply in the same language style, warmly, clearly and concisely.";
        $promptParts[] = "2. Answer questions accurately using ONLY the Live Factory Database Snapshot below. Format Pakistani Rupees as 'Rs. X,XXX'. Never fabricate IDs or numbers.";
        $promptParts[] = "3. Guide first-time users clearly to exact sidebar pages: Dashboard, Production, Printed Imports, Corrugations, Material Conversion, Stock & Paper Reels, Gate Register, Party Khata, Petty Cash Safe, Worker Attendance, Day-End Reports.";
        $promptParts[] = "4. If the user explicitly asks to record or add data, and their role permits it, populate `pending_action` with `target_path` and `payload` using real snapshot IDs so the user can click **Confirm & Save**.";
        $promptParts[] = "5. If required details (such as amount, description, vehicle number) are missing, set `pending_action: null` and ask for the missing information.";
        $promptParts[] = "6. Always respond in valid JSON matching this exact structure:";
        $promptParts[] = "{\n  \"reply\": \"Clear response for the user\",\n  \"pending_action\": null OR {\n    \"title\": \"Short card title\",\n    \"summary_lines\": [\"Detail 1: Value\", \"Detail 2: Value\"],\n    \"target_path\": \"/exact/route\",\n    \"payload\": { ... }\n  }\n}";
        $promptParts[] = "";
        $promptParts[] = $allowedActionsDoc;
        $promptParts[] = "";
        $promptParts[] = "LIVE FACTORY DATABASE SNAPSHOT:";
        $promptParts[] = json_encode($snapshot);

        $systemPrompt = implode("\n", $promptParts);

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

        // Enforce safety & RBAC on pending_action
        if (! empty($decoded['pending_action'])) {
            $targetPath = $decoded['pending_action']['target_path'] ?? '';
            $isGate = $targetPath === '/gate-register' || preg_match('#^/gate-register/\d+/weigh-exit$#', $targetPath);
            $isPetty = $targetPath === '/petty-cash';

            if ($isOwner) {
                // Owner is strictly read-only
                $decoded['pending_action'] = null;
            } elseif ($isManager) {
                // Manager can only execute petty cash
                if (! $isPetty) {
                    $decoded['pending_action'] = null;
                }
            } elseif (! $isFinancial) {
                // Gate Operator can only execute gate
                if (! $isGate) {
                    $decoded['pending_action'] = null;
                }
            }
        }

        return [
            'reply' => $decoded['reply'] ?? 'Sorry, I could not process that request.',
            'pending_action' => $decoded['pending_action'] ?? null,
            'model' => env('GEMINI_MODEL', 'gemini-3-flash-preview'),
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
