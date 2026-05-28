<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('sku', 100)->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('buying_price', 12, 2)->nullable();
            $table->string('tax_type_code', 2)->default('A');
            $table->string('item_category', 20)->default('10101501');
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('barcode', 100)->nullable();
            $table->string('unit_of_measure', 10)->default('EA');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'stock_quantity']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
