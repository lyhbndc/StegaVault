<?php

/**
 * StegaVault - PDF Visible Watermark
 * File: includes/PdfWatermark.php
 *
 * Overlays a diagonal "Downloaded by / Date" watermark on every page of a PDF
 * using FPDI (page import) + TCPDF (text rendering + alpha).
 */

class PdfWatermark
{
    /**
     * Apply a visible watermark to every page of a PDF.
     *
     * @param string      $sourcePath   Path to the decrypted source PDF
     * @param array       $watermarkData  Keys: u_name, u_role, ts (unix timestamp)
     * @param string|null $error        Error message output on failure
     * @return string|false             Path to temp watermarked PDF, or false on error
     */
    public static function applyWatermark(string $sourcePath, array $watermarkData, &$error = null)
    {
        $error = null;

        if (!file_exists($sourcePath)) {
            $error = 'Source PDF not found.';
            return false;
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $error = 'Composer autoload not found.';
            return false;
        }

        require_once $autoload;

        if (!class_exists('setasign\\Fpdi\\Tcpdf\\Fpdi')) {
            $error = 'FPDI/TCPDF library missing — run composer install.';
            return false;
        }

        $userName  = $watermarkData['u_name'] ?? 'Unknown';
        $userRole  = strtoupper($watermarkData['u_role'] ?? '');
        $timestamp = isset($watermarkData['ts']) ? date('Y-m-d H:i', (int)$watermarkData['ts']) : date('Y-m-d H:i');

        $logoPath = __DIR__ . '/../PGMN_WatermarkBg.png';
        $hasLogo  = file_exists($logoPath);

        $tmpPath = false;

        try {
            /** @var \setasign\Fpdi\Tcpdf\Fpdi $pdf */
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);

            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl  = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $orient = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orient, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                $w  = $pdf->getPageWidth();
                $h  = $pdf->getPageHeight();
                $cx = $w / 2;
                $cy = $h / 2;

                if ($hasLogo) {
                    // ── Logo watermark (centred, rotated 45°, 20% opacity) ──────────
                    $logoSize = min($w, $h) * 0.55; // 55% of shorter side
                    $logoX    = $cx - ($logoSize / 2);
                    $logoY    = $cy - ($logoSize / 2);

                    $pdf->SetAlpha(0.20);
                    $pdf->StartTransform();
                    $pdf->Rotate(45, $cx, $cy);
                    $pdf->Image($logoPath, $logoX, $logoY, $logoSize, $logoSize, 'PNG');
                    $pdf->StopTransform();
                    $pdf->SetAlpha(1);
                } else {
                    // ── Fallback: text watermark ──────────────────────────────────
                    $wmLine1 = 'Downloaded by: ' . strtoupper($userName);
                    $wmLine2 = ($userRole ? $userRole . ' | ' : '') . $timestamp;

                    $pdf->SetAlpha(0.30);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetTextColor(100, 100, 100);
                    $pdf->StartTransform();
                    $pdf->Rotate(45, $cx, $cy);

                    $lineH = 7;
                    $cellW = min($w * 0.8, 130);

                    $pdf->SetXY($cx - ($cellW / 2), $cy - $lineH);
                    $pdf->Cell($cellW, $lineH, $wmLine1, 0, 1, 'C');
                    $pdf->SetXY($cx - ($cellW / 2), $cy);
                    $pdf->Cell($cellW, $lineH, $wmLine2, 0, 1, 'C');

                    $pdf->StopTransform();
                    $pdf->SetAlpha(1);
                }
            }

            $tmpDir = __DIR__ . '/../tmp/';
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0777, true);
            }

            $tmpPath = tempnam($tmpDir, 'sv_pdf_wm_');
            if ($tmpPath === false) {
                $error = 'Could not create temp file for watermarked PDF.';
                return false;
            }

            $pdf->Output($tmpPath, 'F');

            if (!file_exists($tmpPath) || filesize($tmpPath) === 0) {
                @unlink($tmpPath);
                $error = 'Watermarked PDF output is empty.';
                return false;
            }

            return $tmpPath;

        } catch (Throwable $e) {
            if ($tmpPath && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
            $error = 'PDF watermark failed: ' . $e->getMessage();
            return false;
        }
    }
}
