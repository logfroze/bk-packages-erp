<?php

namespace Database\Seeders;

use App\Models\CartonProduct;
use App\Models\ConversionRun;
use App\Models\ConversionSheetCut;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\PartyLedgerEntry;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        User::updateOrCreate(
            ['email' => 'sohail@bkpackages.com'],
            [
                'name' => 'Mirza Sohail',
                'role' => 'ACCOUNTANT',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'manzoor@bkpackages.com'],
            [
                'name' => 'Manzoor Ali (Director)',
                'role' => 'ADMIN',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'gate@bkpackages.com'],
            [
                'name' => 'Subedar Nazir Ahmed',
                'role' => 'GATE_OPERATOR',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Parties (Customers & Suppliers from BK Packages Company Profile)
        $ahmedGhee = Party::updateOrCreate(
            ['code' => 'CUST-AHMED'],
            [
                'name' => 'Ahmed Oil & Ghee Ind. Ltd.',
                'type' => 'CUSTOMER',
                'contact_person' => 'Haji Tariq',
                'phone' => '0300-8654321',
                'address' => 'Industrial Area, Depalpur Road Unit',
                'city' => 'Rahim Yar Khan',
                'ntn_strn' => '044270-1',
                'opening_balance' => 1420000.00,
                'current_balance' => 1840000.00,
                'credit_limit' => 3000000.00,
            ]
        );

        $irfanPharma = Party::updateOrCreate(
            ['code' => 'CUST-IRFAN'],
            [
                'name' => 'Irfan Pharma Okara',
                'type' => 'CUSTOMER',
                'contact_person' => 'Dr. Irfan',
                'phone' => '0321-6981234',
                'address' => 'M.A. Jinnah Road / Small Industrial Estate',
                'city' => 'Okara',
                'opening_balance' => 650000.00,
                'current_balance' => 650000.00,
                'credit_limit' => 1500000.00,
            ]
        );

        $parachaGhee = Party::updateOrCreate(
            ['code' => 'CUST-PARACHA'],
            [
                'name' => 'Paracha Vegetable Oil & Ghee Mills',
                'type' => 'CUSTOMER',
                'contact_person' => 'Sheikh Nadeem',
                'phone' => '0333-4567890',
                'address' => 'Gojra Bypass Road',
                'city' => 'Gojra',
                'opening_balance' => 1120000.00,
                'current_balance' => 1120000.00,
                'credit_limit' => 2500000.00,
            ]
        );

        $proLifePharma = Party::updateOrCreate(
            ['code' => 'CUST-PROLIFE'],
            [
                'name' => 'Pro Life Pharma Okara',
                'type' => 'CUSTOMER',
                'contact_person' => 'Mian Bilal',
                'phone' => '0301-7788990',
                'address' => 'Bypass Industrial Link',
                'city' => 'Okara',
                'opening_balance' => 430000.00,
                'current_balance' => 430000.00,
                'credit_limit' => 1200000.00,
            ]
        );

        $centuryPaper = Party::updateOrCreate(
            ['code' => 'SUPP-CENTURY'],
            [
                'name' => 'Century Paper & Board Mills',
                'type' => 'SUPPLIER',
                'contact_person' => 'Raza Khan (Sales)',
                'phone' => '042-35871122',
                'address' => 'Kot Radha Kishan Line',
                'city' => 'Kasur',
                'opening_balance' => -1250000.00,
                'current_balance' => -1250000.00,
            ]
        );

        $flyingPaper = Party::updateOrCreate(
            ['code' => 'SUPP-FLYING'],
            [
                'name' => 'Flying Paper & Board Mills',
                'type' => 'SUPPLIER',
                'contact_person' => 'Salman Butt',
                'phone' => '042-37112233',
                'address' => 'Lahore Sheikhupura Road',
                'city' => 'Lahore',
                'opening_balance' => -690000.00,
                'current_balance' => -690000.00,
            ]
        );

        // 3. Raw Materials & Paper Reels
        $kraft150 = RawMaterial::updateOrCreate(
            ['item_code' => 'RM-KRAFT-150'],
            [
                'name' => 'Imported Kraft Liner 150 GSM (48" Deckle)',
                'category' => 'KRAFT_LINER',
                'gsm' => 150,
                'deckle_size_inches' => 48.00,
                'unit_of_measure' => 'KG',
                'total_stock_qty' => 84500.00,
                'minimum_threshold' => 15000.00,
                'unit_cost' => 235.00,
                'godown_location' => 'Main Reel Shed Bay A',
            ]
        );

        $fluting120 = RawMaterial::updateOrCreate(
            ['item_code' => 'RM-FLUTE-120'],
            [
                'name' => 'Semi-Chemical Fluting Medium 120 GSM (48")',
                'category' => 'FLUTING_MEDIUM',
                'gsm' => 120,
                'deckle_size_inches' => 48.00,
                'unit_of_measure' => 'KG',
                'total_stock_qty' => 58000.00,
                'minimum_threshold' => 12000.00,
                'unit_cost' => 175.00,
                'godown_location' => 'Main Reel Shed Bay B',
            ]
        );

        $reel1 = PaperReel::updateOrCreate(
            ['reel_number' => 'CP-REEL-8801'],
            [
                'raw_material_id' => $kraft150->id,
                'supplier_id' => $centuryPaper->id,
                'initial_weight_kg' => 840.00,
                'remaining_weight_kg' => 840.00,
                'received_date' => Carbon::today(),
                'status' => 'IN_STOCK',
            ]
        );

        $reel2 = PaperReel::updateOrCreate(
            ['reel_number' => 'FP-REEL-9120'],
            [
                'raw_material_id' => $fluting120->id,
                'supplier_id' => $flyingPaper->id,
                'initial_weight_kg' => 760.00,
                'remaining_weight_kg' => 420.00,
                'received_date' => Carbon::yesterday(),
                'status' => 'ON_CORRUGATOR',
            ]
        );

        // 4. Carton Products
        $ghee16L = CartonProduct::updateOrCreate(
            ['sku' => 'CTN-AHM-16L'],
            [
                'client_id' => $ahmedGhee->id,
                'box_title' => '16-Litre Vegetable Oil Outer Box',
                'ply_type' => '5-PLY',
                'flute_type' => 'BC_DOUBLE_WALL',
                'length_mm' => 480,
                'width_mm' => 320,
                'height_mm' => 380,
                'joints_type' => 'STITCHED',
                'printing_colors' => '2-Color (Red & Navy)',
                'bundle_pack_size' => 25,
                'unit_selling_price' => 85.00,
                'stock_pieces' => 18500,
            ]
        );

        $syrup120 = CartonProduct::updateOrCreate(
            ['sku' => 'CTN-IRF-120ML'],
            [
                'client_id' => $irfanPharma->id,
                'box_title' => 'Cough Syrup 120ml Master Shipper',
                'ply_type' => '3-PLY',
                'flute_type' => 'B_FLUTE',
                'length_mm' => 360,
                'width_mm' => 240,
                'height_mm' => 210,
                'joints_type' => 'GLUED',
                'printing_colors' => '1-Color (Blue)',
                'bundle_pack_size' => 25,
                'unit_selling_price' => 42.00,
                'stock_pieces' => 14500,
            ]
        );

        $ghee10kg = CartonProduct::updateOrCreate(
            ['sku' => 'CTN-PAR-10KG'],
            [
                'client_id' => $parachaGhee->id,
                'box_title' => '10kg Banaspati Ghee Master Box',
                'ply_type' => '5-PLY',
                'flute_type' => 'BC_DOUBLE_WALL',
                'length_mm' => 420,
                'width_mm' => 310,
                'height_mm' => 340,
                'joints_type' => 'STITCHED',
                'printing_colors' => '2-Color (Green & Gold)',
                'bundle_pack_size' => 25,
                'unit_selling_price' => 76.00,
                'stock_pieces' => 10000,
            ]
        );

        // 5. Production Batches
        ProductionBatch::updateOrCreate(
            ['batch_number' => 'BATCH-2041'],
            [
                'carton_product_id' => $ghee16L->id,
                'client_id' => $ahmedGhee->id,
                'shift' => 'SHIFT_A_DAY',
                'target_quantity' => 12000,
                'produced_quantity' => 8400,
                'current_machine' => 'FLEXO_PRINT_SLOTTER',
                'status' => 'RUNNING',
                'due_date' => Carbon::today()->setTime(17, 0),
                'started_at' => Carbon::today()->setTime(8, 30),
            ]
        );

        ProductionBatch::updateOrCreate(
            ['batch_number' => 'BATCH-2042'],
            [
                'carton_product_id' => $syrup120->id,
                'client_id' => $irfanPharma->id,
                'shift' => 'SHIFT_A_DAY',
                'target_quantity' => 25000,
                'produced_quantity' => 18500,
                'current_machine' => 'AUTO_STITCHER',
                'status' => 'RUNNING',
                'due_date' => Carbon::tomorrow()->setTime(11, 0),
                'started_at' => Carbon::today()->setTime(9, 0),
            ]
        );

        ProductionBatch::updateOrCreate(
            ['batch_number' => 'BATCH-2043'],
            [
                'carton_product_id' => $ghee10kg->id,
                'client_id' => $parachaGhee->id,
                'shift' => 'SHIFT_B_NIGHT',
                'target_quantity' => 8000,
                'produced_quantity' => 0,
                'current_machine' => 'CORRUGATION_LINE_1',
                'status' => 'QUEUED',
                'due_date' => Carbon::today()->addDays(2),
            ]
        );

        // 6. Gate Registers (Inward / Outward)
        $gp4091 = GateRegister::updateOrCreate(
            ['gate_pass_number' => 'GP-4091'],
            [
                'movement_type' => 'OUTWARD_DISPATCH',
                'vehicle_number' => 'LES-8492',
                'vehicle_type' => 'Mazda Titan',
                'driver_name' => 'Muhammad Asif',
                'driver_phone' => '0300-7654321',
                'party_id' => $ahmedGhee->id,
                'bilti_challan_number' => 'BLT-782',
                'material_summary' => '16L Ghee Outer Boxes (5-Ply)',
                'gross_weight_kg' => 14200.00,
                'tare_weight_kg' => 6800.00,
                'net_weight_kg' => 7400.00,
                'total_bundles' => 480,
                'total_pieces' => 12000,
                'status' => 'EXITED',
                'time_in' => Carbon::today()->setTime(11, 30),
                'time_out' => Carbon::today()->setTime(14, 15),
            ]
        );

        GateRegister::updateOrCreate(
            ['gate_pass_number' => 'GP-4092'],
            [
                'movement_type' => 'OUTWARD_DISPATCH',
                'vehicle_number' => 'SL-1190',
                'vehicle_type' => 'Shehzore Truck',
                'driver_name' => 'Tariq Mehmood',
                'driver_phone' => '0321-9876543',
                'party_id' => $irfanPharma->id,
                'bilti_challan_number' => 'BILTI-1104',
                'material_summary' => 'Cough Syrup 120ml Shippers (3-Ply)',
                'gross_weight_kg' => 9450.00,
                'tare_weight_kg' => 4100.00,
                'net_weight_kg' => 5350.00,
                'total_bundles' => 1000,
                'total_pieces' => 25000,
                'status' => 'LOADING',
                'time_in' => Carbon::today()->setTime(13, 10),
            ]
        );

        GateRegister::updateOrCreate(
            ['gate_pass_number' => 'GP-4093'],
            [
                'movement_type' => 'INWARD_RAW_MATERIAL',
                'vehicle_number' => 'TK-5542',
                'vehicle_type' => '10-Wheeler Truck',
                'driver_name' => 'Riaz Ahmed',
                'driver_phone' => '0345-1234567',
                'party_id' => $centuryPaper->id,
                'bilti_challan_number' => 'CP-5021',
                'material_summary' => 'Kraft Liner 150 GSM Reels (28 Rolls)',
                'gross_weight_kg' => 38500.00,
                'tare_weight_kg' => 15700.00,
                'net_weight_kg' => 22800.00,
                'total_bundles' => 28,
                'status' => 'COMPLETED',
                'time_in' => Carbon::today()->setTime(8, 45),
                'time_out' => Carbon::today()->setTime(11, 15),
            ]
        );

        // 7. Vouchers & Party Ledger Entries for Ahmed Oil & Ghee
        $inv8921 = Voucher::updateOrCreate(
            ['voucher_number' => 'INV-8921'],
            [
                'voucher_type' => 'SALES_INVOICE',
                'voucher_date' => Carbon::today(),
                'party_id' => $ahmedGhee->id,
                'gate_register_id' => $gp4091->id,
                'total_amount' => 1020000.00,
                'payment_mode' => 'CREDIT',
                'description' => '12,000 Pcs 16L Ghee Boxes (GP-4091, Mazda LES-8492)',
            ]
        );

        $cr1052 = Voucher::updateOrCreate(
            ['voucher_number' => 'CR-1052'],
            [
                'voucher_type' => 'RECEIPT_VOUCHER',
                'voucher_date' => Carbon::today(),
                'party_id' => $ahmedGhee->id,
                'total_amount' => 960000.00,
                'payment_mode' => 'CASH',
                'description' => 'Cash received by Mirza Sohail at Factory Office',
            ]
        );

        if (PartyLedgerEntry::where('party_id', $ahmedGhee->id)->count() === 0) {
            PartyLedgerEntry::create([
                'party_id' => $ahmedGhee->id,
                'entry_date' => Carbon::today()->startOfMonth(),
                'narration' => 'Opening Balance B/F from October',
                'debit' => 1780000.00,
                'credit' => 0.00,
                'running_balance' => 1780000.00,
            ]);

            PartyLedgerEntry::create([
                'voucher_id' => $inv8921->id,
                'party_id' => $ahmedGhee->id,
                'entry_date' => Carbon::today(),
                'narration' => '12,000 Pcs 16L Ghee Boxes (GP-4091, Mazda LES-8492)',
                'debit' => 1020000.00,
                'credit' => 0.00,
                'running_balance' => 2800000.00,
            ]);

            PartyLedgerEntry::create([
                'voucher_id' => $cr1052->id,
                'party_id' => $ahmedGhee->id,
                'entry_date' => Carbon::today(),
                'narration' => 'Cash received by Mirza Sohail at Factory Office',
                'debit' => 0.00,
                'credit' => 960000.00,
                'running_balance' => 1840000.00,
            ]);
        }

        // 8. Factory Workers & Attendance
        $worker1 = Worker::updateOrCreate(
            ['worker_code' => 'EMP-01'],
            [
                'full_name' => 'Ustad Muhammad Rafiq',
                'role' => 'Corrugator Line Master',
                'wage_type' => 'MONTHLY_SALARIED',
                'rate_amount' => 65000.00,
                'phone' => '0300-4411223',
            ]
        );

        $worker2 = Worker::updateOrCreate(
            ['worker_code' => 'EMP-02'],
            [
                'full_name' => 'Kamran Ali',
                'role' => 'Flexo Slotter Operator',
                'wage_type' => 'MONTHLY_SALARIED',
                'rate_amount' => 45000.00,
                'phone' => '0322-7788441',
            ]
        );

        $worker3 = Worker::updateOrCreate(
            ['worker_code' => 'EMP-03'],
            [
                'full_name' => 'Aslam Stitching Crew',
                'role' => 'Box Stitcher (Piece Rate)',
                'wage_type' => 'PIECE_RATE_STITCHING',
                'rate_amount' => 350.00,
                'phone' => '0345-9900112',
            ]
        );

        DailyAttendance::updateOrCreate(
            ['worker_id' => $worker1->id, 'attendance_date' => Carbon::today()],
            ['status' => 'PRESENT', 'daily_calculated_wage' => 2166.67]
        );

        DailyAttendance::updateOrCreate(
            ['worker_id' => $worker2->id, 'attendance_date' => Carbon::today()],
            ['status' => 'PRESENT', 'daily_calculated_wage' => 1500.00]
        );

        DailyAttendance::updateOrCreate(
            ['worker_id' => $worker3->id, 'attendance_date' => Carbon::today()],
            ['status' => 'PRESENT', 'pieces_completed' => 12000, 'daily_calculated_wage' => 4200.00]
        );

        // 9. Sample Multi-Stage Conversion Run (2 Reels -> Rolls -> Sheets -> Cartons)
        $convRun = ConversionRun::updateOrCreate(
            ['run_code' => 'CONV-1001'],
            [
                'run_date' => Carbon::today()->toDateString(),
                'machine_name' => 'Corrugator Line 1',
                'client_id' => $ahmedGhee->id,
                'carton_product_id' => $ghee16L->id,
                'reel_1_id' => $reel1->id,
                'reel_1_used_kg' => 420.00,
                'reel_1_rolls_produced' => 14,
                'reel_2_id' => $reel2->id,
                'reel_2_used_kg' => 340.00,
                'reel_2_rolls_produced' => 12,
                'total_rolls_produced' => 26,
                'rolls_remaining' => 6,
                'stage1_loss_kg' => 12.50,
                'rolls_used_for_sheets' => 20,
                'expected_sheets' => 4000,
                'actual_sheets_cut' => 3940,
                'sheets_remaining' => 440,
                'stage2_loss_sheets' => 60,
                'sheets_used_for_cartons' => 3500,
                'sheets_per_carton_rule' => 1.00,
                'expected_cartons' => 3500,
                'actual_cartons_produced' => 3460,
                'stage3_loss_cartons' => 40,
                'status' => 'SHEETS_TO_CARTONS',
                'notes' => 'Morning shift dual-reel run for Ahmed Oil & Ghee 16L boxes',
            ]
        );

        if ($convRun->sheetCuts()->count() === 0) {
            ConversionSheetCut::create([
                'conversion_run_id' => $convRun->id,
                'rolls_consumed' => 20,
                'sheet_size_label' => '48" x 32" (16L Ghee Sheet)',
                'cut_length_inches' => 48,
                'cut_width_inches' => 32,
                'sheets_per_roll_formula' => 200,
                'expected_sheets' => 4000,
                'actual_sheets_cut' => 3940,
                'waste_sheets' => 60,
            ]);
        }
    }
}
