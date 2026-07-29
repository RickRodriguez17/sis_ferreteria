<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('returned_at')->index();
            $table->decimal('total', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_return_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('presentation_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity_base', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_return_items');
        Schema::dropIfExists('customer_returns');
    }
};
