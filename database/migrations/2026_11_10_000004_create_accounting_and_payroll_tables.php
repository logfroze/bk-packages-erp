<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 50)->unique();
            $table->string('voucher_type', 40); // SALES_INVOICE, PURCHASE_BILL, PAYMENT_VOUCHER, RECEIPT_VOUCHER, EXPENSE_VOUCHER, JOURNAL
            $table->date('voucher_date')->useCurrent();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('gate_register_id')->nullable()->constrained('gate_registers')->nullOnDelete();
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_mode', 30)->default('CASH'); // CASH, CHEQUE, ONLINE_BANK, CREDIT
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('created_by', 100)->default('Mirza Sohail');
            $table->timestamps();
        });

        Schema::create('party_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->date('entry_date');
            $table->text('narration')->nullable();
            $table->decimal('debit', 14, 2)->default(0.00);
            $table->decimal('credit', 14, 2)->default(0.00);
            $table->decimal('running_balance', 14, 2);
            $table->timestamps();
        });

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('worker_code', 30)->unique();
            $table->string('full_name', 100);
            $table->string('father_name', 100)->nullable();
            $table->string('cnic', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('role', 60); // Corrugator Master, Slotter Operator, Stitcher, Labor
            $table->string('wage_type', 30); // MONTHLY_SALARIED, DAILY_WAGE, PIECE_RATE_STITCHING
            $table->decimal('rate_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->date('joined_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('daily_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->date('attendance_date')->useCurrent();
            $table->string('status', 20)->default('PRESENT'); // PRESENT, ABSENT, HALF_DAY, LEAVE
            $table->decimal('overtime_hours', 4, 2)->default(0.00);
            $table->integer('pieces_completed')->default(0);
            $table->decimal('daily_calculated_wage', 10, 2)->default(0.00);
            $table->string('recorded_by', 50)->default('Mirza Sohail');
            $table->timestamps();

            $table->unique(['worker_id', 'attendance_date']);
        });

        Schema::create('worker_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->date('advance_date')->useCurrent();
            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_advances');
        Schema::dropIfExists('daily_attendance');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('party_ledger_entries');
        Schema::dropIfExists('vouchers');
    }
};
