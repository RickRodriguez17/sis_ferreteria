<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('quotation_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('with_invoice')->default(false);
            $table->string('payment_type')->index();
            $table->string('status')->index();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->foreignId('location_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('cash_session_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('sale_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('presentation_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity', 14, 4);
            $table->decimal('base_quantity', 14, 4);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->boolean('price_pending')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
