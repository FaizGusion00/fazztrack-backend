<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    /**
     * Display a listing of the jobs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view jobs
        $this->authorize('viewAny', Job::class);

        $query = Job::query();

        // Filter by order
        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        // Filter by phase
        if ($request->has('phase')) {
            $query->where('phase', $request->phase);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // For production staff, only show jobs assigned to them
        $user = Auth::user();
        if ($user->production_role && ! in_array($user->department->name, ['SuperAdmin', 'Sales', 'Admin'])) {
            $query->where('assigned_to', $user->id);
        }

        // Eager load relationships
        $query->with(['order.client', 'assignedUser']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $jobs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($jobs);
    }

    /**
     * Store a newly created job in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Check if user has permission to create jobs
            $this->authorize('create', Job::class);

            $validated = $request->validate([
                'order_id' => 'required|exists:orders,order_id',
                'phase' => 'required|in:design,print,press,cut,sew,qc,iron_packing',
                'status' => 'sometimes|in:pending,in_progress,completed',
                'assigned_to' => 'required|exists:users,id',
            ]);

            // Check if the order exists and is in the right status
            $order = Order::findOrFail($validated['order_id']);
            if (! in_array($order->status, ['approved', 'in_progress'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create jobs for orders that are not approved or in progress',
                ], 422);
            }

            // For design phase, check if the order already has a design job
            if ($validated['phase'] === 'design') {
                $existingDesignJob = Job::where('order_id', $validated['order_id'])
                    ->where('phase', 'design')
                    ->first();

                if ($existingDesignJob) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This order already has a design job',
                    ], 422);
                }
            }

            // For production phases, check if design is finalized
            if ($validated['phase'] !== 'design') {
                $designJob = Job::where('order_id', $validated['order_id'])
                    ->where('phase', 'design')
                    ->first();

                if (! $designJob || $designJob->status !== 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create production jobs until design is completed',
                    ], 422);
                }
            }

            // Check if the assigned user has the right role for the job phase
            $user = User::findOrFail($validated['assigned_to']);

            // Map phases to expected roles
            $phaseRoles = [
                'design' => 'Designer',
                'print' => 'Printer',
                'press' => 'Press',
                'cut' => 'Cutter',
                'sew' => 'Sewer',
                'qc' => 'QC',
                'iron_packing' => 'Packer',
            ];

            // Check if user's production role matches the expected role for the phase
            if ($user->production_role !== $phaseRoles[$validated['phase']]) {
                return response()->json([
                    'success' => false,
                    'message' => "The assigned user must have the '{$phaseRoles[$validated['phase']]}' role",
                ], 422);
            }

            // Create the job
            $job = new Job;
            $job->order_id = $validated['order_id'];
            $job->phase = $validated['phase'];
            $job->status = $validated['status'] ?? 'pending';
            $job->assigned_to = $validated['assigned_to'];
            $job->save();

            // Load relationships for the response
            $job->load(['order.client', 'assignedUser']);

            // Get the QR code URL for the job
            $qrCodeUrl = $job->getQrCodeUrl();

            // Update order status to in_progress if it's still in approved status
            if ($order->status === 'approved') {
                $order->status = 'in_progress';
                $order->save();
            }
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create jobs.',
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the job.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job created successfully',
            'job' => $job,
            'qr_code_url' => $qrCodeUrl,
        ], 201);
    }

    /**
     * Display the specified job.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Job $job)
    {
        // Check if user has permission to view this job
        $this->authorize('view', $job);

        // Load relationships
        $job->load(['order.client', 'assignedUser']);

        return response()->json($job);
    }

    /**
     * Update the specified job in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Job $job)
    {
        // Check if user has permission to update this job
        $this->authorize('update', $job);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_progress,completed',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        // If changing assigned user, check if they have the right role
        if (isset($validated['assigned_to'])) {
            $user = User::findOrFail($validated['assigned_to']);

            // Map phases to expected roles
            $phaseRoles = [
                'design' => 'Designer',
                'print' => 'Printer',
                'press' => 'Press',
                'cut' => 'Cutter',
                'sew' => 'Sewer',
                'qc' => 'QC',
                'iron_packing' => 'Packer',
            ];

            // Check if user's production role matches the expected role for the phase
            if ($user->production_role !== $phaseRoles[$job->phase]) {
                return response()->json([
                    'message' => "The assigned user must have the '{$phaseRoles[$job->phase]}' role",
                ], 422);
            }
        }

        $job->update($validated);

        // Load relationships for the response
        $job->load(['order.client', 'assignedUser']);

        return response()->json($job);
    }

    /**
     * Start the job.
     *
     * @return \Illuminate\Http\Response
     */
    public function startJob(Job $job)
    {
        // Check if user has permission to start this job
        $this->authorize('start', $job);

        // Check if the job is in the right status
        if ($job->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending jobs can be started',
            ], 422);
        }

        // For production phases, check if the previous phase is completed
        $phases = ['design', 'print', 'press', 'cut', 'sew', 'qc', 'iron_packing'];
        $currentPhaseIndex = array_search($job->phase, $phases);

        if ($currentPhaseIndex > 0) {
            $previousPhase = $phases[$currentPhaseIndex - 1];
            $previousJob = Job::where('order_id', $job->order_id)
                ->where('phase', $previousPhase)
                ->first();

            if (! $previousJob || $previousJob->status !== 'completed') {
                return response()->json([
                    'message' => "Cannot start this job until the {$previousPhase} phase is completed",
                ], 422);
            }
        }

        // Update the job
        $job->status = 'in_progress';
        $job->start_time = Carbon::now();
        $job->save();

        // Update the order status if this is the first job being started
        $order = $job->order;
        if ($order->status === 'approved') {
            $order->status = 'in_progress';
            $order->save();
        }

        // Load relationships for the response
        $job->load(['order.client', 'assignedUser']);

        return response()->json([
            'message' => 'Job started successfully',
            'job' => $job,
        ]);
    }

    /**
     * Complete the job.
     *
     * @return \Illuminate\Http\Response
     */
    public function completeJob(Job $job)
    {
        // Check if user has permission to complete this job
        $this->authorize('complete', $job);

        // Check if the job is in the right status
        if ($job->status !== 'in_progress') {
            return response()->json([
                'message' => 'Only in-progress jobs can be completed',
            ], 422);
        }

        // Update the job
        $job->status = 'completed';
        $job->end_time = Carbon::now();

        // Calculate duration in minutes
        if ($job->start_time) {
            $startTime = new Carbon($job->start_time);
            $job->duration = $startTime->diffInMinutes($job->end_time);
        }

        $job->save();

        // Check if this is the last job in the order
        $phases = ['design', 'print', 'press', 'cut', 'sew', 'qc', 'iron_packing'];
        $lastPhase = end($phases);

        if ($job->phase === $lastPhase) {
            // Check if all jobs for this order are completed
            $pendingJobs = Job::where('order_id', $job->order_id)
                ->where('status', '!=', 'completed')
                ->count();

            if ($pendingJobs === 0) {
                // Update the order status
                $order = $job->order;
                if ($order->delivery_method === 'shipping') {
                    $order->status = 'in_delivery';
                } else {
                    $order->status = 'ready_to_collect';
                }
                $order->save();
            }
        }

        // Load relationships for the response
        $job->load(['order.client', 'assignedUser']);

        return response()->json([
            'message' => 'Job completed successfully',
            'job' => $job,
        ]);
    }

    /**
     * Access job via QR code scan.
     *
     * @param  string  $hash
     * @return \Illuminate\Http\Response
     */
    public function getJobByQrCode($hash)
    {
        try {
            // Find the job by QR code hash
            $job = Job::where('qr_code_hash', $hash)->firstOrFail();

            // Check if user has permission to access this job via QR code
            $this->authorize('scan', $job);

            // Load relationships
            $job->load(['order.client', 'assignedUser']);

            return response()->json([
                'message' => 'Job accessed via QR code',
                'job' => $job,
                'actions' => [
                    'can_start' => $job->status === 'pending',
                    'can_complete' => $job->status === 'in_progress',
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Invalid QR code. Job not found.',
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to access this job.',
            ], 403);
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('QR code scan error: '.$e->getMessage());

            return response()->json([
                'message' => 'An error occurred while processing the QR code.',
            ], 500);
        }
    }

    /**
     * Remove the specified job from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Job $job)
    {
        // Check if user has permission to delete this job
        $this->authorize('delete', $job);

        // Don't allow deleting jobs that are in progress or completed
        if ($job->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete jobs that are in progress or completed',
            ], 422);
        }

        $job->delete();

        return response()->json(null, 204);
    }
}
