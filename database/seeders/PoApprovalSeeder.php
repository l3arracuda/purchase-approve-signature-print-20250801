<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PoApproval;
use App\Models\User;

class PoApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $gm = User::where('username', 'gm001')->first();
        $manager1 = User::where('username', 'manager001')->first();
        $manager2 = User::where('username', 'manager002')->first();
        $user1 = User::where('username', 'user001')->first();
        $user2 = User::where('username', 'user002')->first();

        // Sample PO Approvals for testing
        $poApprovals = [
            // PO001 - Fully Approved
            [
                'po_docno' => 'PO001',
                'approver_id' => $user1->id,
                'approval_level' => 1,
                'approval_status' => 'approved',
                'approval_date' => now()->subDays(3),
                'approval_note' => 'Initial approval by user',
                'po_amount' => 50000.00,
            ],
            [
                'po_docno' => 'PO001',
                'approver_id' => $manager1->id,
                'approval_level' => 2,
                'approval_status' => 'approved',
                'approval_date' => now()->subDays(2),
                'approval_note' => 'Approved by manager',
                'po_amount' => 50000.00,
            ],
            [
                'po_docno' => 'PO001',
                'approver_id' => $gm->id,
                'approval_level' => 3,
                'approval_status' => 'approved',
                'approval_date' => now()->subDays(1),
                'approval_note' => 'Final approval by GM',
                'po_amount' => 50000.00,
            ],

            // PO002 - Pending at Manager Level
            [
                'po_docno' => 'PO002',
                'approver_id' => $user2->id,
                'approval_level' => 1,
                'approval_status' => 'approved',
                'approval_date' => now()->subHours(6),
                'approval_note' => 'Approved by user',
                'po_amount' => 75000.00,
            ],
            [
                'po_docno' => 'PO002',
                'approver_id' => $manager2->id,
                'approval_level' => 2,
                'approval_status' => 'pending',
                'approval_date' => null,
                'approval_note' => null,
                'po_amount' => 75000.00,
            ],

            // PO003 - Rejected
            [
                'po_docno' => 'PO003',
                'approver_id' => $user1->id,
                'approval_level' => 1,
                'approval_status' => 'approved',
                'approval_date' => now()->subDays(1),
                'approval_note' => 'Initial approval',
                'po_amount' => 25000.00,
            ],
            [
                'po_docno' => 'PO003',
                'approver_id' => $manager1->id,
                'approval_level' => 2,
                'approval_status' => 'rejected',
                'approval_date' => now()->subHours(12),
                'approval_note' => 'Budget exceeded for this period',
                'po_amount' => 25000.00,
            ],

            // PO004 - Just Started (Pending at User Level)
            [
                'po_docno' => 'PO004',
                'approver_id' => $user2->id,
                'approval_level' => 1,
                'approval_status' => 'pending',
                'approval_date' => null,
                'approval_note' => null,
                'po_amount' => 15000.00,
            ],

            // PO005 - Bulk Approval Example
            [
                'po_docno' => 'PO005',
                'approver_id' => $manager1->id,
                'approval_level' => 2,
                'approval_status' => 'approved',
                'approval_date' => now()->subHours(2),
                'approval_note' => 'Bulk approval',
                'po_amount' => 30000.00,
                'approval_method' => 'bulk',
                'bulk_approval_batch_id' => 'BATCH001',
            ],
            [
                'po_docno' => 'PO006',
                'approver_id' => $manager1->id,
                'approval_level' => 2,
                'approval_status' => 'approved',
                'approval_date' => now()->subHours(2),
                'approval_note' => 'Bulk approval',
                'po_amount' => 35000.00,
                'approval_method' => 'bulk',
                'bulk_approval_batch_id' => 'BATCH001',
            ],
        ];

        foreach ($poApprovals as $poApproval) {
            PoApproval::firstOrCreate(
                [
                    'po_docno' => $poApproval['po_docno'],
                    'approver_id' => $poApproval['approver_id'],
                    'approval_level' => $poApproval['approval_level'],
                ],
                $poApproval
            );
        }
    }
}
