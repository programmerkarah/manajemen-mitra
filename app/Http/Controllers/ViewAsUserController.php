<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViewAsUserController extends Controller
{
    /**
     * Set the user to view as in session
     */
    public function set(Request $request): RedirectResponse
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user()->username !== 'rhmtzikri' || $request->user()->username !== 'rahmat.zikri') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $viewAsUser = User::select('id', 'name', 'username')
            ->findOrFail($validated['user_id']);

        session(['view_as_user_id' => $viewAsUser->id]);

        ActivityLog::log(
            'View As User',
            'user',
            "Mulai viewing sebagai user: {$viewAsUser->name} ({$viewAsUser->username})",
            'success',
            ['target_user_id' => $viewAsUser->id, 'target_user_name' => $viewAsUser->name, 'target_username' => $viewAsUser->username]
        );

        $redirectUrl = $request->headers->get('referer') ?? route('dashboard');

        return redirect($redirectUrl)
            ->with('success', "Sekarang viewing sebagai: {$viewAsUser->name}")
            ->header('X-Inertia-Location', $redirectUrl);
    }

    /**
     * Clear view as user from session
     */
    public function clear(Request $request): RedirectResponse
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user()->username !== 'rhmtzikri' || $request->user()->username !== 'rahmat.zikri') {
            abort(403, 'Unauthorized');
        }

        session()->forget('view_as_user_id');

        ActivityLog::log(
            'Clear View As User',
            'user',
            'Mengakhiri sesi view-as-user, kembali sebagai user asli',
            'success'
        );

        $redirectUrl = $request->headers->get('referer') ?? route('dashboard');

        return redirect($redirectUrl)
            ->with('success', 'Kembali sebagai user asli')
            ->header('X-Inertia-Location', $redirectUrl);
    }

    /**
     * Get list of users for dropdown
     */
    public function search(Request $request)
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user()->username !== 'rhmtzikri' || $request->user()->username !== 'rahmat.zikri') {
            abort(403, 'Unauthorized');
        }

        $search = $request->input('search', '');

        $users = User::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        })
            ->where('id', '!=', $request->user()->id) // Exclude current user
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'username', 'email']);

        return response()->json($users);
    }
}
