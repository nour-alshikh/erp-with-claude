<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty_remaining');
            $table->unsignedBigInteger('cost_per_unit'); // cents
            $table->date('date');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'date']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_layers');
    }
};
