<?php

namespace App\Http\Controllers\Analisis;

use App\Http\Controllers\Controller;
use App\Services\Analysis\DocumentAnalysisService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DokumenAnalysisController extends Controller
{
    public function __construct(
        private readonly DocumentAnalysisService $analysis,
    ) {}

    public function __invoke(Request $request): Response
    {
        $year = $request->integer('year', (int) date('Y'));

        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        return Inertia::render(
            'Analisis/Dokumen',
            $this->analysis->build($year),
        );
    }
}
