<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_code', 40)->unique();
            $table->date('run_date')->useCurrent();
            $table->string('machine_name', 100);
            $table->foreignId('client_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('carton_product_id')->nullable()->constrained('carton_products')->nullOnDelete();

            // Stage 1: Reel -> Roll (Supports 1 or 2 simultaneous reels)
            $table->foreignId('reel_1_id')->nullable()->constrained('paper_reels')->nullOnDelete();
            $table->decimal('reel_1_used_kg', 10, 2)->default(0.00);
            $table->integer('reel_1_rolls_produced')->default(0);

            $table->foreignId('reel_2_id')->nullable()->constrained('paper_reels')->nullOnDelete();
            $table->decimal('reel_2_used_kg', 10, 2)->default(0.00);
            $table->integer('reel_2_rolls_produced')->default(0);

            $table->integer('total_rolls_produced')->default(0);
            $table->integer('rolls_remaining')->default(0);
            $table->decimal('stage1_loss_kg', 10, 2)->default(0.00);

            // Stage 2: Roll -> Sheet Totals
            $table->integer('rolls_used_for_sheets')->default(0);
            $table->integer('expected_sheets')->default(0);
            $table->integer('actual_sheets_cut')->default(0);
            $table->integer('sheets_remaining')->default(0);
            $table->integer('stage2_loss_sheets')->default(0);

            // Stage 3: Sheet -> Carton Totals
            $table->integer('sheets_used_for_cartons')->default(0);
            $table->decimal('sheets_per_carton_rule', 6, 2)->default(1.00);
            $table->integer('expected_cartons')->default(0);
            $table->integer('actual_cartons_produced')->default(0);
            $table->integer('stage3_loss_cartons')->default(0);

            $table->string('status', 30)->default('REELS_TO_ROLLS'); // REELS_TO_ROLLS, ROLLS_TO_SHEETS, SHEETS_TO_CARTONS, COMPLETED
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Child table allowing 1 roll or batch to produce multiple different sheet sizes & cutting rules
        Schema::create('conversion_sheet_cuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_run_id')->constrained('conversion_runs')->cascadeOnDelete();
            $table->integer('rolls_consumed');
            $table->string('sheet_size_label', 120);
            $table->decimal('cut_length_inches', 8, 2)->nullable();
            $table->decimal('cut_width_inches', 8, 2)->nullable();
            $table->decimal('sheets_per_roll_formula', 8, 2)->nullable();
            $table->integer('expected_sheets')->default(0);
            $table->integer('actual_sheets_cut');
            $table->integer('waste_sheets')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_sheet_cuts');
        Schema::dropIfExists('conversion_runs');
    }
};

