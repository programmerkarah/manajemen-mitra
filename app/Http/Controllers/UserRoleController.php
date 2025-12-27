<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of users with their roles.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::select('users.id', 'users.name', 'users.username', 'users.email', 'users.is_active', 'users.created_at')
            ->with('roles:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => [
                'encrypted' => encryptData($users->items()),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
                'links' => $users->linkCollection()->toArray(),
            ],
            'filters' => [
                'encrypted' => encryptFilters($request->only(['search'])),
                'decrypted' => $request->only(['search']),
            ],
        ]);
    }

    /**
     * Show the form for editing user roles.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        $allRoles = Role::orderBy('name')->get();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'allRoles' => $allRoles,
        ]);
    }

    /**
     * Update user roles.
     */
    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        // Prevent removing all admin roles if this is the last admin
        $isLastAdmin = $user->isAdmin() &&
            User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count() === 1 &&
            ! in_array(Role::where('name', 'admin')->first()?->id, $request->roles);

        if ($isLastAdmin) {
            return back()->withErrors([
                'roles' => 'Tidak dapat menghapus role admin dari user terakhir yang memiliki role admin.',
            ]);
        }

        // Sync roles
        $user->roles()->sync($request->roles);

        return redirect()->route('users.index')
            ->with('success', 'Role user berhasil diperbarui.');
    }
}
