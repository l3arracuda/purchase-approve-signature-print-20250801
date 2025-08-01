<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PoPrint;
use App\Models\User;

class PoPrintSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $manager1 = User::where('username', 'manager001')->first();
        $user1 = User::where('username', 'user001')->first();

        $poPrints = [
            [
                'po_docno' => 'PO001',
                'printed_by' => $admin->id,
                'print_type' => 'pdf',
                'created_at' => now()->subDays(1),
            ],
            [
                'po_docno' => 'PO001',
                'printed_by' => $manager1->id,
                'print_type' => 'excel',
                'created_at' => now()->subHours(12),
            ],
            [
                'po_docno' => 'PO002',
                'printed_by' => $user1->id,
                'print_type' => 'pdf',
                'created_at' => now()->subHours(6),
            ],
        ];

        foreach ($poPrints as $poPrint) {
            PoPrint::firstOrCreate(
                [
                    'po_docno' => $poPrint['po_docno'],
                    'printed_by' => $poPrint['printed_by'],
                    'print_type' => $poPrint['print_type'],
                ],
                $poPrint
            );
        }
    }
}
