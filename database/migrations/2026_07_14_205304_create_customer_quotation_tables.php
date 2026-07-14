<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('type')->index();
            $table->string('name');
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('with_invoice')->default(false);
            $table->string('status')->index();
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('total', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('quotation_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('presentation_id')->nullable()->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('customers');
    }
};
