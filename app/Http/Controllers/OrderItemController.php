<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the order items for a specific order.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Order $order)
    {
        // Check if user has permission to view this order's items
        $this->authorize('view', $order);

        $orderItems = $order->orderItems()->with('product')->get();

        return response()->json($orderItems);
    }

    /**
     * Store a newly created order item in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Order $order)
    {
        // Check if user has permission to update this order
        $this->authorize('update', $order);

        // Only allow adding items to pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Can only add items to pending orders',
            ], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $orderItem = new OrderItem;
        $orderItem->order_id = $order->order_id;
        $orderItem->product_id = $validated['product_id'];
        $orderItem->quantity = $validated['quantity'];
        $orderItem->price = $validated['price'];
        $orderItem->save();

        // Load the product relationship
        $orderItem->load('product');

        return response()->json($orderItem, 201);
    }

    /**
     * Display the specified order item.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(OrderItem $orderItem)
    {
        // Check if user has permission to view the parent order
        $this->authorize('view', $orderItem->order);

        // Load the product relationship
        $orderItem->load('product');

        return response()->json($orderItem);
    }

    /**
     * Update the specified order item in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        // Check if user has permission to update the parent order
        $this->authorize('update', $orderItem->order);

        // Only allow updating items in pending orders
        if ($orderItem->order->status !== 'pending') {
            return response()->json([
                'message' => 'Can only update items in pending orders',
            ], 422);
        }

        $validated = $request->validate([
            'product_id' => 'sometimes|required|exists:products,product_id',
            'quantity' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        $orderItem->update($validated);

        // Load the product relationship
        $orderItem->load('product');

        return response()->json($orderItem);
    }

    /**
     * Remove the specified order item from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderItem $orderItem)
    {
        // Check if user has permission to update the parent order
        $this->authorize('update', $orderItem->order);

        // Only allow deleting items from pending orders
        if ($orderItem->order->status !== 'pending') {
            return response()->json([
                'message' => 'Can only delete items from pending orders',
            ], 422);
        }

        // Ensure the order has at least one item remaining
        if ($orderItem->order->orderItems()->count() <= 1) {
            return response()->json([
                'message' => 'Order must have at least one item',
            ], 422);
        }

        $orderItem->delete();

        return response()->json(null, 204);
    }
}
