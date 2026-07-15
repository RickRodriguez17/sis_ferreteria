<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table): void {
            $table->string('destination')->default('tienda')->index()->after('location_id');
            $table->string('destination_reference')->nullable()->after('destination');
        });

        Schema::create('reception_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reception_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('cost_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('reception_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('previous_cost', 14, 4);
            $table->decimal('received_unit_cost', 14, 4);
            $table->decimal('new_cost', 14, 4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_histories');
        Schema::dropIfExists('reception_attachments');
        Schema::table('receptions', function (Blueprint $table): void {
            $table->dropColumn(['destination', 'destination_reference']);
        });
    }
};
