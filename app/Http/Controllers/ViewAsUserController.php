<?php

namespace App\Http\Controllers;

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
        if ($request->user()->username !== 'rhmtzikri') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $viewAsUser = User::findOrFail($validated['user_id']);

        session(['view_as_user_id' => $viewAsUser->id]);

        return redirect()->back()->with('success', "Sekarang viewing sebagai: {$viewAsUser->name}");
    }

    /**
     * Clear view as user from session
     */
    public function clear(Request $request): RedirectResponse
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user()->username !== 'rhmtzikri') {
            abort(403, 'Unauthorized');
        }

        session()->forget('view_as_user_id');

        return redirect()->back()->with('success', 'Kembali sebagai user asli');
    }

    /**
     * Get list of users for dropdown
     */
    public function search(Request $request)
    {
        // Only allow rhmtzikri to use this feature
        if ($request->user()->username !== 'rhmtzikri') {
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
