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
        Schema::create('order_designs', function (Blueprint $table) {
            $table->unsignedInteger('design_id')->autoIncrement();
            $table->unsignedInteger('order_id');
            $table->enum('status', ['new', 'in_progress', 'finalized', 'completed'])->default('new');
            $table->unsignedBigInteger('designer_id');
            $table->unsignedInteger('design_file_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('order_id')
                ->on('orders')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('designer_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('design_file_id')
                ->references('file_id')
                ->on('file_attachments')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // Create index for performance
        Schema::table('order_designs', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_design_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_designs');
    }
};
