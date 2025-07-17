<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function summary()
    {
        // Check if user has permission to view dashboard
        $this->authorize('viewDashboard');

        // Get counts
        $orderCount = Order::count();
        $clientCount = Client::count();
        $pendingOrderCount = Order::where('status', 'pending')->count();
        $inProgressOrderCount = Order::where('status', 'in_progress')->count();
        $completedOrderCount = Order::whereIn('status', ['completed', 'delivered'])->count();

        // Get payment statistics
        $totalPayments = Payment::sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->sum('amount');
        $approvedPayments = Payment::where('status', 'approved')->sum('amount');

        // Get orders due soon (within 7 days)
        $now = Carbon::now();
        $sevenDaysLater = $now->copy()->addDays(7);

        $designDueSoon = Order::where('design_due_date', '>=', $now)
            ->where('design_due_date', '<=', $sevenDaysLater)
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->count();

        $productionDueSoon = Order::where('production_due_date', '>=', $now)
            ->where('production_due_date', '<=', $sevenDaysLater)
            ->whereIn('status', ['approved', 'in_progress'])
            ->count();

        $deliveryDueSoon = Order::where('delivery_date', '>=', $now)
            ->where('delivery_date', '<=', $sevenDaysLater)
            ->whereIn('status', ['in_progress', 'qc', 'in_delivery', 'ready_to_collect'])
            ->count();

        // Get job statistics
        $pendingJobs = Job::where('status', 'pending')->count();
        $inProgressJobs = Job::where('status', 'in_progress')->count();
        $completedJobs = Job::where('status', 'completed')->count();

        return response()->json([
            'orders' => [
                'total' => $orderCount,
                'pending' => $pendingOrderCount,
                'in_progress' => $inProgressOrderCount,
                'completed' => $completedOrderCount,
            ],
            'clients' => [
                'total' => $clientCount,
            ],
            'payments' => [
                'total' => $totalPayments,
                'pending' => $pendingPayments,
                'approved' => $approvedPayments,
            ],
            'due_soon' => [
                'design' => $designDueSoon,
                'production' => $productionDueSoon,
                'delivery' => $deliveryDueSoon,
            ],
            'jobs' => [
                'pending' => $pendingJobs,
                'in_progress' => $inProgressJobs,
                'completed' => $completedJobs,
            ],
        ]);
    }

    /**
     * Get recent orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function recentOrders()
    {
        // Check if user has permission to view dashboard
        $this->authorize('viewDashboard');

        $recentOrders = Order::with(['client', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($recentOrders);
    }

    /**
     * Get orders due soon.
     *
     * @return \Illuminate\Http\Response
     */
    public function dueSoonOrders()
    {
        // Check if user has permission to view dashboard
        $this->authorize('viewDashboard');

        $now = Carbon::now();
        $sevenDaysLater = $now->copy()->addDays(7);

        $dueSoonOrders = Order::with(['client', 'creator'])
            ->where(function ($query) use ($now, $sevenDaysLater) {
                $query->where('design_due_date', '>=', $now)
                    ->where('design_due_date', '<=', $sevenDaysLater);
            })
            ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                $query->where('production_due_date', '>=', $now)
                    ->where('production_due_date', '<=', $sevenDaysLater);
            })
            ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                $query->where('delivery_date', '>=', $now)
                    ->where('delivery_date', '<=', $sevenDaysLater);
            })
            ->whereIn('status', ['pending', 'approved', 'in_progress', 'qc', 'in_delivery', 'ready_to_collect'])
            ->orderBy('design_due_date', 'asc')
            ->orderBy('production_due_date', 'asc')
            ->orderBy('delivery_date', 'asc')
            ->get();

        return response()->json($dueSoonOrders);
    }

    /**
     * Get monthly order statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function monthlyOrderStats(Request $request)
    {
        // Check if user has permission to view dashboard
        $this->authorize('viewDashboard');

        // Get year parameter, default to current year
        $year = $request->input('year', Carbon::now()->year);

        // Get monthly order counts
        $monthlyOrders = DB::table('orders')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        // Get monthly payment totals
        $monthlyPayments = DB::table('payments')
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $year)
            ->where('status', 'approved')
            ->groupBy(DB::raw('MONTH(payment_date)'))
            ->orderBy('month')
            ->get();

        // Format the data for all 12 months
        $formattedData = [];
        for ($month = 1; $month <= 12; $month++) {
            $orderCount = 0;
            $paymentTotal = 0;

            // Find order count for this month
            $orderData = $monthlyOrders->firstWhere('month', $month);
            if ($orderData) {
                $orderCount = $orderData->count;
            }

            // Find payment total for this month
            $paymentData = $monthlyPayments->firstWhere('month', $month);
            if ($paymentData) {
                $paymentTotal = $paymentData->total;
            }

            $formattedData[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'order_count' => $orderCount,
                'payment_total' => $paymentTotal,
            ];
        }

        return response()->json($formattedData);
    }

    /**
     * Get production efficiency statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function productionEfficiency(Request $request)
    {
        // Check if user has permission to view dashboard
        $this->authorize('viewDashboard');

        // Get date range parameters
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        // Get average job duration by phase
        $jobEfficiency = DB::table('jobs')
            ->select('phase', DB::raw('AVG(duration) as avg_duration'), DB::raw('COUNT(*) as job_count'))
            ->where('status', 'completed')
            ->whereNotNull('duration')
            ->whereBetween('end_time', [$startDate, $endDate])
            ->groupBy('phase')
            ->orderBy('phase')
            ->get();

        // Get average job duration by user
        $userEfficiency = DB::table('jobs')
            ->join('users', 'jobs.assigned_to', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.production_role',
                'jobs.phase',
                DB::raw('AVG(duration) as avg_duration'),
                DB::raw('COUNT(*) as job_count')
            )
            ->where('jobs.status', 'completed')
            ->whereNotNull('jobs.duration')
            ->whereBetween('jobs.end_time', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name', 'users.production_role', 'jobs.phase')
            ->orderBy('users.name')
            ->orderBy('jobs.phase')
            ->get();

        return response()->json([
            'date_range' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'job_efficiency' => $jobEfficiency,
            'user_efficiency' => $userEfficiency,
        ]);
    }

    /**
     * Get user-specific dashboard data.
     *
     * @return \Illuminate\Http\Response
     */
    public function userDashboard()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $department = $user->department?->name ?? 'Unknown';
        $data = [];

        // Common data for all users
        $data['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department' => $department,
            'production_role' => $user->production_role,
        ];

        // Department-specific data
        if ($department === 'Sales') {
            // For sales, show order statistics
            $data['orders'] = [
                'total_created' => $user->createdOrders()->count(),
                'pending' => $user->createdOrders()->where('status', 'pending')->count(),
                'approved' => $user->createdOrders()->where('status', 'approved')->count(),
                'in_progress' => $user->createdOrders()->where('status', 'in_progress')->count(),
                'completed' => $user->createdOrders()->whereIn('status', ['completed', 'delivered'])->count(),
            ];

            // Recent orders created by this user
            $data['recent_orders'] = $user->createdOrders()
                ->with('client')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Orders due soon
            $now = Carbon::now();
            $sevenDaysLater = $now->copy()->addDays(7);

            $data['due_soon_orders'] = $user->createdOrders()
                ->with('client')
                ->where(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('design_due_date', '>=', $now)
                        ->where('design_due_date', '<=', $sevenDaysLater);
                })
                ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('production_due_date', '>=', $now)
                        ->where('production_due_date', '<=', $sevenDaysLater);
                })
                ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('delivery_date', '>=', $now)
                        ->where('delivery_date', '<=', $sevenDaysLater);
                })
                ->whereIn('status', ['pending', 'approved', 'in_progress', 'qc', 'in_delivery', 'ready_to_collect'])
                ->orderBy('design_due_date', 'asc')
                ->orderBy('production_due_date', 'asc')
                ->orderBy('delivery_date', 'asc')
                ->limit(5)
                ->get();

        } elseif ($department === 'Designer') {
            // For designers, show design statistics
            $data['designs'] = [
                'total_assigned' => $user->designs()->count(),
                'new' => $user->designs()->where('status', 'new')->count(),
                'in_progress' => $user->designs()->where('status', 'in_progress')->count(),
                'finalized' => $user->designs()->where('status', 'finalized')->count(),
                'completed' => $user->designs()->where('status', 'completed')->count(),
            ];

            // Recent designs assigned to this user
            $data['recent_designs'] = $user->designs()
                ->with(['order.client'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Designs due soon
            $now = Carbon::now();
            $sevenDaysLater = $now->copy()->addDays(7);

            $data['due_soon_designs'] = $user->designs()
                ->join('orders', 'order_designs.order_id', '=', 'orders.order_id')
                ->with(['order.client'])
                ->where('orders.design_due_date', '>=', $now)
                ->where('orders.design_due_date', '<=', $sevenDaysLater)
                ->whereIn('order_designs.status', ['new', 'in_progress'])
                ->select('order_designs.*')
                ->orderBy('orders.design_due_date', 'asc')
                ->limit(5)
                ->get();

        } elseif ($user->production_role) {
            // For production staff, show job statistics
            $data['jobs'] = [
                'total_assigned' => $user->jobs()->count(),
                'pending' => $user->jobs()->where('status', 'pending')->count(),
                'in_progress' => $user->jobs()->where('status', 'in_progress')->count(),
                'completed' => $user->jobs()->where('status', 'completed')->count(),
            ];

            // Recent jobs assigned to this user
            $data['recent_jobs'] = $user->jobs()
                ->with(['order.client'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Jobs due soon
            $now = Carbon::now();
            $sevenDaysLater = $now->copy()->addDays(7);

            $data['due_soon_jobs'] = $user->jobs()
                ->join('orders', 'jobs.order_id', '=', 'orders.order_id')
                ->with(['order.client'])
                ->where('orders.production_due_date', '>=', $now)
                ->where('orders.production_due_date', '<=', $sevenDaysLater)
                ->whereIn('jobs.status', ['pending', 'in_progress'])
                ->select('jobs.*')
                ->orderBy('orders.production_due_date', 'asc')
                ->limit(5)
                ->get();

            // Job efficiency
            $completedJobs = $user->jobs()
                ->where('status', 'completed')
                ->whereNotNull('duration')
                ->get();

            $totalDuration = $completedJobs->sum('duration');
            $jobCount = $completedJobs->count();

            $data['efficiency'] = [
                'completed_job_count' => $jobCount,
                'total_duration_minutes' => $totalDuration,
                'avg_duration_minutes' => $jobCount > 0 ? round($totalDuration / $jobCount, 2) : 0,
            ];
        } elseif (in_array($department, ['Admin', 'SuperAdmin'])) {
            // For admin users, show summary statistics
            $data['summary'] = [
                'orders' => [
                    'total' => Order::count(),
                    'pending' => Order::where('status', 'pending')->count(),
                    'in_progress' => Order::where('status', 'in_progress')->count(),
                    'completed' => Order::whereIn('status', ['completed', 'delivered'])->count(),
                ],
                'payments' => [
                    'total' => Payment::sum('amount'),
                    'pending' => Payment::where('status', 'pending')->sum('amount'),
                    'approved' => Payment::where('status', 'approved')->sum('amount'),
                ],
            ];

            // Recent orders
            $data['recent_orders'] = Order::with(['client', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Recent payments
            $data['recent_payments'] = Payment::with(['order.client'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Orders due soon
            $now = Carbon::now();
            $sevenDaysLater = $now->copy()->addDays(7);

            $data['due_soon_orders'] = Order::with(['client', 'creator'])
                ->where(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('design_due_date', '>=', $now)
                        ->where('design_due_date', '<=', $sevenDaysLater);
                })
                ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('production_due_date', '>=', $now)
                        ->where('production_due_date', '<=', $sevenDaysLater);
                })
                ->orWhere(function ($query) use ($now, $sevenDaysLater) {
                    $query->where('delivery_date', '>=', $now)
                        ->where('delivery_date', '<=', $sevenDaysLater);
                })
                ->whereIn('status', ['pending', 'approved', 'in_progress', 'qc', 'in_delivery', 'ready_to_collect'])
                ->orderBy('design_due_date', 'asc')
                ->orderBy('production_due_date', 'asc')
                ->orderBy('delivery_date', 'asc')
                ->limit(5)
                ->get();
        }

        return response()->json($data);
    }
}
