<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $spaces = Space::where('is_active', true)
            ->with(['owner:id,name'])
            ->paginate($request->input('per_page', 15));

        return response()->json($spaces);
    }

    public function managed(Request $request)
    {
        $user = $request->user();

        $spaces = Space::where(function ($query) use ($user) {
            $query->where('owner_id', $user->id)
                ->orWhereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        })
            ->with(['owner:id,name', 'admins:id,name', 'workers:id,name'])
            ->paginate($request->input('per_page', 15));

        return response()->json($spaces);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Space::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $space = Space::create([
            'owner_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json($space, 201);
    }

    public function show(Request $request, Space $space)
    {
        if (! $space->is_active) {
            Gate::authorize('view', $space);
        }

        return response()->json($space->load(['owner:id,name,email,phone']));
    }

    public function update(Request $request, Space $space)
    {
        Gate::authorize('update', $space);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $space->update($request->only([
            'name', 'description', 'address', 'city', 'country', 'is_active', 'contact_phone', 'contact_email', 'latitude', 'longitude',
        ]));

        return response()->json($space);
    }

    public function destroy(Request $request, Space $space)
    {
        Gate::authorize('delete', $space);

        $space->delete();

        return response()->json(['message' => 'Space deleted successfully']);
    }

    public function assignUser(Request $request, Space $space)
    {
        Gate::authorize('manageUsers', $space);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:space_admin,space_worker',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($space->users()->where('user_id', $user->id)->exists()) {
            $space->users()->updateExistingPivot($user->id, ['role' => $request->role]);

            return response()->json([
                'message' => 'User role updated successfully',
                'space' => $space->load(['admins', 'workers']),
            ]);
        }

        $space->users()->attach($user->id, ['role' => $request->role]);

        return response()->json([
            'message' => 'User assigned successfully',
            'space' => $space->load(['admins', 'workers']),
        ]);
    }

    public function removeUser(Request $request, Space $space, User $user)
    {
        Gate::authorize('manageUsers', $space);

        $space->users()->detach($user->id);

        return response()->json(['message' => 'User removed successfully']);
    }

    public function users(Request $request, Space $space)
    {
        Gate::authorize('view', $space);

        $users = $space->users()
            ->select('users.id', 'users.name', 'users.email', 'users.phone')
            ->withPivot('role')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->pivot->role,
                ];
            });

        return response()->json($users);
    }
}
