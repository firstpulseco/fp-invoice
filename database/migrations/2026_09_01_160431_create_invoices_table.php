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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status')->default('draft');
            $table->decimal('total', 14, 2)->default(0);

            $table->string('client_company_name');
            $table->string('client_contact_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_street_address')->nullable();
            $table->string('client_address_line_2')->nullable();
            $table->string('client_city')->nullable();
            $table->string('client_region')->nullable();
            $table->string('client_postal_code')->nullable();

            $table->string('business_name')->nullable();
            $table->string('business_logo_path')->nullable();
            $table->string('business_street_address')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_region')->nullable();
            $table->string('business_postal_code')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('payment_method_name')->nullable();
            $table->text('payment_details')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->timestamps();
            $table->index(['status', 'invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
