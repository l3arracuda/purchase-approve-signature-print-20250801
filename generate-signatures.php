<?php
// Create proper base64 signature images
$signatures = [
    'gm001' => [
        'name' => 'GM Digital Signature',
        'text' => 'GM Approved'
    ],
    'manager001' => [
        'name' => 'Manager1 Signature', 
        'text' => 'Manager 1'
    ],
    'manager002' => [
        'name' => 'Manager2 Signature',
        'text' => 'Manager 2' 
    ],
    'admin' => [
        'name' => 'Admin Signature',
        'text' => 'Admin'
    ],
    'user001' => [
        'name' => 'User Signature',
        'text' => 'User 1'
    ]
];

echo "Creating signature base64 data:\n\n";

foreach($signatures as $username => $info) {
    // Create a simple signature image using GD
    $width = 200;
    $height = 80;
    
    $image = imagecreate($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $blue = imagecolorallocate($image, 0, 100, 200);
    
    // Add text
    imagestring($image, 5, 20, 30, $info['text'], $blue);
    
    // Convert to base64
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();
    
    $base64 = 'data:image/png;base64,' . base64_encode($imageData);
    
    echo "'{$username}' => [\n";
    echo "    'signature_name' => '{$info['name']}',\n";
    echo "    'signature_data' => '{$base64}',\n";
    echo "    'is_active' => true,\n";
    echo "],\n\n";
    
    imagedestroy($image);
}
?>
