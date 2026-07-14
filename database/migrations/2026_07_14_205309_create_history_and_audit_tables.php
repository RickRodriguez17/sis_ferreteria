<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('priceable');
            $table->string('field')->index();
            $table->decimal('old_value', 14, 4)->nullable();
            $table->decimal('new_value', 14, 4);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('audits', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->string('event')->index();
            $table->morphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
        Schema::dropIfExists('price_histories');
    }
};
