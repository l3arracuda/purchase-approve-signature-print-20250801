<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdatePoApprovalCustomerDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "Updating existing PO approvals with customer and item data...\n";
        
        // ดึงข้อมูล PO ทั้งหมดที่ยังไม่มี customer_name
        $pos = DB::connection('modern')
            ->table('po_approvals')
            ->whereNull('customer_name')
            ->get();
        
        $customers = [
            'ABC Trading Co., Ltd.',
            'XYZ Manufacturing Ltd.',
            'Global Import Export Co.',
            'Tech Solutions Ltd.',
            'Bangkok Supply Chain Co.',
            'Premium Products Ltd.',
            'Quality Materials Co.',
            'Smart Business Ltd.',
            'Excellence Trading Co.',
            'Innovation Corp Ltd.'
        ];
        
        foreach ($pos as $po) {
            $customerName = $customers[array_rand($customers)];
            $itemCount = rand(1, 15);
            
            DB::connection('modern')
                ->table('po_approvals')
                ->where('id', $po->id)
                ->update([
                    'customer_name' => $customerName,
                    'item_count' => $itemCount,
                    'updated_at' => now()
                ]);
            
            echo "Updated PO: {$po->po_docno} -> Customer: {$customerName}, Items: {$itemCount}\n";
        }
        
        echo "Updated {$pos->count()} records successfully!\n";
    }
}
