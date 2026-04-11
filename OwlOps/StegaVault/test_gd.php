<?php
header('Content-Type: application/json');
if (!extension_loaded('gd')) {
    echo json_encode(['success' => false, 'error' => 'GD extension NOT loaded']);
    exit;
}

$im = imagecreatetruecolor(100, 100);
if (!$im) {
    echo json_encode(['success' => false, 'error' => 'Failed to create image']);
    exit;
}

$red = imagecolorallocate($im, 255, 0, 0);
imagefill($im, 0, 0, $red);

$color = imagecolorat($im, 50, 50);
$r = ($color >> 16) & 0xFF;

imagedestroy($im);

echo json_encode([
    'success' => true,
    'gd_info' => gd_info(),
    'test_pixel_r' => $r
]);