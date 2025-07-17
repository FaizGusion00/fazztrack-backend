<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view orders
        $this->authorize('viewAny', Order::class);

        $query = Order::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // Search by job name
        if ($request->has('search')) {
            $query->where('job_name', 'like', "%{$request->search}%");
        }

        // For production staff, only show orders assigned to them
        $user = Auth::user();
        if ($user->department->name === 'Production Staff') {
            $query->whereHas('jobs', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        // For designers, only show orders assigned to them
        if ($user->department->name === 'Designer') {
            $query->whereHas('design', function ($q) use ($user) {
                $q->where('designer_id', $user->id);
            });
        }

        // Eager load relationships
        $query->with(['client', 'creator', 'orderItems.product']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Store a newly created order in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create orders
        $this->authorize('create', Order::class);

        // Validate basic order information
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'job_name' => 'required|string|max:255',
            'delivery_method' => 'required|in:self_collect,delivery',
            'shipping_address' => 'required_if:delivery_method,delivery|nullable|string',
            'due_date_design' => 'required|date|after_or_equal:today',
            'due_date_production' => 'required|date|after_or_equal:due_date_design',
            'estimated_delivery_date' => 'required|date|after_or_equal:due_date_production',
            'link_download' => 'nullable|url|max:255',

            // Order items validation
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',

            // Payment validation
            'payments' => 'required|array|min:1',
            'payments.*.type' => 'required|in:deposit_design,deposit_production,balance_payment',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_date' => 'required|date',
            'payments.*.remarks' => 'nullable|string',
            'payments.*.receipt_file_id' => 'nullable|exists:file_attachments,file_id',
        ]);

        // Start a database transaction
        return DB::transaction(function () use ($validated) {
            // Create the order
            $order = new Order;
            $order->client_id = $validated['client_id'];
            $order->created_by = Auth::id();
            $order->job_name = $validated['job_name'];
            $order->status = 'pending';
            $order->delivery_method = $validated['delivery_method'];
            $order->shipping_address = $validated['shipping_address'] ?? null;
            $order->due_date_design = $validated['due_date_design'];
            $order->due_date_production = $validated['due_date_production'];
            $order->estimated_delivery_date = $validated['estimated_delivery_date'];
            $order->link_download = $validated['link_download'] ?? null;
            $order->save();

            // Create order items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Create payments
            foreach ($validated['payments'] as $payment) {
                Payment::create([
                    'order_id' => $order->order_id,
                    'type' => $payment['type'],
                    'amount' => $payment['amount'],
                    'payment_date' => $payment['payment_date'],
                    'remarks' => $payment['remarks'] ?? null,
                    'receipt_file_id' => $payment['receipt_file_id'] ?? null,
                ]);
            }

            // Load relationships for the response
            $order->load(['client', 'creator', 'orderItems.product', 'payments']);

            return response()->json($order, 201);
        });
    }

    /**
     * Display the specified order.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        // Check if user has permission to view this order
        $this->authorize('view', $order);

        // Load relationships
        $order->load([
            'client',
            'creator',
            'orderItems.product',
            'payments.receiptFile',
            'design.designer',
            'design.designFile',
            'jobs.assignedTo',
        ]);

        return response()->json($order);
    }

    /**
     * Get detailed information about an order.
     *
     * @return \Illuminate\Http\Response
     */
    public function getOrderDetails(Order $order)
    {
        // Check if user has permission to view this order
        $this->authorize('view', $order);

        // Load all relationships for a complete view
        $order->load([
            'client',
            'creator',
            'orderItems.product',
            'payments.receiptFile',
            'design.designer',
            'design.designFile',
            'jobs.assignedTo',
        ]);

        // Calculate totals
        $totalAmount = $order->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $totalPaid = $order->payments->where(function ($payment) {
            // Only count approved payments or those that don't need approval
            return $payment->status === 'approved' || $payment->status === null;
        })->sum('amount');

        $balance = $totalAmount - $totalPaid;

        // Get job progress
        $jobProgress = [
            'total' => $order->jobs->count(),
            'completed' => $order->jobs->where('status', 'completed')->count(),
            'in_progress' => $order->jobs->where('status', 'in_progress')->count(),
            'pending' => $order->jobs->where('status', 'pending')->count(),
        ];

        return response()->json([
            'order' => $order,
            'financials' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ],
            'job_progress' => $jobProgress,
        ]);
    }

    /**
     * Update the specified order in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        // Check if user has permission to update this order
        $this->authorize('update', $order);

        // Validate the request
        $validated = $request->validate([
            'job_name' => 'sometimes|required|string|max:255',
            'delivery_method' => 'sometimes|required|in:self_collect,delivery',
            'shipping_address' => 'sometimes|required_if:delivery_method,delivery|nullable|string',
            'delivery_tracking_id' => 'nullable|string|max:100',
            'due_date_design' => 'sometimes|required|date',
            'due_date_production' => 'sometimes|required|date|after_or_equal:due_date_design',
            'estimated_delivery_date' => 'sometimes|required|date|after_or_equal:due_date_production',
            'link_download' => 'nullable|url|max:255',
        ]);

        // Update the order
        $order->update($validated);

        // Reload the order with relationships
        $order->load(['client', 'creator', 'orderItems.product', 'payments']);

        return response()->json($order);
    }

    /**
     * Update the status of an order.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Check if user has permission to update this order's status
        $this->authorize('updateStatus', $order);

        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,in_progress,qc_packaging,in_delivery,ready_to_collect,completed',
        ]);

        // Update the order status
        $order->status = $validated['status'];
        $order->save();

        return response()->json(['message' => 'Order status updated successfully', 'order' => $order]);
    }

    /**
     * Remove the specified order from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        // Check if user has permission to delete this order
        $this->authorize('delete', $order);

        // Only allow deletion of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be deleted',
            ], 422);
        }

        // Delete the order (cascade will handle related records)
        $order->delete();

        return response()->json(null, 204);
    }
}
