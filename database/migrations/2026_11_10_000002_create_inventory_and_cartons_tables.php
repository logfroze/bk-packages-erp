<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->string('category', 40); // KRAFT_LINER, FLUTING_MEDIUM, WHITE_TOP, STARCH, SILICATE, STITCHING_WIRE, FLEXO_INK
            $table->integer('gsm')->nullable();
            $table->decimal('deckle_size_inches', 6, 2)->nullable();
            $table->string('unit_of_measure', 20)->default('KG'); // KG, TON, BAG, DRUM
            $table->decimal('total_stock_qty', 12, 2)->default(0.00);
            $table->decimal('minimum_threshold', 12, 2)->default(1000.00);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->string('godown_location', 100)->default('Main Reel Shed');
            $table->timestamps();
        });

        Schema::create('paper_reels', function (Blueprint $table) {
            $table->id();
            $table->string('reel_number', 60)->unique();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('initial_weight_kg', 10, 2);
            $table->decimal('remaining_weight_kg', 10, 2);
            $table->date('received_date')->useCurrent();
            $table->string('status', 30)->default('IN_STOCK'); // IN_STOCK, ON_CORRUGATOR, CONSUMED
            $table->timestamps();
        });

        Schema::create('carton_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->foreignId('client_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('box_title');
            $table->string('ply_type', 20); // 3-PLY, 5-PLY, 7-PLY
            $table->string('flute_type', 30); // B_FLUTE, C_FLUTE, BC_DOUBLE_WALL, E_MICRO
            $table->decimal('length_mm', 8, 2)->nullable();
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->string('joints_type', 30)->default('STITCHED'); // STITCHED, GLUED
            $table->string('printing_colors', 100)->nullable();
            $table->integer('bundle_pack_size')->default(25);
            $table->decimal('unit_selling_price', 10, 2)->default(0.00);
            $table->integer('stock_pieces')->default(0);
            $table->string('godown_shed', 100)->default('Finished Godown Bay 1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_products');
        Schema::dropIfExists('paper_reels');
        Schema::dropIfExists('raw_materials');
    }
};
