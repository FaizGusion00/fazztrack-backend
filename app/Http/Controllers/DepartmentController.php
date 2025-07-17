<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view departments
        $this->authorize('viewAny', Department::class);

        $query = Department::query();

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Eager load sections
        $query->with('sections');

        // Pagination
        $perPage = $request->input('per_page', 15);
        $departments = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($departments);
    }

    /**
     * Store a newly created department in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create departments
        $this->authorize('create', Department::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:departments,name',
            'section_ids' => 'sometimes|array',
            'section_ids.*' => 'exists:sections,section_id',
        ]);

        // Create the department
        $department = Department::create([
            'name' => $validated['name'],
        ]);

        // Attach sections if provided
        if (isset($validated['section_ids'])) {
            $department->sections()->attach($validated['section_ids']);
        }

        // Load sections for the response
        $department->load('sections');

        return response()->json($department, 201);
    }

    /**
     * Display the specified department.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Department $department)
    {
        // Check if user has permission to view this department
        $this->authorize('view', $department);

        // Load sections
        $department->load('sections');

        // Get user count for this department
        $userCount = DB::table('users')
            ->where('department_id', $department->department_id)
            ->count();

        $department->user_count = $userCount;

        return response()->json($department);
    }

    /**
     * Update the specified department in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Department $department)
    {
        // Check if user has permission to update this department
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:departments,name,'.$department->department_id.',department_id',
            'section_ids' => 'sometimes|array',
            'section_ids.*' => 'exists:sections,section_id',
        ]);

        // Update the department name if provided
        if (isset($validated['name'])) {
            $department->name = $validated['name'];
            $department->save();
        }

        // Update sections if provided
        if (isset($validated['section_ids'])) {
            $department->sections()->sync($validated['section_ids']);
        }

        // Load sections for the response
        $department->load('sections');

        return response()->json($department);
    }

    /**
     * Remove the specified department from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Department $department)
    {
        // Check if user has permission to delete this department
        $this->authorize('delete', $department);

        // Check if the department has users
        $hasUsers = DB::table('users')
            ->where('department_id', $department->department_id)
            ->exists();

        if ($hasUsers) {
            return response()->json([
                'message' => 'Cannot delete department that has users assigned to it',
            ], 422);
        }

        // Detach all sections
        $department->sections()->detach();

        // Delete the department
        $department->delete();

        return response()->json(null, 204);
    }
}
