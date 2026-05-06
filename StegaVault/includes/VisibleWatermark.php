<?php
/**
 * StegaVault - Visible Watermark for Images, Excel (.xlsx), and Word (.docx)
 * File: includes/VisibleWatermark.php
 *
 * Images : GD diagonal text stamp (TTF if available, tiled built-in font fallback)
 * Excel  : ZipArchive — injects header/footer into every worksheet XML
 * Word   : ZipArchive — injects a styled watermark paragraph at document top
 */

class VisibleWatermark
{
    private static function buildLines(array $data): array
    {
        $name = strtoupper($data['u_name'] ?? 'Unknown');
        $role = strtoupper($data['u_role'] ?? '');
        $ts   = isset($data['ts']) ? date('Y-m-d H:i', (int) $data['ts']) : date('Y-m-d H:i');
        return [
            'Downloaded by: ' . $name,
            ($role ? $role . '  |  ' : '') . $ts,
        ];
    }

    private static function xe(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Route to the correct handler based on file extension.
     * Returns true on success; always produces $outputPath even on partial failure.
     */
    public static function apply(string $inputPath, string $outputPath, array $data, string $ext): bool
    {
        $ext = strtolower(ltrim($ext, '.'));
        switch ($ext) {
            case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp':
                return self::applyToImage($inputPath, $outputPath, $data);
            case 'mp4': case 'mov': case 'webm': case 'avi': case 'ogg':
                $ok = self::applyToVideo($inputPath, $outputPath, $data);
                // FFmpeg unavailable — fall back to plain copy so download still works
                if (!$ok && $inputPath !== $outputPath) {
                    return (bool) copy($inputPath, $outputPath);
                }
                return $ok;
            case 'xlsx':
                return self::applyToExcel($inputPath, $outputPath, $data);
            case 'docx':
                return self::applyToWord($inputPath, $outputPath, $data);
            default:
                return ($inputPath === $outputPath) ? true : (bool) copy($inputPath, $outputPath);
        }
    }

    // ── Images ────────────────────────────────────────────────────────────────

    public static function applyToImage(string $inputPath, string $outputPath, array $data): bool
    {
        $info = @getimagesize($inputPath);
        if (!$info) return false;

        switch ($info['mime']) {
            case 'image/jpeg': $img = @imagecreatefromjpeg($inputPath); break;
            case 'image/png':  $img = @imagecreatefrompng($inputPath);  break;
            case 'image/gif':  $img = @imagecreatefromgif($inputPath);  break;
            case 'image/webp': $img = @imagecreatefromwebp($inputPath); break;
            default: return false;
        }
        if (!$img) return false;

        if (!imageistruecolor($img)) imagepalettetotruecolor($img);
        imagealphablending($img, true);

        $w = imagesx($img);
        $h = imagesy($img);
        [$line1, $line2] = self::buildLines($data);

        $logoPath = __DIR__ . '/../PGMN_WatermarkBg.png';

        if (file_exists($logoPath)) {
            // ── PGMN logo — bottom-right corner stamp ─────────────────────
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                // 18% of the shorter side, minimum 60px
                $logoSize = max(60, (int) (min($w, $h) * 0.18));
                $resized  = imagecreatetruecolor($logoSize, $logoSize);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $trans = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefill($resized, 0, 0, $trans);
                imagecopyresampled($resized, $logo, 0, 0, 0, 0,
                    $logoSize, $logoSize, imagesx($logo), imagesy($logo));
                imagedestroy($logo);

                // ~60% opaque (alpha 51 out of 127)
                for ($ly = 0; $ly < $logoSize; $ly++) {
                    for ($lx = 0; $lx < $logoSize; $lx++) {
                        $c  = imagecolorat($resized, $lx, $ly);
                        $a  = ($c >> 24) & 0x7F;
                        $na = max($a, 51);
                        imagesetpixel($resized, $lx, $ly,
                            imagecolorallocatealpha($resized,
                                ($c >> 16) & 0xFF,
                                ($c >>  8) & 0xFF,
                                 $c        & 0xFF, $na));
                    }
                }

                // Bottom-right with 1.5% padding from each edge
                $padding = max(8, (int) (min($w, $h) * 0.015));
                $dx = $w - $logoSize - $padding;
                $dy = $h - $logoSize - $padding;

                imagealphablending($img, true);
                imagecopy($img, $resized, $dx, $dy, 0, 0, $logoSize, $logoSize);
                imagedestroy($resized);
            }
        } else {
            // ── Text fallback (TTF if available, built-in font otherwise) ─
            $fontFile = self::findFont();

            if ($fontFile) {
                $fontSize = max(12, (int) (min($w, $h) * 0.04));
                $gray     = imagecolorallocatealpha($img, 160, 160, 160, 70);

                $bb1  = imagettfbbox($fontSize, 0, $fontFile, $line1);
                $bb2  = imagettfbbox($fontSize, 0, $fontFile, $line2);
                $len1 = abs($bb1[2] - $bb1[0]);
                $len2 = abs($bb2[2] - $bb2[0]);
                $lineH = abs($bb1[5] - $bb1[1]);
                $gap  = (int) ($lineH * 1.5);

                $cos45 = $sin45 = 0.7071;
                $cx    = $w / 2;
                $cy    = $h / 2;

                $x1 = (int) ($cx - $len1 / 2 * $cos45);
                $y1 = (int) ($cy + $len1 / 2 * $sin45);
                imagettftext($img, $fontSize, 45, $x1, $y1, $gray, $fontFile, $line1);

                $x2 = (int) ($cx + $gap * $sin45 - $len2 / 2 * $cos45);
                $y2 = (int) ($cy + $gap * $cos45 + $len2 / 2 * $sin45);
                imagettftext($img, $fontSize, 45, $x2, $y2, $gray, $fontFile, $line2);
            } else {
                $font  = 5;
                $cw    = imagefontwidth($font);
                $ch    = imagefontheight($font);
                $gray  = imagecolorallocatealpha($img, 160, 160, 160, 70);
                $text  = $line1 . '   |   ' . $line2;
                $textW = strlen($text) * $cw;
                $stepY = $ch * 6;

                for ($baseY = -$textW; $baseY < $h + $textW; $baseY += $stepY) {
                    $row = (int) ($baseY / $stepY);
                    for ($col = -2; $col <= (int) (($w + $textW) / $textW) + 2; $col++) {
                        $x = $col * $textW + (int) ($row * $textW * 0.5);
                        imagestring($img, $font, $x, $baseY, $text, $gray);
                    }
                }
            }
        }

        $ext = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg': case 'jpeg': $ok = imagejpeg($img, $outputPath, 92); break;
            case 'gif':              $ok = imagegif($img, $outputPath);       break;
            case 'webp':             $ok = imagewebp($img, $outputPath, 90);  break;
            default:                 $ok = imagepng($img, $outputPath, 0);    break;
        }
        imagedestroy($img);
        return (bool) $ok;
    }

    // ── Excel (.xlsx) ─────────────────────────────────────────────────────────

    public static function applyToExcel(string $inputPath, string $outputPath, array $data): bool
    {
        if (!class_exists('ZipArchive')) {
            return ($inputPath === $outputPath) ? true : (bool) copy($inputPath, $outputPath);
        }

        if ($inputPath !== $outputPath && !copy($inputPath, $outputPath)) {
            return false;
        }

        [$line1, $line2] = self::buildLines($data);
        $hdrText = self::xe($line1 . '  |  ' . $line2);

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) return false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) continue;

            $xml = $zip->getFromIndex($i);
            if ($xml === false) continue;

            // Remove any existing headerFooter block
            $xml = preg_replace('/<headerFooter\b[^>]*>.*?<\/headerFooter>/s', '', $xml);

            $hfXml = '<headerFooter>'
                . '<oddHeader>&amp;C&amp;"Calibri,Bold"&amp;12&amp;K808080' . $hdrText . '</oddHeader>'
                . '<evenHeader>&amp;C&amp;"Calibri,Bold"&amp;12&amp;K808080' . $hdrText . '</evenHeader>'
                . '</headerFooter>';

            $xml = str_replace('</worksheet>', $hfXml . '</worksheet>', $xml);
            $zip->addFromString($name, $xml);
        }

        $zip->close();
        return true;
    }

    // ── Word (.docx) ──────────────────────────────────────────────────────────

    public static function applyToWord(string $inputPath, string $outputPath, array $data): bool
    {
        if (!class_exists('ZipArchive')) {
            return ($inputPath === $outputPath) ? true : (bool) copy($inputPath, $outputPath);
        }

        if ($inputPath !== $outputPath && !copy($inputPath, $outputPath)) {
            return false;
        }

        [$line1, $line2] = self::buildLines($data);
        $t1 = self::xe($line1);
        $t2 = self::xe($line2);

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) return false;

        $docXml = $zip->getFromName('word/document.xml');
        if ($docXml === false) {
            $zip->close();
            return false;
        }

        // Styled watermark paragraph — gray, centered, with a bottom border separator
        $wmPara =
            '<w:p>'
            . '<w:pPr>'
            . '<w:jc w:val="center"/>'
            . '<w:spacing w:before="0" w:after="120"/>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="4" w:space="1" w:color="C8C8C8"/></w:pBdr>'
            . '</w:pPr>'
            . '<w:r><w:rPr><w:color w:val="C0C0C0"/><w:sz w:val="36"/><w:szCs w:val="36"/><w:b/></w:rPr>'
            . '<w:t>' . $t1 . '</w:t></w:r>'
            . '<w:r><w:rPr><w:color w:val="C0C0C0"/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr>'
            . '<w:t xml:space="preserve">  |  ' . $t2 . '</w:t></w:r>'
            . '</w:p>';

        // Inject right after the <w:body> opening tag
        if (strpos($docXml, '<w:body>') !== false) {
            $docXml = str_replace('<w:body>', '<w:body>' . $wmPara, $docXml);
        } elseif (preg_match('/<w:body\b[^>]*>/', $docXml, $m)) {
            $docXml = str_replace($m[0], $m[0] . $wmPara, $docXml);
        } else {
            $zip->close();
            return false;
        }

        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();
        return true;
    }

    // ── Video ─────────────────────────────────────────────────────────────────

    /**
     * Burn the PGMN logo into the bottom-right corner of a video using FFmpeg.
     * Returns the output path on success, or false if FFmpeg is unavailable.
     */
    public static function applyToVideo(string $inputPath, string $outputPath, array $data): bool
    {
        $ffmpeg = self::findFfmpeg();
        if (!$ffmpeg) return false;

        $logoPath = __DIR__ . '/../PGMN_WatermarkBg.png';
        if (!file_exists($logoPath)) return false;

        // Scale logo to 12% of video width, 10px from each edge, ~60% opacity
        // format=auto handles alpha in the PNG; yuv420p ensures broad compatibility
        $filter = "[1:v]scale=iw*0.12:-1,format=rgba,colorchannelmixer=aa=0.6[logo];"
                . "[0:v][logo]overlay=W-w-10:H-h-10:format=auto,format=yuv420p";

        $cmd = escapeshellcmd($ffmpeg)
            . ' -y'
            . ' -i ' . escapeshellarg($inputPath)
            . ' -i ' . escapeshellarg($logoPath)
            . ' -filter_complex ' . escapeshellarg($filter)
            . ' -codec:a copy'
            . ' -preset fast'
            . ' ' . escapeshellarg($outputPath)
            . ' 2>/dev/null';

        exec($cmd, $out, $rc);
        return $rc === 0 && file_exists($outputPath) && filesize($outputPath) > 0;
    }

    private static function findFfmpeg(): ?string
    {
        static $cached = false;
        if ($cached !== false) return $cached ?: null;

        $candidates = [
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            '/usr/bin/ffmpeg',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) { $cached = $p; return $p; }
        }
        // Last resort: ask the shell
        $which = trim((string) shell_exec('which ffmpeg 2>/dev/null'));
        if ($which && file_exists($which)) { $cached = $which; return $which; }

        $cached = '';
        return null;
    }

    // ── Font discovery ────────────────────────────────────────────────────────

    private static function findFont(): ?string
    {
        static $cached = false;
        if ($cached !== false) return $cached ?: null;

        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
            '/Library/Fonts/Arial Unicode.ttf',
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) {
                $cached = $p;
                return $p;
            }
        }
        $cached = '';
        return null;
    }
}
