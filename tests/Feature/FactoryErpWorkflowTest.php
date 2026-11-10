<?php

namespace Tests\Feature;

use App\Models\CartonProduct;
use App\Models\ConversionRun;
use App\Models\GateRegister;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryErpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private User $gateOperator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->accountant = User::where('email', 'sohail@bkpackages.com')->firstOrFail();
        $this->gateOperator = User::where('email', 'gate@bkpackages.com')->firstOrFail();
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/khata')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200)->assertSee('BK PACKAGES ERP');
    }

    public function test_login_and_logout_flow_works(): void
    {
        $this->post('/login', [
            'email' => 'sohail@bkpackages.com',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($this->accountant);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_gate_operator_role_restricts_financial_modules(): void
    {
        $this->actingAs($this->gateOperator);

        $this->get('/')->assertStatus(200);
        $this->get('/gate-register')->assertStatus(200);
        $this->get('/khata')->assertStatus(403);
        $this->get('/attendance')->assertStatus(403);
        $this->get('/reports')->assertStatus(403);

        // Ensure offline replay rejects financial paths for gate operators
        $this->postJson('/offline-sync/replay', [
            'client_uuid' => 'gate-op-test-uuid',
            'target_path' => '/khata/voucher',
            'payload' => ['total_amount' => 100],
        ])->assertStatus(403);
    }

    public function test_all_erp_pages_render_successfully_for_accountant(): void
    {
        $this->actingAs($this->accountant);

        $party = Party::first();
        $gate = GateRegister::first();

        $this->get('/')->assertStatus(200)->assertSee('Welcome back, Mirza Sohail');
        $this->get('/production')->assertStatus(200)->assertSee('Production Batches');
        $this->get('/inventory')->assertStatus(200)->assertSee('Stock & Paper Reels');
        $this->get('/inventory?tab=reels')->assertStatus(200)->assertSee('Kraft Paper Reels');
        $this->get('/gate-register')->assertStatus(200)->assertSee('Gate Register & Logistics');
        $this->get("/gate-register/{$gate->id}/slip")->assertStatus(200)->assertSee('BK PACKAGES');
        $this->get('/khata')->assertStatus(200)->assertSee('Party Khata');
        $this->get("/khata/{$party->id}")->assertStatus(200)->assertSee($party->name);
        $this->get('/attendance')->assertStatus(200)->assertSee('Worker Attendance');
        $this->get('/reports')->assertStatus(200)->assertSee('Daily Closing Report');
    }

    public function test_recording_production_updates_finished_carton_stock(): void
    {
        $this->actingAs($this->accountant);

        $carton = CartonProduct::first();
        $initialStock = $carton->stock_pieces;

        $response = $this->post('/production', [
            'carton_product_id' => $carton->id,
            'target_quantity' => 5000,
            'produced_quantity' => 2000,
            'shift' => 'SHIFT_A_DAY',
            'current_machine' => 'CORRUGATION_LINE_1',
        ]);

        $response->assertRedirect();
        $this->assertEquals($initialStock + 2000, $carton->fresh()->stock_pieces);
    }

    public function test_gate_dispatch_with_invoice_updates_stock_and_party_khata(): void
    {
        $this->actingAs($this->accountant);

        $carton = CartonProduct::first();
        $party = $carton->client;
        $initialStock = $carton->stock_pieces;
        $initialBalance = (float) $party->current_balance;

        $qty = 1000;
        $expectedAmount = $qty * (float) $carton->unit_selling_price;

        $response = $this->post('/gate-register', [
            'movement_type' => 'OUTWARD_DISPATCH',
            'vehicle_number' => 'OKR-9911',
            'driver_name' => 'Akram',
            'party_id' => $party->id,
            'carton_product_id' => $carton->id,
            'total_pieces' => $qty,
            'create_invoice' => 1,
        ]);

        $response->assertRedirect();
        $this->assertEquals($initialStock - $qty, $carton->fresh()->stock_pieces);
        $this->assertEquals($initialBalance + $expectedAmount, (float) $party->fresh()->current_balance);
    }

    public function test_recording_cash_receipt_credits_party_balance(): void
    {
        $this->actingAs($this->accountant);

        $party = Party::where('type', 'CUSTOMER')->first();
        $initialBalance = (float) $party->current_balance;

        $response = $this->post('/khata/voucher', [
            'voucher_type' => 'RECEIPT_VOUCHER',
            'party_id' => $party->id,
            'total_amount' => 50000,
            'payment_mode' => 'CASH',
            'description' => 'Cash payment test',
        ]);

        $response->assertRedirect();
        $this->assertEquals($initialBalance - 50000, (float) $party->fresh()->current_balance);
    }

    public function test_attendance_calculates_piece_rate_wages_accurately(): void
    {
        $this->actingAs($this->accountant);

        $stitcher = Worker::where('wage_type', 'PIECE_RATE_STITCHING')->first();

        $response = $this->post('/attendance', [
            'attendance_date' => now()->toDateString(),
            'records' => [
                [
                    'worker_id' => $stitcher->id,
                    'status' => 'PRESENT',
                    'pieces_completed' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_attendance', [
            'worker_id' => $stitcher->id,
            'pieces_completed' => 10000,
            'daily_calculated_wage' => 3500.00,
        ]);
    }

    public function test_offline_sync_ping_returns_csrf_and_online_status(): void
    {
        $this->actingAs($this->accountant);

        $response = $this->getJson('/offline-sync/ping');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'csrf_token', 'server_time'])
            ->assertJson(['status' => 'online']);
    }

    public function test_offline_sync_replay_executes_and_prevents_duplicates(): void
    {
        $this->actingAs($this->accountant);

        $party = Party::where('type', 'CUSTOMER')->first();
        $initialBalance = (float) $party->current_balance;
        $uuid = 'test-sync-uuid-001';

        $payload = [
            'client_uuid' => $uuid,
            'target_path' => '/khata/voucher',
            'payload' => [
                'voucher_type' => 'RECEIPT_VOUCHER',
                'party_id' => $party->id,
                'total_amount' => 25000,
                'payment_mode' => 'CASH',
                'description' => 'Offline cash receipt',
            ],
        ];

        $this->postJson('/offline-sync/replay', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'synced']);

        $this->assertEquals($initialBalance - 25000, (float) $party->fresh()->current_balance);

        $this->postJson('/offline-sync/replay', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'already_synced']);

        $this->assertEquals($initialBalance - 25000, (float) $party->fresh()->current_balance);
    }

    public function test_multi_stage_material_conversion_pipeline_works_end_to_end(): void
    {
        $this->actingAs($this->accountant);

        $reels = PaperReel::all();
        $reel1 = $reels[0];
        $reel2 = $reels[1];
        $r1Initial = (float) $reel1->remaining_weight_kg;
        $r2Initial = (float) $reel2->remaining_weight_kg;

        $carton = CartonProduct::first();
        $initialCartonStock = $carton->stock_pieces;

        // Ensure Conversion index and seeded report render cleanly
        $this->get('/production/conversion')->assertStatus(200)->assertSee('Multi-Stage Material Conversion');

        // Stage 1: Feed 2 simultaneous reels -> produce 10 + 8 = 18 rolls
        $this->post('/production/conversion', [
            'machine_name' => 'Corrugator Line 1',
            'carton_product_id' => $carton->id,
            'reel_1_id' => $reel1->id,
            'reel_1_used_kg' => 200,
            'reel_1_rolls_produced' => 10,
            'reel_2_id' => $reel2->id,
            'reel_2_used_kg' => 150,
            'reel_2_rolls_produced' => 8,
            'stage1_loss_kg' => 5,
        ])->assertRedirect();

        $this->assertEquals($r1Initial - 200, (float) $reel1->fresh()->remaining_weight_kg);
        $this->assertEquals($r2Initial - 150, (float) $reel2->fresh()->remaining_weight_kg);

        $run = ConversionRun::latest('id')->first();
        $this->assertEquals(18, $run->total_rolls_produced);
        $this->assertEquals(18, $run->rolls_remaining);

        // Stage 2: Cut 10 rolls into Size A (expected 10*150=1500, actual 1480 -> loss 20)
        $this->post("/production/conversion/{$run->id}/sheets", [
            'rolls_consumed' => 10,
            'sheet_size_label' => '48" x 32" Ghee Sheet',
            'sheets_per_roll_formula' => 150,
            'actual_sheets_cut' => 1480,
        ])->assertRedirect();

        $run->refresh();
        $this->assertEquals(8, $run->rolls_remaining);
        $this->assertEquals(1480, $run->sheets_remaining);
        $this->assertEquals(20, $run->stage2_loss_sheets);

        // Stage 3: Convert 1000 sheets -> 990 cartons (rule 1 sheet/box, waste 10) -> auto adds to CartonProduct stock
        $this->post("/production/conversion/{$run->id}/cartons", [
            'sheets_used' => 1000,
            'sheets_per_carton_rule' => 1,
            'actual_cartons_produced' => 990,
        ])->assertRedirect();

        $run->refresh();
        $this->assertEquals(480, $run->sheets_remaining);
        $this->assertEquals(990, $run->actual_cartons_produced);
        $this->assertEquals(10, $run->stage3_loss_cartons);
        $this->assertEquals($initialCartonStock + 990, $carton->fresh()->stock_pieces);

        // Full Printable Report renders cleanly
        $this->get("/production/conversion/{$run->id}/report")
            ->assertStatus(200)
            ->assertSee($run->run_code);
    }
}
