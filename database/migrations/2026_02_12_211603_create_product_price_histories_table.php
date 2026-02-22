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
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('changed_by_type')->nullable();
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
