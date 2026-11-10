<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_registers', function (Blueprint $table) {
            $table->id();
            $table->string('gate_pass_number', 50)->unique();
            $table->string('movement_type', 40); // INWARD_RAW_MATERIAL, OUTWARD_DISPATCH, GENERAL
            $table->string('vehicle_number', 50);
            $table->string('vehicle_type', 50)->nullable();
            $table->string('driver_name', 100);
            $table->string('driver_phone', 50)->nullable();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('bilti_challan_number', 80)->nullable();
            $table->string('material_summary')->nullable();
            $table->decimal('gross_weight_kg', 10, 2)->nullable();
            $table->decimal('tare_weight_kg', 10, 2)->nullable();
            $table->decimal('net_weight_kg', 10, 2)->nullable();
            $table->integer('total_bundles')->nullable();
            $table->integer('total_pieces')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('ENTERED'); // ENTERED, WEIGHING, LOADING, UNLOADING, COMPLETED, EXITED
            $table->timestamp('time_in')->useCurrent();
            $table->timestamp('time_out')->nullable();
            $table->string('security_officer', 100)->default('Subedar (R) Nazir Ahmed');
            $table->timestamps();
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->foreignId('carton_product_id')->nullable()->constrained('carton_products')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('shift', 30)->default('SHIFT_A_DAY'); // SHIFT_A_DAY, SHIFT_B_NIGHT
            $table->integer('target_quantity');
            $table->integer('produced_quantity')->default(0);
            $table->integer('rejected_waste_pieces')->default(0);
            $table->string('current_machine', 50); // CORRUGATION_LINE_1, CORRUGATION_LINE_2, FLEXO_PRINT_SLOTTER, DIE_CUTTER, AUTO_STITCHER, MANUAL_BUNDLING
            $table->string('status', 30)->default('QUEUED'); // QUEUED, RUNNING, ON_HOLD, COMPLETED, REJECTED
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('gate_registers');
    }
};
