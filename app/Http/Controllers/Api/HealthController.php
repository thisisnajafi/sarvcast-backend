<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    protected $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Get application health status
     */
    public function health()
    {
        $health = $this->monitoringService->checkApplicationHealth();
        
        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        
        return response()->json($health, $statusCode);
    }

    /**
     * Get application metrics
     */
    public function metrics()
    {
        $metrics = $this->monitoringService->getApplicationMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    /**
     * Get monitoring report
     */
    public function report()
    {
        $report = $this->monitoringService->generateMonitoringReport();
        
        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Get error rates
     */
    public function errorRates()
    {
        $errorRates = $this->monitoringService->getErrorRates();
        
        return response()->json([
            'success' => true,
            'data' => $errorRates
        ]);
    }

    /**
     * Get API performance metrics
     */
    public function apiPerformance()
    {
        $performance = $this->monitoringService->getApiPerformance();
        
        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }
}