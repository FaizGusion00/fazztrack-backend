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
        Schema::create('jobs', function (Blueprint $table) {
            $table->unsignedInteger('job_id')->autoIncrement();
            $table->unsignedInteger('order_id');
            $table->enum('phase', ['DESIGN', 'PRINT', 'PRESS', 'CUT', 'SEW', 'QC', 'PACKING']);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->unsignedBigInteger('assigned_to');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->time('duration')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('order_id')
                ->on('orders')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        // Create index for performance
        Schema::table('jobs', function (Blueprint $table) {
            $table->index('order_id', 'idx_jobs_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
