<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderDesign;
use App\Models\Job;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $products = Product::all();
        $salesUsers = User::whereHas('department', function($q) {
            $q->where('name', 'Sales');
        })->get();
        $designers = User::whereHas('department', function($q) {
            $q->where('name', 'Designer');
        })->get();
        $productionUsers = User::whereHas('department', function($q) {
            $q->where('name', 'Production');
        })->get();

        // Order 1 - Tech Solutions Inc.
        $order1 = Order::create([
            'client_id' => $clients->where('name', 'Tech Solutions Inc.')->first()->client_id,
            'created_by' => $salesUsers->first()->id,
            'job_name' => 'Corporate T-Shirts and Business Cards',
            'status' => 'in_progress',
            'shipping_address' => '123 Business Ave, Tech City, TC 12345',
            'delivery_method' => 'delivery',
            'delivery_tracking_id' => 'TRK001',
            'due_date_design' => Carbon::now()->addDays(3),
            'due_date_production' => Carbon::now()->addDays(7),
            'estimated_delivery_date' => Carbon::now()->addDays(10),
            'link_download' => null,
            'tracking_id' => 'ORD001',
        ]);

        // Order Items for Order 1
        OrderItem::create([
            'order_id' => $order1->order_id,
            'product_id' => $products->where('product_name', 'Custom T-Shirt')->first()->product_id,
            'quantity' => 50,
            'price' => 15.99,
        ]);

        OrderItem::create([
            'order_id' => $order1->order_id,
            'product_id' => $products->where('product_name', 'Business Cards')->first()->product_id,
            'quantity' => 500,
            'price' => 0.05,
        ]);

        // Design for Order 1
        $design1 = OrderDesign::create([
            'order_id' => $order1->order_id,
            'designer_id' => $designers->first()->id,
            'status' => 'in_progress',
        ]);

        // Jobs for Order 1
        Job::create([
            'order_id' => $order1->order_id,
            'assigned_to' => $productionUsers->first()->id,
            'phase' => 'PRINT',
            'status' => 'pending',
        ]);

        Job::create([
            'order_id' => $order1->order_id,
            'assigned_to' => $productionUsers->last()->id,
            'phase' => 'QC',
            'status' => 'pending',
        ]);

        // Order 2 - Creative Agency Ltd.
        $order2 = Order::create([
            'client_id' => $clients->where('name', 'Creative Agency Ltd.')->first()->client_id,
            'created_by' => $salesUsers->last()->id,
            'job_name' => 'Custom Hoodies and Promotional Materials',
            'status' => 'approved',
            'shipping_address' => '456 Design Street, Art District, AD 67890',
            'delivery_method' => 'delivery',
            'delivery_tracking_id' => 'TRK002',
            'due_date_design' => Carbon::now()->addDays(5),
            'due_date_production' => Carbon::now()->addDays(8),
            'estimated_delivery_date' => Carbon::now()->addDays(12),
            'link_download' => null,
            'tracking_id' => 'ORD002',
        ]);

        // Order Items for Order 2
        OrderItem::create([
            'order_id' => $order2->order_id,
            'product_id' => $products->where('product_name', 'Custom Hoodie')->first()->product_id,
            'quantity' => 25,
            'price' => 35.99,
        ]);

        OrderItem::create([
            'order_id' => $order2->order_id,
            'product_id' => $products->where('product_name', 'Promotional Flyers')->first()->product_id,
            'quantity' => 1000,
            'price' => 0.0125,
        ]);

        // Design for Order 2
        $design2 = OrderDesign::create([
            'order_id' => $order2->order_id,
            'designer_id' => $designers->last()->id,
            'status' => 'new',
        ]);

        // Jobs for Order 2
        Job::create([
            'order_id' => $order2->order_id,
            'assigned_to' => $productionUsers->first()->id,
            'phase' => 'PRINT',
            'status' => 'pending',
        ]);

        Job::create([
            'order_id' => $order2->order_id,
            'assigned_to' => $productionUsers->last()->id,
            'phase' => 'PACKING',
            'status' => 'pending',
        ]);
    }
}
