<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('cash_session_id')->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('type')->index();
            $table->string('method')->index();
            $table->decimal('amount', 14, 2);
            $table->nullableMorphs('reference');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
