<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    /**
     * Generate sales report by period
     *
     * @return \Illuminate\Http\Response
     */
    public function salesReport(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:day,week,month,year',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $groupBy = $request->group_by;

        // Format for grouping by different periods
        $dateFormat = [
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u', // ISO week number
            'month' => '%Y-%m',
            'year' => '%Y',
        ][$groupBy];

        // Get sales data grouped by the specified period
        $salesData = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as average_order_value')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Get payment data grouped by the specified period
        $paymentData = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'approved')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('SUM(amount) as total_payments')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Combine sales and payment data
        $reportData = $salesData->map(function ($item) use ($paymentData) {
            $period = $item->period;
            $payment = $paymentData->get($period);

            return [
                'period' => $period,
                'order_count' => $item->order_count,
                'total_sales' => $item->total_sales,
                'average_order_value' => $item->average_order_value,
                'total_payments' => $payment ? $payment->total_payments : 0,
                'collection_rate' => $item->total_sales > 0 ?
                    ($payment ? ($payment->total_payments / $item->total_sales) * 100 : 0) : 0,
            ];
        });

        // Calculate summary statistics
        $summary = [
            'total_orders' => $salesData->sum('order_count'),
            'total_sales' => $salesData->sum('total_sales'),
            'total_payments' => $paymentData->sum('total_payments'),
            'overall_collection_rate' => $salesData->sum('total_sales') > 0 ?
                ($paymentData->sum('total_payments') / $salesData->sum('total_sales')) * 100 : 0,
            'average_order_value' => $salesData->avg('average_order_value'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $reportData,
                'summary' => $summary,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'group_by' => $groupBy,
                ],
            ],
        ]);
    }

    /**
     * Generate product performance report
     *
     * @return \Illuminate\Http\Response
     */
    public function productReport(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sort_by' => 'nullable|in:quantity,revenue',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $sortBy = $request->sort_by ?? 'revenue';
        $limit = $request->limit ?? 10;

        // Get product performance data
        $productData = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy($sortBy === 'quantity' ? 'total_quantity' : 'total_revenue', 'desc')
            ->limit($limit)
            ->get();

        // Calculate total revenue and quantity for percentage calculations
        $totalRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->sum(DB::raw('order_items.price * order_items.quantity'));

        $totalQuantity = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->sum('order_items.quantity');

        // Add percentage calculations to product data
        $reportData = $productData->map(function ($item) use ($totalRevenue, $totalQuantity) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'total_quantity' => $item->total_quantity,
                'total_revenue' => $item->total_revenue,
                'order_count' => $item->order_count,
                'revenue_percentage' => $totalRevenue > 0 ?
                    ($item->total_revenue / $totalRevenue) * 100 : 0,
                'quantity_percentage' => $totalQuantity > 0 ?
                    ($item->total_quantity / $totalQuantity) * 100 : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $reportData,
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_quantity' => $totalQuantity,
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Generate client performance report
     *
     * @return \Illuminate\Http\Response
     */
    public function clientReport(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sort_by' => 'nullable|in:orders,revenue',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $sortBy = $request->sort_by ?? 'revenue';
        $limit = $request->limit ?? 10;

        // Get client performance data
        $clientData = Order::join('clients', 'orders.client_id', '=', 'clients.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'clients.id',
                'clients.name',
                'clients.email',
                'clients.phone',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as total_revenue'),
                DB::raw('AVG(orders.total_amount) as average_order_value'),
                DB::raw('MIN(orders.created_at) as first_order_date'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.email', 'clients.phone')
            ->orderBy($sortBy === 'orders' ? 'order_count' : 'total_revenue', 'desc')
            ->limit($limit)
            ->get();

        // Calculate total revenue and orders for percentage calculations
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Add percentage calculations to client data
        $reportData = $clientData->map(function ($item) use ($totalRevenue, $totalOrders) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'phone' => $item->phone,
                'order_count' => $item->order_count,
                'total_revenue' => $item->total_revenue,
                'average_order_value' => $item->average_order_value,
                'first_order_date' => $item->first_order_date,
                'last_order_date' => $item->last_order_date,
                'revenue_percentage' => $totalRevenue > 0 ?
                    ($item->total_revenue / $totalRevenue) * 100 : 0,
                'order_percentage' => $totalOrders > 0 ?
                    ($item->order_count / $totalOrders) * 100 : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $reportData,
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
                    'total_clients' => $clientData->count(),
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Generate production efficiency report
     *
     * @return \Illuminate\Http\Response
     */
    public function productionReport(Request $request)
    {
        $this->authorize('viewAny', Job::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:phase,user,both',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $groupBy = $request->group_by;

        // Base query for completed jobs
        $baseQuery = Job::whereBetween('completed_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at');

        if ($groupBy === 'phase') {
            // Group by production phase
            $reportData = $baseQuery->select(
                'phase',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_duration_minutes'),
                DB::raw('MIN(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as min_duration_minutes'),
                DB::raw('MAX(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as max_duration_minutes')
            )
                ->groupBy('phase')
                ->orderBy('phase')
                ->get()
                ->map(function ($item) {
                    return [
                        'phase' => $item->phase,
                        'job_count' => $item->job_count,
                        'avg_duration_minutes' => $item->avg_duration_minutes,
                        'min_duration_minutes' => $item->min_duration_minutes,
                        'max_duration_minutes' => $item->max_duration_minutes,
                        'avg_duration_formatted' => $this->formatDuration($item->avg_duration_minutes),
                    ];
                });
        } elseif ($groupBy === 'user') {
            // Group by user
            $reportData = $baseQuery->join('users', 'jobs.assigned_to', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.production_role',
                    DB::raw('COUNT(*) as job_count'),
                    DB::raw('AVG(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as avg_duration_minutes'),
                    DB::raw('MIN(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as min_duration_minutes'),
                    DB::raw('MAX(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as max_duration_minutes')
                )
                ->groupBy('users.id', 'users.name', 'users.production_role')
                ->orderBy('users.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->id,
                        'user_name' => $item->name,
                        'production_role' => $item->production_role,
                        'job_count' => $item->job_count,
                        'avg_duration_minutes' => $item->avg_duration_minutes,
                        'min_duration_minutes' => $item->min_duration_minutes,
                        'max_duration_minutes' => $item->max_duration_minutes,
                        'avg_duration_formatted' => $this->formatDuration($item->avg_duration_minutes),
                    ];
                });
        } else { // both
            // Group by phase and user
            $reportData = $baseQuery->join('users', 'jobs.assigned_to', '=', 'users.id')
                ->select(
                    'jobs.phase',
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('COUNT(*) as job_count'),
                    DB::raw('AVG(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as avg_duration_minutes'),
                    DB::raw('MIN(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as min_duration_minutes'),
                    DB::raw('MAX(TIMESTAMPDIFF(MINUTE, jobs.started_at, jobs.completed_at)) as max_duration_minutes')
                )
                ->groupBy('jobs.phase', 'users.id', 'users.name')
                ->orderBy('jobs.phase')
                ->orderBy('users.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'phase' => $item->phase,
                        'user_id' => $item->user_id,
                        'user_name' => $item->user_name,
                        'job_count' => $item->job_count,
                        'avg_duration_minutes' => $item->avg_duration_minutes,
                        'min_duration_minutes' => $item->min_duration_minutes,
                        'max_duration_minutes' => $item->max_duration_minutes,
                        'avg_duration_formatted' => $this->formatDuration($item->avg_duration_minutes),
                    ];
                });
        }

        // Calculate overall statistics
        $overallStats = $baseQuery->select(
            DB::raw('COUNT(*) as total_jobs'),
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as overall_avg_duration')
        )->first();

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $reportData,
                'summary' => [
                    'total_jobs' => $overallStats->total_jobs,
                    'overall_avg_duration_minutes' => $overallStats->overall_avg_duration,
                    'overall_avg_duration_formatted' => $this->formatDuration($overallStats->overall_avg_duration),
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'group_by' => $groupBy,
                ],
            ],
        ]);
    }

    /**
     * Generate user performance report
     *
     * @return \Illuminate\Http\Response
     */
    public function userReport(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $department = $request->department;

        // Base query for users
        $usersQuery = User::with('department')
            ->when($department, function ($query) use ($department) {
                return $query->whereHas('department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            });

        $users = $usersQuery->get();

        $reportData = [];

        foreach ($users as $user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department->name,
                'production_role' => $user->production_role,
            ];

            // Sales metrics
            if ($user->department->name === 'Sales') {
                $orders = Order::where('created_by', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $userData['sales'] = [
                    'orders_created' => $orders->count(),
                    'total_sales' => $orders->sum('total_amount'),
                    'average_order_value' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
                ];
            }

            // Designer metrics
            if ($user->department->name === 'Designer') {
                $designs = OrderDesign::where('designer_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $finalized = $designs->where('status', 'finalized')->count();

                $userData['design'] = [
                    'designs_assigned' => $designs->count(),
                    'designs_finalized' => $finalized,
                    'completion_rate' => $designs->count() > 0 ? ($finalized / $designs->count()) * 100 : 0,
                ];
            }

            // Production metrics
            if ($user->production_role) {
                $jobs = Job::where('assigned_to', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $completed = $jobs->where('status', 'completed')->count();
                $avgDuration = $jobs->where('status', 'completed')
                    ->whereNotNull('started_at')
                    ->whereNotNull('completed_at')
                    ->avg(function ($job) {
                        return Carbon::parse($job->started_at)->diffInMinutes(Carbon::parse($job->completed_at));
                    }) ?? 0;

                $userData['production'] = [
                    'jobs_assigned' => $jobs->count(),
                    'jobs_completed' => $completed,
                    'completion_rate' => $jobs->count() > 0 ? ($completed / $jobs->count()) * 100 : 0,
                    'avg_duration_minutes' => $avgDuration,
                    'avg_duration_formatted' => $this->formatDuration($avgDuration),
                ];
            }

            $reportData[] = $userData;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report_data' => $reportData,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'department' => $department ?? 'All',
                ],
            ],
        ]);
    }

    /**
     * Format minutes into a human-readable duration string
     *
     * @param  float  $minutes
     * @return string
     */
    private function formatDuration($minutes)
    {
        if (! $minutes) {
            return '0 minutes';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        $result = '';
        if ($hours > 0) {
            $result .= $hours.' hour'.($hours != 1 ? 's' : '').' ';
        }
        if ($mins > 0) {
            $result .= $mins.' minute'.($mins != 1 ? 's' : '');
        }

        return trim($result);
    }
}
