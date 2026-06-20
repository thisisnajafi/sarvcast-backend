<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\CorporateSponsorship;
use App\Models\InfluencerCampaign;
use App\Models\SchoolPartnership;
use App\Models\TeacherAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerProgramsApiController extends Controller
{
    public function teachersIndex(Request $request)
    {
        $query = TeacherAccount::with('user');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"));
            });
        }

        return AdminApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 15), 100))
        );
    }

    public function teachersShow(TeacherAccount $teacher)
    {
        return AdminApiResponse::success($teacher->load('user'));
    }

    public function teachersStatistics()
    {
        return AdminApiResponse::success([
            'total' => TeacherAccount::count(),
            'verified' => TeacherAccount::where('is_verified', true)->count(),
            'pending' => TeacherAccount::where('status', 'pending')->count(),
            'active' => TeacherAccount::where('status', 'active')->count(),
            'suspended' => TeacherAccount::where('status', 'suspended')->count(),
        ]);
    }

    public function teachersBulkAction(Request $request)
    {
        return $this->runBulk($request, 'teacher_ids', TeacherAccount::class, [
            'verify' => fn ($q) => $q->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'active']),
            'suspend' => fn ($q) => $q->update(['status' => 'suspended']),
            'activate' => fn ($q) => $q->update(['status' => 'active']),
            'delete' => fn ($q) => $q->delete(),
        ]);
    }

    public function teachersExport(Request $request): StreamedResponse
    {
        $query = TeacherAccount::with('user');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"));
            });
        }

        return $this->streamPartnerExport(
            'teachers-'.now()->format('Y-m-d-His').'.csv',
            ['id', 'institution_name', 'institution_type', 'user_name', 'user_phone', 'status', 'is_verified', 'student_count', 'created_at'],
            $query->orderByDesc('created_at'),
            function ($row) {
                $userName = $row->user
                    ? trim(($row->user->first_name ?? '').' '.($row->user->last_name ?? ''))
                    : '';

                return [
                    $row->id,
                    $row->institution_name,
                    $row->institution_type,
                    $userName,
                    $row->user?->phone_number ?? '',
                    $row->status,
                    $row->is_verified ? '1' : '0',
                    $row->student_count,
                    $row->created_at?->toIso8601String(),
                ];
            }
        );
    }

    public function schoolsIndex(Request $request)
    {
        $query = SchoolPartnership::with(['affiliatePartner', 'assignedTeacher']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('school_name', 'like', "%{$search}%");
        }

        return AdminApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 15), 100))
        );
    }

    public function schoolsShow(SchoolPartnership $school)
    {
        return AdminApiResponse::success($school->load(['affiliatePartner', 'assignedTeacher']));
    }

    public function schoolsStatistics()
    {
        return AdminApiResponse::success([
            'total' => SchoolPartnership::count(),
            'verified' => SchoolPartnership::where('is_verified', true)->count(),
            'pending' => SchoolPartnership::where('status', 'pending')->count(),
            'active' => SchoolPartnership::where('status', 'active')->count(),
            'suspended' => SchoolPartnership::where('status', 'suspended')->count(),
        ]);
    }

    public function schoolsBulkAction(Request $request)
    {
        return $this->runBulk($request, 'school_ids', SchoolPartnership::class, [
            'verify' => fn ($q) => $q->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'active']),
            'suspend' => fn ($q) => $q->update(['status' => 'suspended']),
            'activate' => fn ($q) => $q->update(['status' => 'active']),
            'delete' => fn ($q) => $q->delete(),
        ]);
    }

    public function schoolsExport(Request $request): StreamedResponse
    {
        $query = SchoolPartnership::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('school_name', 'like', '%'.$request->search.'%');
        }

        return $this->streamPartnerExport(
            'schools-'.now()->format('Y-m-d-His').'.csv',
            ['id', 'school_name', 'school_type', 'school_level', 'status', 'is_verified', 'student_count', 'contact_person', 'contact_phone', 'created_at'],
            $query->orderByDesc('created_at'),
            fn ($row) => [
                $row->id,
                $row->school_name,
                $row->school_type,
                $row->school_level,
                $row->status,
                $row->is_verified ? '1' : '0',
                $row->student_count,
                $row->contact_person,
                $row->contact_phone,
                $row->created_at?->toIso8601String(),
            ]
        );
    }

    public function influencersIndex(Request $request)
    {
        $query = InfluencerCampaign::with('affiliatePartner');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('campaign_name', 'like', "%{$search}%");
        }

        return AdminApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 15), 100))
        );
    }

    public function influencersShow(InfluencerCampaign $influencer)
    {
        return AdminApiResponse::success($influencer->load('affiliatePartner'));
    }

    public function influencersStatistics()
    {
        return AdminApiResponse::success([
            'total' => InfluencerCampaign::count(),
            'pending' => InfluencerCampaign::where('status', 'pending')->count(),
            'active' => InfluencerCampaign::where('status', 'active')->count(),
            'suspended' => InfluencerCampaign::where('status', 'suspended')->count(),
        ]);
    }

    public function influencersBulkAction(Request $request)
    {
        return $this->runBulk($request, 'influencer_ids', InfluencerCampaign::class, [
            'verify' => fn ($q) => $q->update(['status' => 'active']),
            'suspend' => fn ($q) => $q->update(['status' => 'suspended']),
            'activate' => fn ($q) => $q->update(['status' => 'active']),
            'delete' => fn ($q) => $q->delete(),
        ]);
    }

    public function influencersExport(Request $request): StreamedResponse
    {
        $query = InfluencerCampaign::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('campaign_name', 'like', '%'.$request->search.'%');
        }

        return $this->streamPartnerExport(
            'influencers-'.now()->format('Y-m-d-His').'.csv',
            ['id', 'campaign_name', 'campaign_type', 'content_type', 'status', 'start_date', 'end_date', 'compensation_per_post', 'created_at'],
            $query->orderByDesc('created_at'),
            fn ($row) => [
                $row->id,
                $row->campaign_name,
                $row->campaign_type,
                $row->content_type,
                $row->status,
                $row->start_date?->format('Y-m-d'),
                $row->end_date?->format('Y-m-d'),
                $row->compensation_per_post,
                $row->created_at?->toIso8601String(),
            ]
        );
    }

    public function corporateIndex(Request $request)
    {
        $query = CorporateSponsorship::with('affiliatePartner');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('company_name', 'like', '%'.$request->search.'%');
        }

        return AdminApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 15), 100))
        );
    }

    public function corporateShow(CorporateSponsorship $corporate)
    {
        return AdminApiResponse::success($corporate->load('affiliatePartner'));
    }

    public function corporateStatistics()
    {
        return AdminApiResponse::success([
            'total' => CorporateSponsorship::count(),
            'verified' => CorporateSponsorship::where('is_verified', true)->count(),
            'pending' => CorporateSponsorship::where('status', 'pending')->count(),
            'active' => CorporateSponsorship::where('status', 'active')->count(),
            'suspended' => CorporateSponsorship::where('status', 'suspended')->count(),
            'total_amount' => (float) CorporateSponsorship::sum('sponsorship_amount'),
        ]);
    }

    public function corporateBulkAction(Request $request)
    {
        return $this->runBulk($request, 'corporate_ids', CorporateSponsorship::class, [
            'verify' => fn ($q) => $q->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'active']),
            'suspend' => fn ($q) => $q->update(['status' => 'suspended']),
            'activate' => fn ($q) => $q->update(['status' => 'active']),
            'delete' => fn ($q) => $q->delete(),
        ]);
    }

    public function corporateExport(Request $request): StreamedResponse
    {
        $query = CorporateSponsorship::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('company_name', 'like', '%'.$request->search.'%');
        }

        return $this->streamPartnerExport(
            'corporate-'.now()->format('Y-m-d-His').'.csv',
            ['id', 'company_name', 'company_type', 'industry', 'status', 'is_verified', 'sponsorship_amount', 'currency', 'contact_person', 'created_at'],
            $query->orderByDesc('created_at'),
            fn ($row) => [
                $row->id,
                $row->company_name,
                $row->company_type,
                $row->industry,
                $row->status,
                $row->is_verified ? '1' : '0',
                $row->sponsorship_amount,
                $row->currency,
                $row->contact_person,
                $row->created_at?->toIso8601String(),
            ]
        );
    }

    /**
     * @param  callable(mixed): array<int, scalar|null>  $mapRow
     */
    private function streamPartnerExport(string $filename, array $headers, $query, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $query, $mapRow) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            $query->chunk(500, function ($rows) use ($handle, $mapRow) {
                foreach ($rows as $row) {
                    fputcsv($handle, $mapRow($row));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function runBulk(Request $request, string $idsKey, string $modelClass, array $actions)
    {
        $ids = $request->input($idsKey, $request->input('selected_items', []));
        $validator = validator([
            'action' => $request->input('action'),
            $idsKey => $ids,
        ], [
            'action' => 'required|in:'.implode(',', array_keys($actions)),
            $idsKey => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        try {
            DB::beginTransaction();
            $query = $modelClass::whereIn('id', $ids);
            $actions[$request->action]($query);
            DB::commit();

            return AdminApiResponse::okMessage('عملیات گروهی انجام شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Partner bulk action failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در عملیات گروهی.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }
}
