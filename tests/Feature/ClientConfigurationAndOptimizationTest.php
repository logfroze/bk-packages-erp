<?php

namespace Tests\Feature;

use App\Models\CartonProduct;
use App\Models\DocumentSequence;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\PettyCashTransaction;
use App\Models\PrintedTopSheet;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientConfigurationAndOptimizationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['email' => 'test-accountant@bkpackages.com'],
            [
                'name' => 'Mirza Sohail',
                'role' => 'ACCOUNTANT',
                'password' => bcrypt('password123'),
            ]
        );
    }

    /**
     * Requirement 1: Bill Book & Document Numbering Configuration
     */
    public function test_document_sequences_and_numbering_customization(): void
    {
        $this->actingAs($this->user);

        // Verify settings page loads
        $response = $this->get('/settings/numbering');
        $response->assertStatus(200);
        $response->assertSee('Document Sequences');

        // Update sequence configuration
        $postRes = $this->post('/settings/numbering', [
            'document_type' => 'DELIVERY_CHALLAN',
            'prefix' => 'DC-PK',
            'next_number' => 101,
            'padding' => 4,
            'suffix' => null,
        ]);
        $postRes->assertRedirect();

        // Verify generated number follows custom format
        $preview = DocumentSequence::previewNextNumber('DELIVERY_CHALLAN');
        $this->assertEquals('DC-PK-0101', $preview);

        $generated = DocumentSequence::generateNextNumber('DELIVERY_CHALLAN');
        $this->assertEquals('DC-PK-0101', $generated);

        // Next number auto-increments
        $nextPreview = DocumentSequence::previewNextNumber('DELIVERY_CHALLAN');
        $this->assertEquals('DC-PK-0102', $nextPreview);
    }

    /**
     * Requirement 2: Paper Reels Configuration (Deckle sizes 15-50" & 3 Grades)
     */
    public function test_paper_reels_deckle_sizes_and_grades(): void
    {
        $this->actingAs($this->user);

        $rawMaterial = RawMaterial::firstOrCreate(
            ['name' => 'Testliner 150 GSM'],
            [
                'item_code' => 'RM-TEST-TL-150',
                'category' => 'KRAFT_PAPER_REEL',
                'unit_of_measure' => 'KG',
                'total_stock_qty' => 5000,
                'minimum_threshold' => 1000,
            ]
        );

        $uniqueReel = 'TEST-REEL-' . strtoupper(bin2hex(random_bytes(4)));
        $response = $this->post('/inventory/reels', [
            'reel_number' => $uniqueReel,
            'raw_material_id' => $rawMaterial->id,
            'paper_grade' => 'SUPER_FLUTING',
            'deckle_size_inches' => 35.5,
            'initial_weight_kg' => 1250.0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paper_reels', [
            'reel_number' => $uniqueReel,
            'paper_grade' => 'SUPER_FLUTING',
            'deckle_size_inches' => 35.5,
            'initial_weight_kg' => 1250.0,
        ]);

        // Deckle sizes helper test (71 total sizes between 15.0 and 50.0 with 0.5 step)
        $sizes = PaperReel::availableDeckleSizes();
        $this->assertCount(71, $sizes);
        $this->assertEquals(15.0, $sizes[0]);
        $this->assertEquals(50.0, end($sizes));
    }

    /**
     * Requirement 3: Pre-Printed Top Sheets (Tracked strictly in Pieces/Count)
     */
    public function test_pre_printed_top_sheets_piece_count_and_adjustments(): void
    {
        $this->actingAs($this->user);

        $uniqueItem = 'Test Top Sheet ' . uniqid();
        $postRes = $this->post('/inventory/top-sheets', [
            'item_name' => $uniqueItem,
            'sourcing_city' => 'Multan',
            'stock_pieces' => 5000,
            'unit_cost' => 11.50,
            'minimum_threshold' => 500,
        ]);
        $postRes->assertRedirect();

        $sheet = PrintedTopSheet::where('item_name', $uniqueItem)->first();
        $this->assertNotNull($sheet);
        $this->assertEquals(5000, $sheet->stock_pieces);
        $this->assertEquals('Multan', $sheet->sourcing_city);

        // Adjust stock (Add 1500 pcs)
        $adjustRes = $this->post("/inventory/top-sheets/{$sheet->id}/adjust", [
            'adjustment_type' => 'ADD',
            'pieces' => 1500,
            'notes' => 'Received fresh shipment from Multan',
        ]);
        $adjustRes->assertRedirect();
        $this->assertEquals(6500, $sheet->fresh()->stock_pieces);
    }

    /**
     * Requirement 4: Labour Management & Weekly Thursday Payout Cycle
     */
    public function test_labour_management_and_weekly_thursday_payroll(): void
    {
        $this->actingAs($this->user);

        // Add dynamic worker
        $workerRes = $this->post('/attendance/worker', [
            'full_name' => 'Muhammad Akram',
            'role' => 'Corrugation Machine Master',
            'wage_type' => 'WEEKLY_WAGE',
            'rate_amount' => 7200.00, // 6 days @ Rs. 1200/day
            'phone' => '0300-9988776',
            'standard_hours_per_day' => 8.0,
            'overtime_rate_per_hour' => 180.0,
        ]);
        $workerRes->assertRedirect();

        $worker = Worker::where('full_name', 'Muhammad Akram')->first();
        $this->assertNotNull($worker);

        // Save daily attendance with 2 hours overtime
        $attRes = $this->post('/attendance', [
            'attendance_date' => now()->toDateString(),
            'records' => [
                [
                    'worker_id' => $worker->id,
                    'status' => 'PRESENT',
                    'overtime_hours' => 2.0,
                    'pieces_completed' => 0,
                ]
            ]
        ]);
        $attRes->assertRedirect();

        // Verify weekly sheet route
        $weeklyRes = $this->get('/attendance/weekly');
        $weeklyRes->assertStatus(200);
        $weeklyRes->assertSee('Weekly Labor Payout');

        // Verify printable Thursday pay slip
        $printRes = $this->get('/attendance/weekly/print');
        $printRes->assertStatus(200);
        $printRes->assertSee('WEEKLY WAGE ROSTER', false);
        $printRes->assertSee('Worker Signature / Thumb', false);
    }

    /**
     * Requirement 5: Local Vendor Management & Audited Balance Adjustment
     */
    public function test_local_vendor_shops_and_audit_safe_balance_adjustment(): void
    {
        $this->actingAs($this->user);

        // Add local hardware vendor
        $vendorName = 'Ittehad Electric Motor ' . uniqid();
        $vendorRes = $this->post('/khata/party', [
            'name' => $vendorName,
            'type' => 'SUPPLIER',
            'classification' => 'LOCAL_VENDOR_SHOP',
            'vendor_category' => 'Electric Motors & Rewinding',
            'contact_person' => 'Ustad Tariq',
            'phone' => '0312-' . rand(1000000, 9999999),
            'city' => 'Okara',
            'opening_balance' => 15000.00,
            'balance_type' => 'PAYABLE',
        ]);
        $vendorRes->assertRedirect();

        $vendor = Party::where('name', $vendorName)->first();
        $this->assertNotNull($vendor);
        $this->assertEquals(-15000.00, (float) $vendor->current_balance);

        // Adjust balance safely with audit trail (no hard delete)
        $adjustRes = $this->post("/khata/party/{$vendor->id}/adjust", [
            'adjustment_type' => 'DEBIT_INCREASE',
            'amount' => 5000.00,
            'reason' => 'Cash advance paid directly by director',
        ]);
        $adjustRes->assertRedirect();

        // New balance is -15000 + 5000 = -10000 (payable reduced)
        $this->assertEquals(-10000.00, (float) $vendor->fresh()->current_balance);

        // Ledger entry exists for audit
        $this->assertDatabaseHas('party_ledger_entries', [
            'party_id' => $vendor->id,
            'debit' => 5000.00,
        ]);
    }

    /**
     * Requirement 6: Petty Cash Management & Daily Factory Safe
     */
    public function test_petty_cash_transactions_and_safe_balance(): void
    {
        $this->actingAs($this->user);

        // Verify index page loads
        $indexRes = $this->get('/petty-cash');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Current Safe Balance');

        // Record cash expense (Kharch)
        $expenseRes = $this->post('/petty-cash', [
            'transaction_type' => 'CASH_OUT',
            'category' => 'TEA_REFRESHMENT',
            'amount' => 450.00,
            'spent_by_or_paid_to' => 'Canteen Boy',
            'description' => 'Morning and evening tea for corrugator shift workers',
        ]);
        $expenseRes->assertRedirect();

        $this->assertDatabaseHas('petty_cash_transactions', [
            'transaction_type' => 'CASH_OUT',
            'amount' => 450.00,
        ]);

        // Printable petty cash ledger
        $printRes = $this->get('/petty-cash/print');
        $printRes->assertStatus(200);
        $printRes->assertSee('SAFE LEDGER');
    }

    /**
     * Requirement 7: FastNav & Service Worker Offline Assets
     */
    public function test_fast_nav_and_service_worker_assets_exist(): void
    {
        $this->assertFileExists(public_path('js/fast-nav.js'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('js/offline-sync.js'));

        $swContent = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('/petty-cash', $swContent);
        $this->assertStringContainsString('/settings/numbering', $swContent);
        $this->assertStringContainsString('/attendance/weekly', $swContent);
        $this->assertStringContainsString('/js/fast-nav.js', $swContent);
    }
}
