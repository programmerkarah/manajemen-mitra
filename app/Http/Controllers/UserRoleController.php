<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of users with their roles.
     */
    public function index(FilterRequest $request): Response
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validated();

        $users = User::select('users.id', 'users.name', 'users.username', 'users.email', 'users.is_active', 'users.email_verified_at', 'users.two_factor_secret', 'users.created_at')
            ->with('roles:id,name')
            ->when(! empty($validated['search']), function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('name', 'like', "%{$validated['search']}%")
                        ->orWhere('username', 'like', "%{$validated['search']}%")
                        ->orWhere('email', 'like', "%{$validated['search']}%");
                });
            })
            ->orderBy('name');

        // Get page from validated data
        $page = ! empty($validated['page']) ? (int) $validated['page'] : 1;

        $users = $users->paginate(15, ['*'], 'page', $page)->withQueryString();

        // Transform data to add computed properties
        $transformedUsers = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'two_factor_enabled' => ! is_null($user->two_factor_secret),
                'roles' => $user->roles,
                'created_at' => $user->created_at,
            ];
        });

        return Inertia::render('Users/Index', [
            'users' => [
                'encrypted' => encryptData($transformedUsers),
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
                'encrypted' => encryptFilters($validated),
                'decrypted' => $validated,
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
