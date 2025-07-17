<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_id', 32)->nullable()->after('order_id');
            $table->index('tracking_id', 'idx_orders_tracking_id');
        });

        // Update existing orders with tracking IDs
        $orders = Order::whereNull('tracking_id')->get();
        foreach ($orders as $order) {
            $order->tracking_id = strtoupper(Str::random(10));
            $order->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_tracking_id');
            $table->dropColumn('tracking_id');
        });
    }
};
