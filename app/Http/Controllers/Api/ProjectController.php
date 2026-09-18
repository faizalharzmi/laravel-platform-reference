<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request, Workspace $workspace): JsonResponse
    {
        $membership = $workspace->users()->whereKey($request->user()->id)->first()?->pivot;

        abort_unless($membership !== null && in_array($membership->role, ['owner', 'admin'], true), 403);
        abort_unless($request->user()->tokenCan('projects:create'), 403);

        $project = $workspace->projects()->create([
            'created_by' => $request->user()->id,
            'name' => $request->string('name')->toString(),
            'key' => strtoupper($request->string('key')->toString()),
        ]);

        return response()->json(['data' => $project], 201);
    }
}
