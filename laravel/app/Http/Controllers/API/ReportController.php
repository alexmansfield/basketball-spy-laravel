<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display a listing of reports.
     *
     * GET /api/reports?player_id=1&user_id=1
     *
     * Returns reports scoped to user's organization.
     * Supports filtering by player and date range.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Report::with(['player.team', 'user:id,name'])
            ->where('user_id', $user->id);

        // If user is org_admin or super_admin, show all reports in their organization
        if ($user->isOrgAdmin() || $user->isSuperAdmin()) {
            $query = Report::with(['player.team', 'user:id,name']);

            if (!$user->isSuperAdmin()) {
                // Org admins see only their organization's reports
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('organization_id', $user->organization_id);
                });
            }
        }

        // Filter by player
        if ($request->has('player_id')) {
            $query->where('player_id', $request->player_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Order by most recent first
        $query->latest('created_at');

        $perPage = $request->get('per_page', 20);
        $reports = $query->paginate($perPage);

        return response()->json($reports);
    }

    /**
     * Store a newly created report.
     *
     * POST /api/reports
     *
     * Creates or updates a report (upsert based on synced_at).
     * Validates all rating fields (1-5 range).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|exists:players,id',

            // Offense ratings (all optional, nullable for partial reports)
            'offense_shooting' => 'nullable|integer|min:1|max:5',
            'offense_finishing' => 'nullable|integer|min:1|max:5',
            'offense_driving' => 'nullable|integer|min:1|max:5',
            'offense_dribbling' => 'nullable|integer|min:1|max:5',
            'offense_creating' => 'nullable|integer|min:1|max:5',
            'offense_passing' => 'nullable|integer|min:1|max:5',

            // Defense ratings
            'defense_one_on_one' => 'nullable|integer|min:1|max:5',
            'defense_blocking' => 'nullable|integer|min:1|max:5',
            'defense_team_defense' => 'nullable|integer|min:1|max:5',
            'defense_rebounding' => 'nullable|integer|min:1|max:5',

            // Intangibles ratings
            'intangibles_effort' => 'nullable|integer|min:1|max:5',
            'intangibles_role_acceptance' => 'nullable|integer|min:1|max:5',
            'intangibles_iq' => 'nullable|integer|min:1|max:5',
            'intangibles_awareness' => 'nullable|integer|min:1|max:5',

            // Athleticism ratings
            'athleticism_hands' => 'nullable|integer|min:1|max:5',
            'athleticism_length' => 'nullable|integer|min:1|max:5',
            'athleticism_quickness' => 'nullable|integer|min:1|max:5',
            'athleticism_jumping' => 'nullable|integer|min:1|max:5',
            'athleticism_strength' => 'nullable|integer|min:1|max:5',
            'athleticism_coordination' => 'nullable|integer|min:1|max:5',

            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Get player to record their team at time of report
        $player = Player::findOrFail($request->player_id);

        // Create report
        $report = Report::create([
            'user_id' => $user->id,
            'player_id' => $request->player_id,
            'team_id_at_time' => $player->team_id,

            // Offense
            'offense_shooting' => $request->offense_shooting,
            'offense_finishing' => $request->offense_finishing,
            'offense_driving' => $request->offense_driving,
            'offense_dribbling' => $request->offense_dribbling,
            'offense_creating' => $request->offense_creating,
            'offense_passing' => $request->offense_passing,

            // Defense
            'defense_one_on_one' => $request->defense_one_on_one,
            'defense_blocking' => $request->defense_blocking,
            'defense_team_defense' => $request->defense_team_defense,
            'defense_rebounding' => $request->defense_rebounding,

            // Intangibles
            'intangibles_effort' => $request->intangibles_effort,
            'intangibles_role_acceptance' => $request->intangibles_role_acceptance,
            'intangibles_iq' => $request->intangibles_iq,
            'intangibles_awareness' => $request->intangibles_awareness,

            // Athleticism
            'athleticism_hands' => $request->athleticism_hands,
            'athleticism_length' => $request->athleticism_length,
            'athleticism_quickness' => $request->athleticism_quickness,
            'athleticism_jumping' => $request->athleticism_jumping,
            'athleticism_strength' => $request->athleticism_strength,
            'athleticism_coordination' => $request->athleticism_coordination,

            'notes' => $request->notes,
            'synced_at' => Carbon::now(),
        ]);

        $report->load(['player.team', 'user:id,name']);

        return response()->json($report, 201);
    }

    /**
     * Display the specified report.
     *
     * GET /api/reports/{id}
     *
     * Returns single report with player/team relationships.
     * Organization-scoped authorization.
     */
    public function show(Report $report): JsonResponse
    {
        $user = request()->user();

        // Authorization: scouts can only see their own reports
        if ($user->isScout() && $report->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Org admins can only see reports from their organization
        if ($user->isOrgAdmin() && $report->user->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $report->load(['player.team', 'user:id,name']);

        return response()->json($report);
    }

    /**
     * Update the specified report.
     *
     * PUT/PATCH /api/reports/{id}
     */
    public function update(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();

        // Only the report creator can update their own report
        if ($report->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            // Same validation rules as store
            'offense_shooting' => 'nullable|integer|min:1|max:5',
            'offense_finishing' => 'nullable|integer|min:1|max:5',
            'offense_driving' => 'nullable|integer|min:1|max:5',
            'offense_dribbling' => 'nullable|integer|min:1|max:5',
            'offense_creating' => 'nullable|integer|min:1|max:5',
            'offense_passing' => 'nullable|integer|min:1|max:5',
            'defense_one_on_one' => 'nullable|integer|min:1|max:5',
            'defense_blocking' => 'nullable|integer|min:1|max:5',
            'defense_team_defense' => 'nullable|integer|min:1|max:5',
            'defense_rebounding' => 'nullable|integer|min:1|max:5',
            'intangibles_effort' => 'nullable|integer|min:1|max:5',
            'intangibles_role_acceptance' => 'nullable|integer|min:1|max:5',
            'intangibles_iq' => 'nullable|integer|min:1|max:5',
            'intangibles_awareness' => 'nullable|integer|min:1|max:5',
            'athleticism_hands' => 'nullable|integer|min:1|max:5',
            'athleticism_length' => 'nullable|integer|min:1|max:5',
            'athleticism_quickness' => 'nullable|integer|min:1|max:5',
            'athleticism_jumping' => 'nullable|integer|min:1|max:5',
            'athleticism_strength' => 'nullable|integer|min:1|max:5',
            'athleticism_coordination' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $report->update($request->all());
        $report->synced_at = Carbon::now();
        $report->save();

        $report->load(['player.team', 'user:id,name']);

        return response()->json($report);
    }

    /**
     * Remove the specified report.
     *
     * DELETE /api/reports/{id}
     */
    public function destroy(Report $report): JsonResponse
    {
        $user = request()->user();

        // Only the report creator or super admin can delete
        if ($report->user_id !== $user->id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $report->delete();

        return response()->json(['message' => 'Report deleted successfully']);
    }

    /**
     * Batch sync endpoint for local-first architecture.
     *
     * POST /api/reports/sync
     *
     * Accepts array of reports from mobile app.
     * Returns conflicts for resolution.
     */
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reports' => 'required|array',
            'reports.*.id' => 'nullable|exists:reports,id',
            'reports.*.player_id' => 'required|exists:players,id',
            'reports.*.local_updated_at' => 'required|date',
            // ... include all rating fields
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $synced = [];
        $conflicts = [];

        foreach ($request->reports as $reportData) {
            // If report has an ID, check for conflicts
            if (isset($reportData['id'])) {
                $existingReport = Report::find($reportData['id']);

                if ($existingReport) {
                    // Check for conflict: server updated after local
                    $localUpdated = Carbon::parse($reportData['local_updated_at']);
                    if ($existingReport->updated_at > $localUpdated) {
                        $conflicts[] = [
                            'id' => $existingReport->id,
                            'server_version' => $existingReport,
                            'client_version' => $reportData,
                        ];
                        continue;
                    }

                    // No conflict, update
                    $existingReport->update($reportData);
                    $existingReport->synced_at = Carbon::now();
                    $existingReport->save();
                    $synced[] = $existingReport;
                } else {
                    // Report deleted on server
                    $conflicts[] = [
                        'id' => $reportData['id'],
                        'error' => 'Report not found on server (possibly deleted)',
                        'client_version' => $reportData,
                    ];
                }
            } else {
                // New report from mobile, create it
                $player = Player::findOrFail($reportData['player_id']);

                $newReport = Report::create(array_merge($reportData, [
                    'user_id' => $user->id,
                    'team_id_at_time' => $player->team_id,
                    'synced_at' => Carbon::now(),
                ]));

                $synced[] = $newReport;
            }
        }

        return response()->json([
            'synced' => $synced,
            'conflicts' => $conflicts,
            'synced_count' => count($synced),
            'conflict_count' => count($conflicts),
        ]);
    }
}
