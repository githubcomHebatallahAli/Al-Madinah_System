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
        Schema::create('pilgrims', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('idNum')->unique();
            $table->string('phoNum')->nullable();
            $table->string('nationality');
            $table->enum('gender', ['male', 'female','child']);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('added_by_type')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->dateTime('creationDate')->nullable();
            $table->string('creationDateHijri')->nullable();
            $table->enum('status', ['active', 'notActive'])->default('active');
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilgrims');
    }
};
