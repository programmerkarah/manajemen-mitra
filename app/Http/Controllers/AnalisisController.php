<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Analisis\DokumenAnalysisController;
use App\Http\Controllers\Analisis\PetugasAnalysisController;
use App\Http\Controllers\Analisis\PetugasOrganikAnalysisController;
use App\Http\Controllers\Analisis\PulsaAnalysisController;
use App\Http\Controllers\Analisis\UmumAnalysisController;
use Illuminate\Http\Request;
use Inertia\Response;

class AnalisisController extends Controller
{
    public function petugas(): Response
    {
        return app(PetugasAnalysisController::class)();
    }

    public function petugasOrganik(): Response
    {
        return app(PetugasOrganikAnalysisController::class)();
    }

    public function pulsa(): Response
    {
        return app(PulsaAnalysisController::class)();
    }

    public function dokumen(Request $request): Response
    {
        return app(DokumenAnalysisController::class)($request);
    }

    public function umum(): Response
    {
        return app(UmumAnalysisController::class)();
    }
}
