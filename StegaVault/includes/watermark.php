<?php
/**
 * StegaVault - Watermarking Library (FIXED PARAMETER ORDER)
 * File: includes/watermark.php
 * 
 * Uses LSB (Least Significant Bit) Steganography
 * Embeds invisible watermarks into images
 */

class Watermark
{

    /**
     * Embed watermark into image
     * 
     * @param string $imagePath - Path to original image
     * @param string $outputPath - Path to save watermarked image  
     * @param array $data - Data to embed
     * @return bool - Success status
     */
    public static function embedWatermark($imagePath, $outputPath, $data)
    {
        try {
            // Convert data array to JSON string
            $watermarkData = is_array($data) ? json_encode($data) : $data;

            // Load image
            $image = self::loadImage($imagePath);
            if (!$image) {
                throw new Exception("Failed to load image");
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Prepare watermark data
            // Format: [length][data][checksum]
            $dataLength = strlen($watermarkData);
            $checksum = md5($watermarkData);
            $fullData = $dataLength . '|' . $watermarkData . '|' . $checksum;

            // Convert to binary
            $binaryData = self::stringToBinary($fullData);
            $binaryLength = strlen($binaryData);

            // Check if image is large enough
            $maxCapacity = ($width * $height * 3) - 32; // Reserve space for header
            if ($binaryLength > $maxCapacity) {
                throw new Exception("Image too small for watermark data");
            }

            // Embed binary data using LSB
            $dataIndex = 0;
            $embedded = false;

            for ($y = 0; $y < $height && !$embedded; $y++) {
                for ($x = 0; $x < $width && !$embedded; $x++) {

                    if ($dataIndex >= $binaryLength) {
                        $embedded = true;
                        break;
                    }

                    // Get pixel color
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Embed data in LSB of each color channel
                    if ($dataIndex < $binaryLength) {
                        $r = self::embedBit($r, $binaryData[$dataIndex]);
                        $dataIndex++;
                    }

                    if ($dataIndex < $binaryLength) {
                        $g = self::embedBit($g, $binaryData[$dataIndex]);
                        $dataIndex++;
                    }

                    if ($dataIndex < $binaryLength) {
                        $b = self::embedBit($b, $binaryData[$dataIndex]);
                        $dataIndex++;
                    }

                    // Set new pixel color
                    $newColor = imagecolorallocate($image, $r, $g, $b);
                    imagesetpixel($image, $x, $y, $newColor);
                }
            }

            // Save watermarked image (FIXED PARAMETER ORDER!)
            $result = self::saveImage($image, $imagePath, $outputPath);

            // Free memory
            imagedestroy($image);

            return $result;

        } catch (Exception $e) {
            error_log("Watermark embed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract watermark from image
     * 
     * @param string $imagePath - Path to watermarked image
     * @return array|false - Watermark data or false on failure
     */
    public static function extractWatermark($imagePath)
    {
        try {
            // First, try extracting a document watermark (appended metadata)
            $docWatermark = self::extractDocumentWatermark($imagePath);
            if ($docWatermark !== false) {
                return $docWatermark;
            }

            // If no document watermark, try LSB
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                return false; // Not an image and no doc watermark
            }

            // Load image
            $image = self::loadImage($imagePath);
            if (!$image) {
                throw new Exception("Failed to load image");
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Extract binary data from LSB
            $binaryData = '';
            $maxBitsToExtract = min($width * $height * 3, 50000); // Max ~6KB of data
            $bitsExtracted = 0;

            for ($y = 0; $y < $height && $bitsExtracted < $maxBitsToExtract; $y++) {
                for ($x = 0; $x < $width && $bitsExtracted < $maxBitsToExtract; $x++) {

                    // Get pixel color
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Extract LSB from each channel
                    $binaryData .= self::extractBit($r);
                    $bitsExtracted++;

                    if ($bitsExtracted < $maxBitsToExtract) {
                        $binaryData .= self::extractBit($g);
                        $bitsExtracted++;
                    }

                    if ($bitsExtracted < $maxBitsToExtract) {
                        $binaryData .= self::extractBit($b);
                        $bitsExtracted++;
                    }
                }
            }

            // Final parse attempt
            $parsed = self::tryParseData($binaryData);

            imagedestroy($image);

            return $parsed;

        } catch (Exception $e) {
            error_log("Watermark extract error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Embed a bit into the LSB of a byte
     */
    private static function embedBit($byte, $bit)
    {
        return ($byte & 0xFE) | ($bit == '1' ? 1 : 0);
    }

    /**
     * Extract the LSB from a byte
     */
    private static function extractBit($byte)
    {
        return ($byte & 1) == 1 ? '1' : '0';
    }

    /**
     * Convert string to binary
     */
    private static function stringToBinary($string)
    {
        $binary = '';
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $binary .= sprintf('%08b', ord($string[$i]));
        }

        return $binary;
    }

    /**
     * Convert binary to string
     */
    private static function binaryToString($binary)
    {
        $string = '';
        $length = strlen($binary);

        for ($i = 0; $i < $length; $i += 8) {
            $byte = substr($binary, $i, 8);
            if (strlen($byte) == 8) {
                $string .= chr(bindec($byte));
            }
        }

        return $string;
    }

    /**
     * Try to parse extracted binary data
     */
    private static function tryParseData($binaryData)
    {
        try {
            // Convert binary to string
            $string = self::binaryToString($binaryData);

            // Look for our format: length|data|checksum
            $firstBar = strpos($string, '|');
            if ($firstBar === false) {
                return false;
            }

            $lengthStr = substr($string, 0, $firstBar);
            if (!is_numeric($lengthStr)) {
                return false;
            }

            $length = (int) $lengthStr;
            // Extract data using the known length
            $data = substr($string, $firstBar + 1, $length);

            if (strlen($data) !== $length) {
                return false;
            }

            // Checksum should be after the data and another |
            $checksumBarPos = $firstBar + 1 + $length;
            if (substr($string, $checksumBarPos, 1) !== '|') {
                return false;
            }

            $checksum = substr($string, $checksumBarPos + 1, 32);
            if (strlen($checksum) !== 32 || !ctype_xdigit($checksum)) {
                return false;
            }

            // Validate checksum
            if (md5($data) !== $checksum) {
                return false;
            }

            // Parse JSON
            $decoded = json_decode($data, true);
            if ($decoded !== null) {
                return $decoded;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Load image from file
     */
    private static function loadImage($imagePath)
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }

        $mimeType = $imageInfo['mime'];
        $image = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }

        if ($image && !imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        return $image;
    }

    /**
     * Save image to file
     * FIXED: Correct parameter order - $originalPath THEN $outputPath
     */
    private static function saveImage($image, $originalPath, $outputPath)
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        // If no extension in output path, try to get from original path
        if (empty($extension)) {
            $imageInfo = getimagesize($originalPath);
            if ($imageInfo) {
                $mimeType = $imageInfo['mime'];
                switch ($mimeType) {
                    case 'image/jpeg':
                        $extension = 'jpg';
                        break;
                    case 'image/png':
                        $extension = 'png';
                        break;
                    case 'image/gif':
                        $extension = 'gif';
                        break;
                    case 'image/webp':
                        $extension = 'webp';
                        break;
                }
            }
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Note: Quality 100 for JPEG is still lossy and may damage LSB
                return imagejpeg($image, $outputPath, 100);
            case 'png':
                // Compression level 0 = no compression (best for LSB)
                return imagepng($image, $outputPath, 0);
            case 'gif':
                return imagegif($image, $outputPath);
            case 'webp':
                return imagewebp($image, $outputPath, 100);
            default:
                // Fallback to PNG as safest for LSB
                return imagepng($image, $outputPath, 0);
        }
    }

    /**
     * Get data storage capacity of an image in bytes
     */
    public static function getCapacity($imagePath)
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo)
            return 0;

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // 3 bits per pixel (RGB)
        // Reserve 128 bytes for header and overhead
        $totalBits = ($width * $height * 3);
        $availableBytes = floor(($totalBits - 1024) / 8);

        return max(0, $availableBytes);
    }

    /**
     * Get image metadata
     */
    public static function getMetadata($imagePath)
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo)
            return false;

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime' => $imageInfo['mime'],
            'channels' => $imageInfo['channels'] ?? 3,
            'bits' => $imageInfo['bits'] ?? 8,
            'size' => filesize($imagePath)
        ];
    }

    /**
     * Calculate a forensic hash of the image content, ignoring LSBs.
     * This hash is used to detect if pixels have been edited (e.g. in Paint).
     */
    public static function calculateImageHash($imagePath)
    {
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo) {
            return self::calculateDocumentHash($imagePath);
        }

        $image = self::loadImage($imagePath);
        if (!$image)
            return false;

        $width = imagesx($image);
        $height = imagesy($image);

        $ctx = hash_init('sha256');

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = (($rgb >> 16) & 0xFF) & 0xFE; // Zero out LSB
                $g = (($rgb >> 8) & 0xFF) & 0xFE;
                $b = ($rgb & 0xFF) & 0xFE;

                hash_update($ctx, chr($r) . chr($g) . chr($b));
            }
        }

        imagedestroy($image);
        return hash_final($ctx);
    }

    /**
     * Calculate hash of a document, excluding the watermark metadata if present.
     */
    public static function calculateDocumentHash($filePath)
    {
        $size = filesize($filePath);
        $fp = @fopen($filePath, 'r');
        if (!$fp)
            return false;

        $readSize = min($size, 16384);
        fseek($fp, -$readSize, SEEK_END);
        $tail = fread($fp, $readSize);

        $startTag = "\n[STEGAVAULT_DOC_WM]";
        $startPosInTail = strpos($tail, $startTag);

        $originalSize = $size;
        if ($startPosInTail !== false) {
            $originalSize = $size - $readSize + $startPosInTail;
        }

        rewind($fp);
        $ctx = hash_init('sha256');
        $bytesLeft = $originalSize;
        while ($bytesLeft > 0 && !feof($fp)) {
            $chunk = fread($fp, min(8192, $bytesLeft));
            hash_update($ctx, $chunk);
            $bytesLeft -= strlen($chunk);
        }
        fclose($fp);
        return hash_final($ctx);
    }

    /**
     * Embed watermark into a document by appending it at the EOF
     */
    public static function embedDocumentWatermark($filePath, $outputPath, $data)
    {
        try {
            $watermarkData = is_array($data) ? json_encode($data) : $data;
            $payload = "\n[STEGAVAULT_DOC_WM]" . base64_encode($watermarkData) . "[/STEGAVAULT_DOC_WM]\n";

            if (!copy($filePath, $outputPath)) {
                return false;
            }

            file_put_contents($outputPath, $payload, FILE_APPEND);
            return true;
        } catch (Exception $e) {
            error_log("Doc Watermark embed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract watermark from a document
     */
    public static function extractDocumentWatermark($filePath)
    {
        try {
            $size = filesize($filePath);
            $fp = @fopen($filePath, 'r');
            if (!$fp)
                return false;

            $readSize = min($size, 16384);
            fseek($fp, -$readSize, SEEK_END);
            $tail = fread($fp, $readSize);
            fclose($fp);

            $startTag = "[STEGAVAULT_DOC_WM]";
            $endTag = "[/STEGAVAULT_DOC_WM]";

            $startPos = strpos($tail, $startTag);
            $endPos = strpos($tail, $endTag);

            if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                $startPos += strlen($startTag);
                $b64 = substr($tail, $startPos, $endPos - $startPos);
                $json = base64_decode($b64);
                if ($json) {
                    return json_decode($json, true);
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Doc Watermark extract error: " . $e->getMessage());
            return false;
        }
    }
}
?>