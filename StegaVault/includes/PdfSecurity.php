<?php

/**
 * StegaVault - PDF Password Protection Helper
 * File: includes/PdfSecurity.php
 *
 * Uses FPDI + TCPDF to re-save PDFs with user-password protection.
 */

class PdfSecurity
{
    /**
     * Protect a PDF using a user password.
     *
     * @param string $sourcePath Path to input PDF
     * @param string $userPassword Password required to open the PDF
     * @param string|null $error Error message output
     * @return string|false Path to temp protected PDF or false on failure
     */
    public static function protectPdfWithPassword($sourcePath, $userPassword, &$error = null)
    {
        $error = null;

        if (!is_string($sourcePath) || $sourcePath === '' || !file_exists($sourcePath)) {
            $error = 'Source PDF file not found.';
            return false;
        }

        $password = trim((string) $userPassword);
        if ($password === '') {
            $error = 'PDF password is empty.';
            return false;
        }

        // Keep password policy simple and practical for UI entry.
        if (strlen($password) < 4 || strlen($password) > 64) {
            $error = 'PDF password must be between 4 and 64 characters.';
            return false;
        }

        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            $error = 'Composer autoload not found. Run composer install in StegaVault.';
            return false;
        }

        require_once $autoloadPath;

        if (!class_exists('setasign\\Fpdi\\Tcpdf\\Fpdi')) {
            $error = 'PDF libraries are missing. Install tecnickcom/tcpdf and setasign/fpdi packages.';
            return false;
        }

        $tmpPath = false;

        try {
            $fpdiClass = '\\setasign\\Fpdi\\Tcpdf\\Fpdi';
            $pdf = new $fpdiClass();
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $templateSize = $pdf->getTemplateSize($templateId);

                $orientation = ($templateSize['width'] > $templateSize['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$templateSize['width'], $templateSize['height']]);
                $pdf->useTemplate($templateId);
            }

            // Allow only viewing by default; printing/copying requires owner permissions.
            $pdf->SetProtection([], $password, null, 2);

            $tmpDir = __DIR__ . '/../tmp/';
            if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);

            $tmpPath = tempnam($tmpDir, 'sv_pdf_pwd_');
            if ($tmpPath === false) {
                $error = 'Failed to create temporary protected PDF path.';
                return false;
            }

            $pdf->Output($tmpPath, 'F');

            if (!file_exists($tmpPath) || filesize($tmpPath) === 0) {
                @unlink($tmpPath);
                $error = 'Failed to generate protected PDF.';
                return false;
            }

            return $tmpPath;
        } catch (Throwable $e) {
            if ($tmpPath && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }

            $error = 'Unable to secure PDF: ' . $e->getMessage();
            return false;
        }
    }
}
