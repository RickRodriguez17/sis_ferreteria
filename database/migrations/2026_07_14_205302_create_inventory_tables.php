<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('inventory', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('reserved_quantity', 14, 4)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'location_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->string('type')->index();
            $table->string('direction')->index();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->decimal('balance_after', 14, 4);
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
            $table->index(['product_id', 'location_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('locations');
    }
};
