<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view products
        $this->authorize('viewAny', Product::class);

        $query = Product::query();

        // Search by name or item code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $products = $query->orderBy('product_name', 'asc')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create products
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'item_code' => 'required|string|max:50|unique:products,item_code',
            'product_name' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $product = Product::create([
            'item_code' => $validated['item_code'],
            'product_name' => $validated['product_name'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        // Check if user has permission to view this product
        $this->authorize('view', $product);

        // Get usage statistics
        $usageStats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->where('order_items.product_id', $product->product_id)
            ->select(
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->first();

        $product->usage_stats = [
            'order_count' => $usageStats->order_count ?? 0,
            'total_quantity' => $usageStats->total_quantity ?? 0,
        ];

        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        // Check if user has permission to update this product
        $this->authorize('update', $product);

        $validated = $request->validate([
            'item_code' => 'sometimes|required|string|max:50|unique:products,item_code,'.$product->product_id.',product_id',
            'product_name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Remove the specified product from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        // Check if user has permission to delete this product
        $this->authorize('delete', $product);

        // Check if the product is used in any orders
        $usedInOrders = OrderItem::where('product_id', $product->product_id)->exists();

        if ($usedInOrders) {
            return response()->json([
                'message' => 'Cannot delete product that is used in orders. Consider marking it as inactive instead.',
            ], 422);
        }

        $product->delete();

        return response()->json(null, 204);
    }
}
