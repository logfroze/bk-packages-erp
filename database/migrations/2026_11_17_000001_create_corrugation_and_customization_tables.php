<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Corrugations Table (Nali formation & 3-ply / 5-ply / 7-ply Sheet Production)
        if (!Schema::hasTable('corrugation_runs')) {
            Schema::create('corrugation_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_code', 50)->unique();
                $table->string('board_type', 40)->default('DOUBLE_WALL'); // SINGLE_FACE (1 Nali), SINGLE_WALL (3-ply), DOUBLE_WALL (5-ply), TRIPLE_WALL (7-ply)
                $table->integer('flute_ply_count')->default(5); // 2, 3, 5, 7 ply

                // Paper Reels Consumed
                $table->foreignId('plain_paper_reel_1_id')->nullable()->constrained('paper_reels')->nullOnDelete();
                $table->foreignId('fluting_wavy_reel_1_id')->nullable()->constrained('paper_reels')->nullOnDelete();
                $table->foreignId('plain_paper_reel_2_id')->nullable()->constrained('paper_reels')->nullOnDelete();
                $table->foreignId('fluting_wavy_reel_2_id')->nullable()->constrained('paper_reels')->nullOnDelete();
                $table->foreignId('top_plain_paper_reel_id')->nullable()->constrained('paper_reels')->nullOnDelete();

                // Weights & Deckle
                $table->decimal('deckle_size_inches', 5, 1)->default(35.0);
                $table->decimal('total_paper_weight_kg', 10, 2)->default(0.00);
                $table->decimal('waste_trim_kg', 10, 2)->default(0.00);

                // Production Counts
                $table->integer('nalis_produced')->default(0); // 1 plain + 1 wavy = 1 Nali
                $table->integer('completed_sheets_produced')->default(0); // e.g. 2 Nalis + 1 plain = 1 5-ply sheet
                $table->string('sheet_size', 100)->nullable(); // e.g. 45" x 65" or 1140mm x 1650mm

                // Operational Details
                $table->string('operator_name', 100)->default('Master Corrugator');
                $table->string('shift', 30)->default('DAY'); // DAY, NIGHT
                $table->string('status', 30)->default('COMPLETED'); // QUEUED, IN_PROGRESS, COMPLETED
                $table->text('notes')->nullable();
                $table->jsonb('custom_fields')->nullable();
                $table->jsonb('custom_grid')->nullable(); // For dynamic rows & columns created by user
                $table->timestamps();
            });
        }

        // 2. Custom ERP Dynamic Cards & Grids (User-created cards, tables, custom metrics)
        if (!Schema::hasTable('custom_erp_cards')) {
            Schema::create('custom_erp_cards', function (Blueprint $table) {
                $table->id();
                $table->string('page_key', 60); // dashboard, corrugations, printed_imports, production, inventory, khata
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('card_type', 40)->default('DYNAMIC_GRID'); // DYNAMIC_GRID, KEY_VALUE, METRIC
                $table->integer('rows_count')->default(3);
                $table->integer('columns_count')->default(4);
                $table->jsonb('column_headers')->nullable(); // e.g. ["Col 1", "Col 2", "Col 3"]
                $table->jsonb('grid_data')->nullable(); // 2D array of rows & columns
                $table->jsonb('field_pairs')->nullable(); // Key-value pairs for customizable cards
                $table->string('badge_text', 50)->nullable();
                $table->string('badge_color', 30)->default('blue');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 3. Add custom_fields & custom_grid JSONB to existing tables if missing
        $tables = [
            'printed_top_sheets',
            'conversion_runs',
            'production_batches',
            'paper_reels',
            'workers',
            'parties',
            'gate_registers',
            'petty_cash_transactions',
        ];

        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (!Schema::hasColumn($tbl, 'custom_fields')) {
                        $table->jsonb('custom_fields')->nullable();
                    }
                    if (!Schema::hasColumn($tbl, 'custom_grid')) {
                        $table->jsonb('custom_grid')->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_erp_cards');
        Schema::dropIfExists('corrugation_runs');
    }
};
