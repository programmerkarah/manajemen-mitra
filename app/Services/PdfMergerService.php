<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfMergerService
{
    private static function canUseExec(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabledFunctions = ini_get('disable_functions');
        if (! is_string($disabledFunctions) || trim($disabledFunctions) === '') {
            return true;
        }

        $disabled = array_map(
            static fn (string $value): string => trim(strtolower($value)),
            explode(',', $disabledFunctions),
        );

        return ! in_array('exec', $disabled, true);
    }

    private static function runShellCommand(string $command, array &$output, int &$returnVar): bool
    {
        $output = [];
        $returnVar = 1;

        if (! self::canUseExec()) {
            return false;
        }

        @exec($command, $output, $returnVar);

        return true;
    }

    /**
     * Merge multiple PDF files into one using FPDI with TCPDF
     * Supports mixed orientations (portrait and landscape)
     */
    public static function mergePdfFiles(array $pdfPaths, string $outputPath, ?string $title = null): bool
    {
        $outputPath = str_replace('\\', '/', $outputPath);
        $normalizedPaths = array_map(
            static fn (string $path): string => str_replace('\\', '/', $path),
            $pdfPaths
        );
        $fpdiError = null;

        try {
            $pdf = new Fpdi;
            $pdf->SetCreator('BPS');
            $pdf->SetAuthor('BPS');

            if ($title) {
                $pdf->SetTitle($title);
            }

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);
            $pdf->setCompression(true);

            foreach ($normalizedPaths as $pdfPath) {
                if (! file_exists($pdfPath)) {
                    throw new \RuntimeException("File PDF tidak ditemukan: {$pdfPath}");
                }

                $pageCount = $pdf->setSourceFile($pdfPath);

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $pdf->Output($outputPath, 'F');

            if (self::isValidOutput($outputPath)) {
                self::optimizePdfForWeb($outputPath);

                return true;
            }

            $fpdiError = 'FPDI tidak menghasilkan file output yang valid.';
        } catch (\Throwable $e) {
            $fpdiError = $e->getMessage();
        }

        @unlink($outputPath);

        if (self::mergePdfFilesWithExternalTools($normalizedPaths, $outputPath)) {
            return true;
        }

        Log::error('Gagal menggabungkan PDF dengan seluruh engine yang tersedia.', [
            'output' => $outputPath,
            'inputs' => array_map('basename', $normalizedPaths),
            'input_exists' => array_map('file_exists', $normalizedPaths),
            'fpdi_error' => $fpdiError,
            'exec_available' => self::canUseExec(),
            'qpdf' => self::findBinary('qpdf'),
            'pdfunite' => self::findBinary('pdfunite'),
            'pdftk' => self::findPdftk(),
            'ghostscript' => self::findGhostscript(),
        ]);

        return false;
    }

    private static function isValidOutput(string $outputPath): bool
    {
        return file_exists($outputPath) && (int) filesize($outputPath) > 0;
    }

    /**
     * Fallback for signed PDFs that FPDI cannot parse, such as PDFs using
     * compressed cross-reference streams or signatures from newer PDF tools.
     */
    private static function mergePdfFilesWithExternalTools(array $pdfPaths, string $outputPath): bool
    {
        if (! self::canUseExec()) {
            return false;
        }

        $inputFiles = implode(' ', array_map('escapeshellarg', $pdfPaths));
        $commands = [];

        if ($qpdf = self::findBinary('qpdf')) {
            $commands['qpdf'] = sprintf(
                '%s --empty --pages %s -- %s',
                escapeshellarg($qpdf),
                $inputFiles,
                escapeshellarg($outputPath)
            );
        }

        if ($pdfunite = self::findBinary('pdfunite')) {
            $commands['pdfunite'] = sprintf(
                '%s %s %s',
                escapeshellarg($pdfunite),
                $inputFiles,
                escapeshellarg($outputPath)
            );
        }

        if ($pdftk = self::findPdftk()) {
            $commands['pdftk'] = sprintf(
                '%s %s cat output %s',
                escapeshellarg($pdftk),
                $inputFiles,
                escapeshellarg($outputPath)
            );
        }

        if ($gs = self::findGhostscript()) {
            $commands['ghostscript'] = sprintf(
                '%s -dSAFER -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -dEmbedAllFonts=true -sOutputFile=%s %s',
                escapeshellarg($gs),
                escapeshellarg($outputPath),
                $inputFiles
            );
        }

        foreach ($commands as $engine => $command) {
            @unlink($outputPath);
            $output = [];
            $returnVar = 1;

            self::runShellCommand($command.' 2>&1', $output, $returnVar);

            if ($returnVar === 0 && self::isValidOutput($outputPath)) {
                Log::info('PDF berhasil digabungkan menggunakan engine eksternal.', [
                    'engine' => $engine,
                    'output' => $outputPath,
                ]);

                return true;
            }

            Log::warning('Engine eksternal gagal menggabungkan PDF.', [
                'engine' => $engine,
                'exit_code' => $returnVar,
                'output' => array_slice($output, -10),
            ]);
        }

        @unlink($outputPath);

        return false;
    }

    private static function findBinary(string $binary): ?string
    {
        if (! self::canUseExec()) {
            return null;
        }

        $lookupCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'where '.escapeshellarg($binary).' 2>nul'
            : 'command -v '.escapeshellarg($binary).' 2>/dev/null';

        $output = [];
        $returnVar = 1;
        self::runShellCommand($lookupCommand, $output, $returnVar);

        return $returnVar === 0 && ! empty($output[0])
            ? trim((string) $output[0])
            : null;
    }

    private static function findPdftk(): ?string
    {
        foreach (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? ['C:\\Program Files\\PDFtk\\bin\\pdftk.exe', 'C:\\Program Files (x86)\\PDFtk\\bin\\pdftk.exe']
            : ['/usr/bin/pdftk', '/usr/local/bin/pdftk'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return self::findBinary('pdftk');
    }

    private static function findGhostscript(): ?string
    {
        $possiblePaths = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? [
                'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs10.02.1\\bin\\gswin32c.exe',
            ]
            : ['/usr/bin/gs', '/usr/local/bin/gs'];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return self::findBinary(
            strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin64c' : 'gs'
        );
    }

    /**
     * Optimize generated PDF using Ghostscript when available.
     * Safe no-op when Ghostscript is not installed or optimization fails.
     */
    private static function optimizePdfForWeb(string $filePath): void
    {
        if (! file_exists($filePath) || filesize($filePath) < 512000) {
            return;
        }

        $gsPath = self::findGhostscript();
        if (! $gsPath) {
            return;
        }

        $optimizedPath = $filePath.'.optimized.pdf';

        $command = sprintf(
            '%s -dSAFER -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook -dDetectDuplicateImages=true -dCompressFonts=true -dSubsetFonts=true -dEmbedAllFonts=true -sOutputFile=%s %s',
            escapeshellarg($gsPath),
            escapeshellarg($optimizedPath),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnVar = 1;
        if (! self::runShellCommand($command, $output, $returnVar)) {
            return;
        }

        if ($returnVar !== 0 || ! file_exists($optimizedPath)) {
            @unlink($optimizedPath);

            return;
        }

        $originalSize = (int) filesize($filePath);
        $optimizedSize = (int) filesize($optimizedPath);

        if ($optimizedSize > 0 && $optimizedSize < ($originalSize * 0.97)) {
            @rename($optimizedPath, $filePath);

            return;
        }

        @unlink($optimizedPath);
    }
}
