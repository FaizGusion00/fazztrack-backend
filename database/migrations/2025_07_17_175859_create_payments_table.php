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
        Schema::create('payments', function (Blueprint $table) {
            $table->unsignedInteger('payment_id')->autoIncrement();
            $table->unsignedInteger('order_id');
            $table->enum('type', ['deposit_design', 'deposit_production', 'balance_payment']);
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('receipt_file_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('order_id')
                ->on('orders')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('receipt_file_id')
                ->references('file_id')
                ->on('file_attachments')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // Create index for performance
        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_id', 'idx_payments_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
