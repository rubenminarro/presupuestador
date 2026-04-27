<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            ['document_number' => '12345678', 'first_name' => 'Juan', 'last_name' => 'Pérez', 'phone' => '0981111222', 'email' => 'juan@example.com', 'active' => 1],
            ['document_number' => '23456789', 'first_name' => 'María', 'last_name' => 'García', 'phone' => '0982222333', 'email' => 'maria@example.com', 'active' => 1],
            ['document_number' => '34567890', 'first_name' => 'Carlos', 'last_name' => 'López', 'phone' => '0983333444', 'email' => 'carlos@example.com', 'active' => 1],
            ['document_number' => '45678901', 'first_name' => 'Ana', 'last_name' => 'Martínez', 'phone' => '0984444555', 'email' => 'ana@example.com', 'active' => 1],
            ['document_number' => '56789012', 'first_name' => 'Luis', 'last_name' => 'Rodríguez', 'phone' => '0985555666', 'email' => 'luis@example.com', 'active' => 1],
        ];

        foreach ($clients as $client) {
            Client::create([
                'document_number' => $client['document_number'],
                'first_name' => $client['first_name'],
                'last_name' => $client['last_name'],
                'phone' => $client['phone'],
                'email' => $client['email'],
                'active' => $client['active']
            ]);
        }
    }
}
