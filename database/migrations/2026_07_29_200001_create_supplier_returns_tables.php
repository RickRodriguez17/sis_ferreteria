<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('reception_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('returned_at')->index();
            $table->decimal('total', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('reception_item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity_base', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
        Schema::dropIfExists('supplier_returns');
    }
};
