<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check if user has permission to view clients
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $clients = $query->orderBy('name')->paginate($perPage);

        return response()->json($clients);
    }

    /**
     * Store a newly created client in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has permission to create clients
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'billing_address' => 'required|string',
        ]);

        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    /**
     * Display the specified client.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        // Check if user has permission to view this client
        $this->authorize('view', $client);

        // Load the client's orders
        $client->load('orders');

        return response()->json($client);
    }

    /**
     * Update the specified client in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Client $client)
    {
        // Check if user has permission to update this client
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'billing_address' => 'sometimes|required|string',
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    /**
     * Remove the specified client from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        // Check if user has permission to delete this client
        $this->authorize('delete', $client);

        // Check if client has any orders
        if ($client->orders()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete client with existing orders',
            ], 422);
        }

        $client->delete();

        return response()->json(null, 204);
    }
}
