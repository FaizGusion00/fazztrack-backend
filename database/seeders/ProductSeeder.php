<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'Custom T-Shirt',
                'item_code' => 'TSH-001',
                'status' => 'active',
            ],
            [
                'product_name' => 'Business Cards',
                'item_code' => 'BC-001',
                'status' => 'active',
            ],
            [
                'product_name' => 'Custom Hoodie',
                'item_code' => 'HOD-001',
                'status' => 'active',
            ],
            [
                'product_name' => 'Promotional Flyers',
                'item_code' => 'FLY-001',
                'status' => 'active',
            ],
            [
                'product_name' => 'Custom Polo Shirt',
                'item_code' => 'POL-001',
                'status' => 'active',
            ],
            [
                'product_name' => 'Banner Print',
                'item_code' => 'BAN-001',
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['item_code' => $product['item_code']],
                $product
            );
        }
    }
}
