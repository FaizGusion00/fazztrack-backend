<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FileAttachmentController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDesignController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'FazzTrack API is running',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// Test Route
Route::get('/test', function () {
    return response()->json(['message' => 'Test route works']);
});

// Test Login Route
Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    try {
        $user = \App\Models\User::where('email', 'superadmin@fazztrack.com')->first();
        if ($user) {
            return response()->json(['message' => 'User found', 'user' => $user->name]);
        } else {
            return response()->json(['message' => 'User not found']);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Client Management
    Route::apiResource('clients', ClientController::class);

    // Order Management
    Route::apiResource('orders', OrderController::class);
    Route::get('orders/{order}/details', [OrderController::class, 'getOrderDetails']);
    Route::post('orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Order Items
    Route::apiResource('orders.items', OrderItemController::class)->shallow();

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve']);

    // Design Management
    Route::apiResource('designs', OrderDesignController::class);
    Route::post('designs/{design}/finalize', [OrderDesignController::class, 'finalize']);
    Route::post('designs/{design}/upload', [OrderDesignController::class, 'uploadDesign']);

    // Production Jobs
    Route::apiResource('jobs', JobController::class);
    Route::post('jobs/{job}/start', [JobController::class, 'startJob']);
    Route::post('jobs/{job}/end', [JobController::class, 'endJob']);
    Route::get('jobs/qr/{code}', [JobController::class, 'getJobByQrCode']);

    // File Attachments
    Route::post('files/upload', [FileAttachmentController::class, 'upload']);
    Route::get('files/{file}', [FileAttachmentController::class, 'download']);

    // Products
    Route::apiResource('products', ProductController::class);

    // User Management (SuperAdmin only)
    Route::apiResource('users', UserController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('sections', SectionController::class);
    Route::post('departments/{department}/sections', [DepartmentController::class, 'assignSections']);

    // Dashboard & Reports
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('dashboard/due-dates', [DashboardController::class, 'getDueDates']);

    // Reporting Endpoints
    Route::get('reports/sales', [ReportingController::class, 'salesReport']);
    Route::get('reports/products', [ReportingController::class, 'productReport']);
    Route::get('reports/clients', [ReportingController::class, 'clientReport']);
    Route::get('reports/production', [ReportingController::class, 'productionReport']);
    Route::get('reports/users', [ReportingController::class, 'userReport']);
});

// Public Tracking Routes
Route::post('tracking', [TrackingController::class, 'trackOrder']);
Route::get('tracking/order/{order_id}', [TrackingController::class, 'trackOrderById']);
