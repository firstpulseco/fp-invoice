<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('description');
            $table->text('details')->nullable();
            $table->string('pricing_type');
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('hours', 10, 2)->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
            $table->unique(['invoice_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
