<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchases', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->foreignId('supplier_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->string('status')->index();
            $table->string('payment_type')->index();
            $table->decimal('total', 14, 2);
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('purchase_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity_ordered', 14, 4);
            $table->decimal('quantity_received', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::create('receptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->foreignId('purchase_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('received_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::create('reception_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('reception_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('purchase_item_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_items');
        Schema::dropIfExists('receptions');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
