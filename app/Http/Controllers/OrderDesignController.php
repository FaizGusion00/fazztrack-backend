<?php

namespace App\Http\Controllers;

use App\Models\FileAttachment;
use App\Models\Order;
use App\Models\OrderDesign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderDesignController extends Controller
{
    /**
     * Display a listing of the designs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view designs
        $this->authorize('viewAny', OrderDesign::class);

        $query = OrderDesign::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by designer
        if ($request->has('designer_id')) {
            $query->where('designer_id', $request->designer_id);
        }

        // For designers, only show designs assigned to them
        $user = Auth::user();
        if ($user->department->name === 'Designer') {
            $query->where('designer_id', $user->id);
        }

        // Eager load relationships
        $query->with(['order.client', 'designer', 'designFile']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $designs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($designs);
    }

    /**
     * Store a newly created design in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create designs
        $this->authorize('create', OrderDesign::class);

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id',
            'designer_id' => 'required|exists:users,id',
            'status' => 'sometimes|in:new,in_progress,finalized,completed',
        ]);

        // Check if the order already has a design
        $existingDesign = OrderDesign::where('order_id', $validated['order_id'])->first();
        if ($existingDesign) {
            return response()->json([
                'message' => 'This order already has a design assigned',
            ], 422);
        }

        // Check if the designer is actually a designer
        $designer = User::findOrFail($validated['designer_id']);
        if ($designer->department->name !== 'Designer') {
            return response()->json([
                'message' => 'The assigned user must be a designer',
            ], 422);
        }

        // Create the design
        $design = new OrderDesign;
        $design->order_id = $validated['order_id'];
        $design->designer_id = $validated['designer_id'];
        $design->status = $validated['status'] ?? 'new';
        $design->save();

        // Load relationships for the response
        $design->load(['order.client', 'designer']);

        return response()->json($design, 201);
    }

    /**
     * Display the specified design.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(OrderDesign $design)
    {
        // Check if user has permission to view this design
        $this->authorize('view', $design);

        // Load relationships
        $design->load(['order.client', 'designer', 'designFile']);

        return response()->json($design);
    }

    /**
     * Update the specified design in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderDesign $design)
    {
        // Check if user has permission to update this design
        $this->authorize('update', $design);

        $validated = $request->validate([
            'designer_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:new,in_progress,finalized,completed',
        ]);

        // If changing designer, check if the new designer is actually a designer
        if (isset($validated['designer_id'])) {
            $designer = User::findOrFail($validated['designer_id']);
            if ($designer->department->name !== 'Designer') {
                return response()->json([
                    'message' => 'The assigned user must be a designer',
                ], 422);
            }
        }

        $design->update($validated);

        // Load relationships for the response
        $design->load(['order.client', 'designer', 'designFile']);

        return response()->json($design);
    }

    /**
     * Upload a design file for the specified design.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadDesign(Request $request, OrderDesign $design)
    {
        // Check if user has permission to update this design
        $this->authorize('update', $design);

        $request->validate([
            'design_file' => 'required|file|max:10240', // 10MB max
        ]);

        // Store the file
        $file = $request->file('design_file');
        $path = $file->store('designs', 'public');

        // Create a file attachment record
        $fileAttachment = new FileAttachment;
        $fileAttachment->file_path = $path;
        $fileAttachment->file_name = $file->getClientOriginalName();
        $fileAttachment->save();

        // Update the design with the file attachment
        $design->design_file_id = $fileAttachment->file_id;
        $design->save();

        // Load relationships for the response
        $design->load(['order.client', 'designer', 'designFile']);

        return response()->json($design);
    }

    /**
     * Finalize the design.
     *
     * @return \Illuminate\Http\Response
     */
    public function finalize(Request $request, OrderDesign $design)
    {
        // Check if user has permission to finalize this design
        $this->authorize('finalize', $design);

        // Check if the design has a file attached
        if (! $design->design_file_id) {
            return response()->json([
                'message' => 'Cannot finalize design without an uploaded file',
            ], 422);
        }

        // Update the design status to finalized
        $design->status = 'finalized';
        $design->save();

        // Update the order status to in_progress if it's currently approved
        $order = $design->order;
        if ($order->status === 'approved') {
            $order->status = 'in_progress';
            $order->save();
        }

        // Load relationships for the response
        $design->load(['order.client', 'designer', 'designFile']);

        return response()->json([
            'message' => 'Design finalized successfully',
            'design' => $design,
        ]);
    }

    /**
     * Remove the specified design from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderDesign $design)
    {
        // Check if user has permission to delete this design
        $this->authorize('delete', $design);

        // Don't allow deleting designs that are finalized or completed
        if (in_array($design->status, ['finalized', 'completed'])) {
            return response()->json([
                'message' => 'Cannot delete finalized or completed designs',
            ], 422);
        }

        // Delete the design file if it exists
        if ($design->design_file_id) {
            $fileAttachment = $design->designFile;
            Storage::disk('public')->delete($fileAttachment->file_path);
            $fileAttachment->delete();
        }

        $design->delete();

        return response()->json(null, 204);
    }
}
