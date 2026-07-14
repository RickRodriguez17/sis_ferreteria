<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('cash_register_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('opened_by')->constrained('users')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('closed_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('opening_amount', 14, 2);
            $table->decimal('closing_amount', 14, 2)->nullable();
            $table->decimal('counted_amount', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->string('status')->index();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('cash_registers');
    }
};
