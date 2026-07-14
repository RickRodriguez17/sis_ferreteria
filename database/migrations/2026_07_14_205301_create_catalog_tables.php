<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('abbreviation')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('attribute_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->string('value');
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('brand_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('unit_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('min_stock', 14, 4)->nullable();
            $table->decimal('cost', 14, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presentations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->decimal('equivalence', 14, 4);
            $table->decimal('price_without_invoice', 14, 2);
            $table->decimal('price_with_invoice', 14, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attribute_value', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('attribute_value_id')->constrained()
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_value_id']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_attribute_value');
        Schema::dropIfExists('presentations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('units');
    }
};
