<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name', 200);
            $table->string('product_sku', 100);
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('tax_type_code', 2)->default('A');
            $table->string('item_category', 20)->default('10101501');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sale_items'); }
};
