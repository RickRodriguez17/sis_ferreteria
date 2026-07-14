<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('sale_id')->unique()->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('original_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2);
            $table->string('status')->index();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('credit_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('credit_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 14, 2);
            $table->string('method')->index();
            $table->foreignId('cash_session_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('credits');
    }
};
