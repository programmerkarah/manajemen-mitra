<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Analisis\DokumenAnalysisController;
use App\Http\Controllers\Analisis\PetugasAnalysisController;
use App\Http\Controllers\Analisis\PetugasOrganikAnalysisController;
use App\Http\Controllers\Analisis\PulsaAnalysisController;
use App\Http\Controllers\Analisis\UmumAnalysisController;
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

    public function dokumen(): Response
    {
        return app(DokumenAnalysisController::class)();
    }

    public function umum(): Response
    {
        return app(UmumAnalysisController::class)();
    }
}
