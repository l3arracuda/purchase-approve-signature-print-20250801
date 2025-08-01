<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserSignature;
use App\Models\User;

class UserSignatureSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('role', ['gm', 'manager', 'admin'])->get();

        $sampleSignatures = [
            'gm001' => [
                'signature_name' => 'GM Digital Signature',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABQCAYAAACprZ+JAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA50lEQVR4nO3YQQqDMBSF4fceoLewey+wdxBw7wF2K3gHwe49w+z7BZOh0DSJyf+fZBPC40GSkwO4c845z4x/5g8cOGDAgAMGDhhw4IADBgw4cMCgBQYcOGDAgQMGDhhw4IABBw4YMODAARteApAKIBBAKoBEABEBRARIBZAKYPdKH8fNAAOWt1gAAAAAElFTkSuQmCC',
                'is_active' => true,
            ],
            'manager001' => [
                'signature_name' => 'Manager1 Signature',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABQCAYAAACprZ+JAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA50lEQVR4nO3YQQqDMBSF4fceoLewey+wdxBw7wF2K3gHwe49w+z7BZOh0DSJyf+fZBPC40GSkwO4c845z4x/5g8cOGDAgAMGDhhw4IADBgw4cMCgBQYcOGDAgQMGDhhw4IABBw4YMODAARteApAKIBBAKoBEABEBRARIBZAKYPdKH8fNAAOWt1gAAAAAElFTkSuQmCC',
                'is_active' => true,
            ],
            'manager002' => [
                'signature_name' => 'Manager2 Signature',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABQCAYAAACprZ+JAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA50lEQVR4nO3YQQqDMBSF4fceoLewey+wdxBw7wF2K3gHwe49w+z7BZOh0DSJyf+fZBPC40GSkwO4c845z4x/5g8cOGDAgAMGDhhw4IADBgw4cMCgBQYcOGDAgQMGDhhw4IABBw4YMODAARteApAKIBBAKoBEABEBRARIBZAKYPdKH8fNAAOWt1gAAAAAElFTkSuQmCC',
                'is_active' => true,
            ],
            'admin' => [
                'signature_name' => 'Admin Signature',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABQCAYAAACprZ+JAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA50lEQVR4nO3YQQqDMBSF4fceoLewey+wdxBw7wF2K3gHwe49w+z7BZOh0TSJyf+fZBPC40GSkwO4c845z4x/5g8cOGDAgAMGDhhw4IADBgw4cMCgBQYcOGDAgQMGDhhw4IABBw4YMODAARteApAKIBBAKoBEABEBRARIBZAKYPdKH8fNAAOWt1gAAAAAElFTkSuQmCC',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            if (isset($sampleSignatures[$user->username])) {
                UserSignature::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'signature_name' => $sampleSignatures[$user->username]['signature_name'],
                    ],
                    [
                        'signature_data' => $sampleSignatures[$user->username]['signature_data'],
                        'is_active' => $sampleSignatures[$user->username]['is_active'],
                    ]
                );
            }
        }
    }
}
