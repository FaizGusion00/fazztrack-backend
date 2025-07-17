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
        Schema::create('orders', function (Blueprint $table) {
            $table->unsignedInteger('order_id')->autoIncrement();
            $table->unsignedInteger('client_id');
            $table->unsignedBigInteger('created_by');
            $table->string('job_name', 255);
            $table->enum('status', ['pending', 'approved', 'in_progress', 'qc_packaging', 'in_delivery', 'ready_to_collect', 'completed'])->default('pending');
            $table->text('shipping_address')->nullable();
            $table->enum('delivery_method', ['self_collect', 'delivery']);
            $table->string('delivery_tracking_id', 100)->nullable();
            $table->date('due_date_design');
            $table->date('due_date_production');
            $table->date('estimated_delivery_date');
            $table->string('link_download', 255)->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                ->references('client_id')
                ->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        // Create indexes for performance
        Schema::table('orders', function (Blueprint $table) {
            $table->index('client_id', 'idx_orders_client_id');
            $table->index('created_by', 'idx_orders_created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
