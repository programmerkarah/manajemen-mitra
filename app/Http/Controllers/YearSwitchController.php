<?php

namespace App\Http\Controllers;

use App\Services\ActiveYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class YearSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        ActiveYearService::set($validated['year']);

        return back()->with('success', "Tahun aktif berhasil diubah menjadi {$validated['year']}");
    }
}
