<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamMemberController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = TeamMember::query()->with('user:id,first_name,last_name,phone_number,profile_image_url,bio');

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('display_title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('is_visible') && $request->is_visible !== '' && $request->is_visible !== null) {
            $query->where('is_visible', filter_var($request->is_visible, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        if ($sortBy === 'name') {
            $query->join('users', 'users.id', '=', 'team_members.user_id')
                ->orderBy('users.first_name', $sortOrder === 'desc' ? 'desc' : 'asc')
                ->orderBy('users.last_name', $sortOrder === 'desc' ? 'desc' : 'asc')
                ->select('team_members.*');
        } elseif (in_array($sortBy, ['sort_order', 'created_at', 'display_title'], true)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->ordered();
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(
            fn (TeamMember $member) => $member->toAdminArray()
        );

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStore(Request $request)
    {
        $validator = $this->makeValidator($request->all(), true);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $member = TeamMember::create($data);

        return AdminApiResponse::success(
            $member->load('user:id,first_name,last_name,phone_number,profile_image_url,bio')->toAdminArray(),
            'Team member added successfully',
            201
        );
    }

    public function apiShow(TeamMember $teamMember)
    {
        $teamMember->load('user:id,first_name,last_name,phone_number,profile_image_url,bio');

        return AdminApiResponse::success($teamMember->toAdminArray());
    }

    public function apiUpdate(Request $request, TeamMember $teamMember)
    {
        $validator = $this->makeValidator($request->all(), false, $teamMember);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $teamMember->update($validator->validated());

        return AdminApiResponse::success(
            $teamMember->load('user:id,first_name,last_name,phone_number,profile_image_url,bio')->toAdminArray(),
            'Team member updated successfully'
        );
    }

    public function apiDestroy(TeamMember $teamMember)
    {
        $teamMember->delete();

        return AdminApiResponse::okMessage('Team member removed successfully');
    }

    public function apiReorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:team_members,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        foreach ($validator->validated()['items'] as $item) {
            TeamMember::whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return AdminApiResponse::okMessage('Team order updated successfully');
    }

    private function makeValidator(array $data, bool $creating, ?TeamMember $existing = null)
    {
        $userIdRule = 'required|integer|exists:users,id|unique:team_members,user_id';
        if (! $creating && $existing) {
            $userIdRule = 'sometimes|integer|exists:users,id|unique:team_members,user_id,'.$existing->id;
        }

        return Validator::make($data, [
            'user_id' => $creating ? $userIdRule : $userIdRule,
            'display_title' => ($creating ? 'required' : 'sometimes').'|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
            'is_visible' => 'sometimes|boolean',
        ]);
    }

    private function validationError($validator)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }
}
