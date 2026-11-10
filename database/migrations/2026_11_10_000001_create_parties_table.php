<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type', 30)->default('CUSTOMER'); // CUSTOMER, SUPPLIER, TRANSPORTER, BOTH
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50);
            $table->text('address')->nullable();
            $table->string('city', 100)->default('Okara');
            $table->string('ntn_strn', 50)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->decimal('credit_limit', 14, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
