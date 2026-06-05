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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('category');
            $table->string('description');
            $table->decimal('income', 10, 0)->default(0);
            $table->decimal('expense', 10, 0)->default(0);
            $table->string('memo')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('item_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10, 0)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            
            // Foreign key relationship
            $table->foreign('parent_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};