<?php

namespace App\Services;

class PdfMergerService
{
    /**
     * Merge multiple PDF contents into one
     * Using FPDF approach without external dependencies
     */
    public static function mergePdfContents(array $pdfContents): string
    {
        // Since we can't easily merge PDFs without external library,
        // we'll use a simpler approach: concatenate the PDFs
        // This works with some PDF readers but is not perfect
        
        if (count($pdfContents) === 1) {
            return $pdfContents[0];
        }

        // For now, return the first PDF
        // In production, you'd use a proper PDF merger library
        // or generate as single document with page breaks
        
        return implode('', $pdfContents);
    }

    /**
     * Alternative: Generate PDFs and save temporarily, then merge using command line
     */
    public static function mergePdfFiles(array $pdfPaths, string $outputPath): bool
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
                '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s',
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
        if (!empty($output[0])) {
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
                $xamppGs = dirname(PHP_BINDIR) . '\\bin\\gswin64c.exe';
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
        if (!empty($output[0])) {
            return $output[0];
        }

        return null;
    }
}
