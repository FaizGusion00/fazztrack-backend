<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view payments
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query();

        // Filter by order
        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        // Filter by payment type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

        // Eager load relationships
        $query->with(['order.client', 'receiptFile', 'approver']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return response()->json($payments);
    }

    /**
     * Store a newly created payment in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create payments
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id',
            'type' => 'required|in:deposit_design,deposit_production,balance_payment',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,debit_card,check,other',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
            'receipt_file_id' => 'nullable|exists:file_attachments,file_id',
        ]);

        // Create the payment
        $payment = Payment::create($validated);

        // Load relationships for the response
        $payment->load(['order.client', 'receiptFile', 'approver']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        // Check if user has permission to view this payment
        $this->authorize('view', $payment);

        // Load relationships
        $payment->load(['order.client', 'receiptFile', 'approver']);

        return response()->json($payment);
    }

    /**
     * Update the specified payment in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        // Check if user has permission to update this payment
        $this->authorize('update', $payment);

        // Don't allow updating approved payments
        if ($payment->status === 'approved') {
            return response()->json([
                'message' => 'Cannot update approved payments',
            ], 422);
        }

        $validated = $request->validate([
            'type' => 'sometimes|required|in:deposit_design,deposit_production,balance_payment',
            'payment_method' => 'sometimes|required|in:cash,bank_transfer,credit_card,debit_card,check,other',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'remarks' => 'nullable|string',
            'receipt_file_id' => 'nullable|exists:file_attachments,file_id',
        ]);

        $payment->update($validated);

        // Load relationships for the response
        $payment->load(['order.client', 'receiptFile', 'approver']);

        return response()->json($payment);
    }

    /**
     * Approve a payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, Payment $payment)
    {
        // Check if user has permission to approve payments
        $this->authorize('approve', Payment::class);

        // Don't allow approving already approved payments
        if ($payment->status === 'approved') {
            return response()->json([
                'message' => 'Payment is already approved',
            ], 422);
        }

        // Update payment status to approved
        $payment->status = 'approved';
        $payment->approved_by = Auth::id();
        $payment->approved_at = now();
        $payment->save();

        // If this is a design deposit payment, check if we should update the order status
        if ($payment->type === 'deposit_design') {
            $order = $payment->order;

            // If order is still pending, update it to approved
            if ($order->status === 'pending') {
                $order->status = 'approved';
                $order->save();
            }
        }

        // Load relationships for the response
        $payment->load(['order.client', 'receiptFile', 'approver']);

        return response()->json([
            'message' => 'Payment approved successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Remove the specified payment from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        // Check if user has permission to delete this payment
        $this->authorize('delete', $payment);

        // Don't allow deleting approved payments
        if ($payment->status === 'approved') {
            return response()->json([
                'message' => 'Cannot delete approved payments',
            ], 422);
        }

        $payment->delete();

        return response()->json(null, 204);
    }
}
