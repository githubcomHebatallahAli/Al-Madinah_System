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
        Schema::create('ihram_invoice_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ihram_invoice_id')->constrained('ihram_invoices')->cascadeOnDelete();
            $table->foreignId('ihram_supply_id')->constrained('ihram_supplies')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->dateTime('creationDate')->nullable();
            $table->string('creationDateHijri')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ihram_invoice_supplies');
    }
};
