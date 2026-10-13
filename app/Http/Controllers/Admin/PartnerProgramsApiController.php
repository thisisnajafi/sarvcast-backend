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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

    public function teachersStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:teacher_accounts,user_id',
            'institution_name' => 'required|string|max:255',
            'institution_type' => 'nullable|string|max:64',
            'teaching_subject' => 'nullable|string|max:128',
            'years_of_experience' => 'nullable|integer|min:0|max:80',
            'student_count' => 'nullable|integer|min:0',
            'status' => ['nullable', Rule::in(['pending', 'verified', 'active', 'suspended', 'expired'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $payload = $validator->validated();
        $payload['status'] = $this->normalizeTeacherStatus($payload['status'] ?? 'pending');
        $payload['institution_type'] = $payload['institution_type'] ?? 'school';
        $payload['teaching_subject'] = $payload['teaching_subject'] ?? 'general';
        $payload['years_of_experience'] = $payload['years_of_experience'] ?? 0;

        $teacher = TeacherAccount::create($payload);

        return AdminApiResponse::success($teacher->load('user'), 'حساب معلم ثبت شد.', 201);
    }

    public function teachersUpdate(Request $request, TeacherAccount $teacher)
    {
        $validator = Validator::make($request->all(), [
            'institution_name' => 'sometimes|required|string|max:255',
            'institution_type' => 'nullable|string|max:64',
            'teaching_subject' => 'nullable|string|max:128',
            'years_of_experience' => 'nullable|integer|min:0|max:80',
            'student_count' => 'nullable|integer|min:0',
            'status' => ['nullable', Rule::in(['pending', 'verified', 'active', 'suspended', 'expired'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $payload = $validator->validated();
        if (array_key_exists('status', $payload)) {
            $payload['status'] = $this->normalizeTeacherStatus($payload['status']);
        }

        $teacher->update($payload);

        return AdminApiResponse::success($teacher->fresh()->load('user'), 'حساب معلم بروزرسانی شد.');
    }

    public function teachersDestroy(TeacherAccount $teacher)
    {
        $teacher->delete();

        return AdminApiResponse::okMessage('حساب معلم حذف شد.');
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
            'verify' => fn ($q) => $q->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'verified']),
            'suspend' => fn ($q) => $q->update(['status' => 'suspended']),
            'activate' => fn ($q) => $q->update(['status' => 'verified']),
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

    public function schoolsStore(Request $request)
    {
        $validator = $this->schoolPartnershipValidator($request, false);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $school = SchoolPartnership::create($this->schoolCreatePayload($validator->validated()));

        return AdminApiResponse::success($school->fresh(), 'مدرسه ثبت شد.', 201);
    }

    public function schoolsUpdate(Request $request, SchoolPartnership $school)
    {
        $validator = $this->schoolPartnershipValidator($request, true);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $payload = $validator->validated();
        unset($payload['school_address']);
        $school->update($payload);

        return AdminApiResponse::success($school->fresh()->load(['affiliatePartner', 'assignedTeacher']), 'مدرسه بروزرسانی شد.');
    }

    public function schoolsDestroy(SchoolPartnership $school)
    {
        $school->delete();

        return AdminApiResponse::okMessage('مدرسه حذف شد.');
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

    private function schoolPartnershipValidator(Request $request, bool $updating): \Illuminate\Contracts\Validation\Validator
    {
        $statuses = ['pending', 'active', 'suspended', 'expired', 'terminated'];
        $models = array_keys(SchoolPartnership::getPartnershipModels());
        $types = array_keys(SchoolPartnership::getSchoolTypes());
        $levels = array_keys(SchoolPartnership::getSchoolLevels());

        $rules = [
            'school_address' => 'prohibited',
            'school_name' => ($updating ? 'sometimes|' : '').'required|string|max:255',
            'school_type' => ['nullable', Rule::in($types)],
            'school_level' => ['nullable', Rule::in($levels)],
            'location' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:32',
            'student_count' => 'nullable|integer|min:0',
            'teacher_count' => 'nullable|integer|min:0',
            'partnership_model' => ['nullable', Rule::in($models)],
            'max_student_capacity' => 'nullable|integer|min:1',
            'affiliate_partner_id' => 'nullable|exists:affiliate_partners,id',
            'assigned_teacher_id' => 'nullable|exists:teacher_accounts,id',
            'status' => ['nullable', Rule::in($statuses)],
            'notes' => 'nullable|string|max:5000',
            'partnership_start_date' => 'nullable|date',
            'partnership_end_date' => 'nullable|date|after_or_equal:partnership_start_date',
        ];

        return Validator::make($request->all(), $rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function schoolCreatePayload(array $validated): array
    {
        $modelKey = $validated['partnership_model'] ?? 'pilot';
        $modelConfig = SchoolPartnership::getPartnershipModels()[$modelKey] ?? SchoolPartnership::getPartnershipModels()['pilot'];

        return [
            'school_name' => $validated['school_name'],
            'school_type' => $validated['school_type'] ?? 'public',
            'school_level' => $validated['school_level'] ?? 'elementary',
            'location' => $validated['location'] ?? '',
            'contact_person' => $validated['contact_person'] ?? '',
            'contact_email' => $validated['contact_email'] ?? 'noreply@manjiapp.ir',
            'contact_phone' => $validated['contact_phone'] ?? '',
            'student_count' => $validated['student_count'] ?? 0,
            'teacher_count' => $validated['teacher_count'] ?? 0,
            'partnership_model' => $modelKey,
            'discount_rate' => $modelConfig['discount_rate'] ?? 60.00,
            'revenue_share_rate' => $modelConfig['revenue_share_rate'] ?? null,
            'annual_license_fee' => $modelConfig['annual_license_fee'] ?? null,
            'max_student_capacity' => $validated['max_student_capacity'] ?? ($modelConfig['max_capacity'] ?? 500),
            'partnership_start_date' => $validated['partnership_start_date'] ?? now()->toDateString(),
            'partnership_end_date' => $validated['partnership_end_date'] ?? now()->addYear()->toDateString(),
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'affiliate_partner_id' => $validated['affiliate_partner_id'] ?? null,
            'assigned_teacher_id' => $validated['assigned_teacher_id'] ?? null,
        ];
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

    /**
     * teacher_accounts.status enum is pending|verified|suspended|expired.
     * Accept "active" from the dashboard as an alias for "verified".
     */
    private function normalizeTeacherStatus(?string $status): string
    {
        if ($status === 'active') {
            return 'verified';
        }

        return $status ?: 'pending';
    }
}
