<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use Illuminate\Http\JsonResponse;

class SmsOverviewController extends Controller
{
    public function apiStatistics(): JsonResponse
    {
        $today = now()->toDateString();

        return AdminApiResponse::success([
            'templates' => [
                'total' => SmsTemplate::count(),
                'active' => SmsTemplate::where('is_active', true)->count(),
            ],
            'campaigns' => [
                'total' => SmsCampaign::count(),
                'draft' => SmsCampaign::where('status', SmsCampaign::STATUS_DRAFT)->count(),
                'processing' => SmsCampaign::whereIn('status', [
                    SmsCampaign::STATUS_QUEUED,
                    SmsCampaign::STATUS_PROCESSING,
                ])->count(),
                'completed' => SmsCampaign::where('status', SmsCampaign::STATUS_COMPLETED)->count(),
                'total_sent' => (int) SmsCampaign::sum('sent_count'),
                'total_failed' => (int) SmsCampaign::sum('failed_count'),
            ],
            'logs' => [
                'today_total' => SmsLog::whereDate('created_at', $today)->count(),
                'today_sent' => SmsLog::whereDate('created_at', $today)->where('status', 'sent')->count(),
                'today_delivered' => SmsLog::whereDate('created_at', $today)->where('status', 'delivered')->count(),
                'today_failed' => SmsLog::whereDate('created_at', $today)->where('status', 'failed')->count(),
                'pending_delivery' => SmsLog::where('status', 'sent')->whereNotNull('message_id')->whereNull('delivered_at')->count(),
            ],
        ]);
    }
}
