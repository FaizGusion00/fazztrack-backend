<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Track an order by its tracking ID.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Track an order by its tracking ID.
     *
     * @return \Illuminate\Http\Response
     */
    public function trackOrder(Request $request)
    {
        try {
            $request->validate([
                'tracking_id' => 'required|string',
            ]);

            $trackingId = $request->tracking_id;

            // Find the order by tracking ID
            $order = Order::where('tracking_id', $trackingId)->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found with the provided tracking ID',
                ], 404);
            }

            return $this->getOrderTrackingData($order);
        } catch (Exception $e) {
            Log::error('Error tracking order: '.$e->getMessage(), [
                'tracking_id' => $request->tracking_id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while tracking the order. Please try again later.',
            ], 500);
        }
    }

    /**
     * Track an order by its order ID.
     *
     * @param  string  $orderId
     * @return \Illuminate\Http\Response
     */
    public function trackOrderById($orderId)
    {
        try {
            // Find the order by order ID
            $order = Order::find($orderId);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found with the provided order ID',
                ], 404);
            }

            return $this->getOrderTrackingData($order);
        } catch (Exception $e) {
            Log::error('Error tracking order by ID: '.$e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while tracking the order. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get tracking data for an order.
     *
     * @return \Illuminate\Http\Response
     */
    private function getOrderTrackingData(Order $order)
    {
        try {
            // Get job progress
            $jobs = Job::where('order_id', $order->order_id)
                ->orderBy('created_at', 'asc')
                ->get(['phase', 'status', 'start_time', 'end_time']);

            // Prepare the response data
            $trackingData = [
                'success' => true,
                'order_id' => $order->order_id,
                'tracking_id' => $order->tracking_id,
                'job_name' => $order->job_name,
                'status' => $order->status,
                'created_at' => $order->created_at->format('Y-m-d'),
                'delivery_method' => $order->delivery_method,
                'estimated_delivery' => $order->estimated_delivery_date ? $order->estimated_delivery_date->format('Y-m-d') : null,
            ];

            // Add delivery tracking info if available
            if ($order->delivery_method === 'delivery' && $order->delivery_tracking_id) {
                $trackingData['delivery_tracking_id'] = $order->delivery_tracking_id;
            }

            // Add job progress
            $jobProgress = [];
            $phases = ['design', 'print', 'press', 'cut', 'sew', 'qc', 'iron_packing'];
            $phaseNames = [
                'design' => 'Design',
                'print' => 'Printing',
                'press' => 'Heat Press',
                'cut' => 'Cutting',
                'sew' => 'Sewing',
                'qc' => 'Quality Control',
                'iron_packing' => 'Ironing & Packing',
            ];

            foreach ($phases as $phase) {
                $job = $jobs->firstWhere('phase', $phase);

                $jobProgress[] = [
                    'phase' => $phase,
                    'phase_name' => $phaseNames[$phase],
                    'status' => $job ? $job->status : 'not_started',
                    'start_time' => $job && $job->start_time ? $job->start_time->format('Y-m-d H:i') : null,
                    'end_time' => $job && $job->end_time ? $job->end_time->format('Y-m-d H:i') : null,
                ];
            }

            $trackingData['job_progress'] = $jobProgress;

            // Calculate overall progress percentage
            $totalPhases = count($phases);
            $completedPhases = 0;
            $inProgressPhases = 0;

            foreach ($jobProgress as $progress) {
                if ($progress['status'] === 'completed') {
                    $completedPhases++;
                } elseif ($progress['status'] === 'in_progress') {
                    $inProgressPhases++;
                }
            }

            $progressPercentage = $totalPhases > 0 ?
                round(($completedPhases + ($inProgressPhases * 0.5)) / $totalPhases * 100) : 0;

            $trackingData['progress_percentage'] = $progressPercentage;

            return response()->json($trackingData);
        } catch (Exception $e) {
            Log::error('Error generating order tracking data: '.$e->getMessage(), [
                'order_id' => $order->order_id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
