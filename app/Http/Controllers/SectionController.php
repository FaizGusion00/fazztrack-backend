<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of the sections.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view sections
        $this->authorize('viewAny', Section::class);

        $query = Section::query();

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Eager load departments
        $query->with('departments');

        // Pagination
        $perPage = $request->input('per_page', 15);
        $sections = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($sections);
    }

    /**
     * Store a newly created section in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create sections
        $this->authorize('create', Section::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:sections,name',
            'department_ids' => 'sometimes|array',
            'department_ids.*' => 'exists:departments,department_id',
        ]);

        // Create the section
        $section = Section::create([
            'name' => $validated['name'],
        ]);

        // Attach departments if provided
        if (isset($validated['department_ids'])) {
            $section->departments()->attach($validated['department_ids']);
        }

        // Load departments for the response
        $section->load('departments');

        return response()->json($section, 201);
    }

    /**
     * Display the specified section.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Section $section)
    {
        // Check if user has permission to view this section
        $this->authorize('view', $section);

        // Load departments
        $section->load('departments');

        return response()->json($section);
    }

    /**
     * Update the specified section in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Section $section)
    {
        // Check if user has permission to update this section
        $this->authorize('update', $section);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:sections,name,'.$section->section_id.',section_id',
            'department_ids' => 'sometimes|array',
            'department_ids.*' => 'exists:departments,department_id',
        ]);

        // Update the section name if provided
        if (isset($validated['name'])) {
            $section->name = $validated['name'];
            $section->save();
        }

        // Update departments if provided
        if (isset($validated['department_ids'])) {
            $section->departments()->sync($validated['department_ids']);
        }

        // Load departments for the response
        $section->load('departments');

        return response()->json($section);
    }

    /**
     * Remove the specified section from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Section $section)
    {
        // Check if user has permission to delete this section
        $this->authorize('delete', $section);

        // Detach all departments
        $section->departments()->detach();

        // Delete the section
        $section->delete();

        return response()->json(null, 204);
    }
}
