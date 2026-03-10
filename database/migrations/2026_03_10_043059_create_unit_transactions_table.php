<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'consumption', 'adjustment'])->default('purchase');
            $table->decimal('amount_kwh', 12, 6)->comment('Positive = added, Negative = consumed');
            $table->decimal('balance_after_kwh', 12, 6);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['device_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_transactions');
    }
};
