<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Tech Solutions Inc.',
                'email' => 'contact@techsolutions.com',
                'phone' => '+1-555-0123',
                'billing_address' => '123 Business Ave, Tech City, TC 12345',
            ],
            [
                'name' => 'Creative Agency Ltd.',
                'email' => 'hello@creativeagency.com',
                'phone' => '+1-555-0456',
                'billing_address' => '456 Design Street, Art District, AD 67890',
            ],
            [
                'name' => 'Local Restaurant',
                'email' => 'orders@localrestaurant.com',
                'phone' => '+1-555-0789',
                'billing_address' => '789 Food Lane, Culinary City, CC 11111',
            ],
            [
                'name' => 'Sports Club',
                'email' => 'info@sportsclub.com',
                'phone' => '+1-555-0321',
                'billing_address' => '321 Athletic Blvd, Sports Town, ST 22222',
            ],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['email' => $client['email']],
                $client
            );
        }
    }
}
