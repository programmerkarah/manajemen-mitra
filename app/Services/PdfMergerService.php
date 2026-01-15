<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfMergerService
{
    /**
     * Merge multiple PDF files into one using FPDI with TCPDF
     * Supports mixed orientations (portrait and landscape)
     */
    public static function mergePdfFiles(array $pdfPaths, string $outputPath, ?string $title = null): bool
    {
        try {
            // Normalize output path to use forward slashes
            $outputPath = str_replace('\\', '/', $outputPath);

            // Use FPDI with TCPDF for PHP 8 compatibility
            $pdf = new Fpdi;

            // TCPDF specific settings
            $pdf->SetCreator('BPS');
            $pdf->SetAuthor('BPS');
            
            // Set title if provided
            if ($title) {
                $pdf->SetTitle($title);
            }
            
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);

            foreach ($pdfPaths as $pdfPath) {
                // Normalize input path
                $pdfPath = str_replace('\\', '/', $pdfPath);

                if (! file_exists($pdfPath)) {
                    continue;
                }

                $pageCount = $pdf->setSourceFile($pdfPath);

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // Import the page
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);

                    // Determine orientation based on page dimensions
                    if ($size['width'] > $size['height']) {
                        $orientation = 'L'; // Landscape
                    } else {
                        $orientation = 'P'; // Portrait
                    }

                    // Add a page with the same orientation and size as the imported page
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);

                    // Use the imported page
                    $pdf->useTemplate($templateId);
                }
            }

            // Output to file - TCPDF Output method signature: Output($name, $dest)
            // F = save to file, return the file name
            $pdf->Output($outputPath, 'F');

            return file_exists($outputPath);
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('PDF Merge Error: '.$e->getMessage());

            // If FPDI fails, try external tools
            return self::mergePdfFilesWithExternalTools($pdfPaths, $outputPath);
        }
    }

    /**
     * Fallback: merge using external tools (Ghostscript or PDFtk)
     */
    private static function mergePdfFilesWithExternalTools(array $pdfPaths, string $outputPath): bool
    {
        // Check if pdftk is available
        $pdftkPath = self::findPdftk();

        if ($pdftkPath) {
            $inputFiles = implode(' ', array_map('escapeshellarg', $pdfPaths));
            $command = sprintf(
                '%s %s cat output %s',
                $pdftkPath,
                $inputFiles,
                escapeshellarg($outputPath)
            );

            exec($command, $output, $returnVar);

            return $returnVar === 0;
        }

        // Fallback: check for ghostscript
        $gsPath = self::findGhostscript();

        if ($gsPath) {
            $inputFiles = implode(' ', array_map('escapeshellarg', $pdfPaths));
            $command = sprintf(
                '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -dEmbedAllFonts=true -dSubsetFonts=false -dCompressFonts=false -sOutputFile=%s %s',
                $gsPath,
                escapeshellarg($outputPath),
                $inputFiles
            );

            exec($command, $output, $returnVar);

            return $returnVar === 0;
        }

        return false;
    }

    private static function findPdftk(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows paths
            $possiblePaths = [
                'C:\\Program Files\\PDFtk\\bin\\pdftk.exe',
                'C:\\Program Files (x86)\\PDFtk\\bin\\pdftk.exe',
            ];
        } else {
            // Linux/Mac paths
            $possiblePaths = ['/usr/bin/pdftk', '/usr/local/bin/pdftk'];
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to find in PATH
        exec('where pdftk 2>nul', $output);
        if (! empty($output[0])) {
            return $output[0];
        }

        return null;
    }

    private static function findGhostscript(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - check common Ghostscript locations
            $possiblePaths = [
                'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs10.02.1\\bin\\gswin32c.exe',
            ];

            // Check if XAMPP includes ghostscript
            if (defined('PHP_BINDIR')) {
                $xamppGs = dirname(PHP_BINDIR).'\\bin\\gswin64c.exe';
                array_unshift($possiblePaths, $xamppGs);
            }
        } else {
            $possiblePaths = ['/usr/bin/gs', '/usr/local/bin/gs'];
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to find in PATH
        exec('where gs 2>nul', $output);
        if (! empty($output[0])) {
            return $output[0];
        }

        return null;
    }
}
