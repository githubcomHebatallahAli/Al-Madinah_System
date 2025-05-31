<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id');
            $table->string('item_type');
            $table->integer('quantity')->default(1);
            $table->decimal('unitPrice', 15, 2);
            $table->decimal('totalPrice', 15, 2)->nullable();
            // $table->unsignedBigInteger('added_by')->nullable();
            // $table->string('added_by_type')->nullable();
            // $table->unsignedBigInteger('updated_by')->nullable();
            // $table->string('updated_by_type')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
