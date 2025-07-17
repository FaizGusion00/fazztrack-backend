<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view users
        $this->authorize('viewAny', User::class);

        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by production role
        if ($request->has('production_role')) {
            $query->where('production_role', $request->production_role);
        }

        // Eager load relationships
        $query->with('department.sections');

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create users
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'department_id' => 'required|exists:departments,department_id',
            'production_role' => 'nullable|string|max:50',
        ]);

        // Check if the department exists
        $department = Department::findOrFail($validated['department_id']);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'production_role' => $validated['production_role'],
        ]);

        // Load department and sections for the response
        $user->load('department.sections');

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        // Check if user has permission to view this user
        $this->authorize('view', $user);

        // Load relationships
        $user->load('department.sections');

        // Get statistics based on user's role
        $stats = [];

        if ($user->department->name === 'Sales') {
            // For sales, get order statistics
            $stats['orders_created'] = $user->createdOrders()->count();
            $stats['pending_orders'] = $user->createdOrders()->where('status', 'pending')->count();
            $stats['active_orders'] = $user->createdOrders()->whereIn('status', ['approved', 'in_progress'])->count();
        } elseif ($user->department->name === 'Designer') {
            // For designers, get design statistics
            $stats['designs_assigned'] = $user->designs()->count();
            $stats['designs_in_progress'] = $user->designs()->where('status', 'in_progress')->count();
            $stats['designs_completed'] = $user->designs()->whereIn('status', ['finalized', 'completed'])->count();
        } elseif ($user->production_role) {
            // For production staff, get job statistics
            $stats['jobs_assigned'] = $user->jobs()->count();
            $stats['jobs_pending'] = $user->jobs()->where('status', 'pending')->count();
            $stats['jobs_in_progress'] = $user->jobs()->where('status', 'in_progress')->count();
            $stats['jobs_completed'] = $user->jobs()->where('status', 'completed')->count();

            // Calculate average job duration in minutes
            $completedJobs = $user->jobs()->where('status', 'completed')->whereNotNull('duration')->get();
            $totalDuration = $completedJobs->sum('duration');
            $jobCount = $completedJobs->count();
            $stats['avg_job_duration'] = $jobCount > 0 ? round($totalDuration / $jobCount, 2) : 0;
        }

        $user->statistics = $stats;

        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Check if user has permission to update this user
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'department_id' => 'sometimes|required|exists:departments,department_id',
            'production_role' => 'nullable|string|max:50',
        ]);

        // Update the user
        $user->update($validated);

        // Load department and sections for the response
        $user->load('department.sections');

        return response()->json($user);
    }

    /**
     * Update the user's password.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, User $user)
    {
        // Check if user has permission to update this user's password
        $this->authorize('updatePassword', $user);

        $validated = $request->validate([
            'current_password' => 'required_if:self_update,true|string',
            'password' => 'required|string|min:8|confirmed',
            'self_update' => 'sometimes|boolean',
        ]);

        // If this is a self-update, verify the current password
        if (isset($validated['self_update']) && $validated['self_update']) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'The provided current password is incorrect',
                ], 422);
            }
        }

        // Update the password
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Get the current authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrentUser()
    {
        $user = Auth::user();
        $user->load('department.sections');

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // Check if user has permission to delete this user
        $this->authorize('delete', $user);

        // Don't allow deleting yourself
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        // Check if the user has created orders, designs, or has assigned jobs
        $hasCreatedOrders = $user->createdOrders()->exists();
        $hasDesigns = $user->designs()->exists();
        $hasJobs = $user->jobs()->exists();

        if ($hasCreatedOrders || $hasDesigns || $hasJobs) {
            return response()->json([
                'message' => 'Cannot delete user that has created orders, designs, or has assigned jobs',
            ], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
