<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Controllers;

use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Delivery\Http\Requests\DestroyTimeLogRequest;
use App\Features\Delivery\Http\Requests\IndexTaskTimeLogRequest;
use App\Features\Delivery\Http\Requests\StoreTimeLogRequest;
use App\Features\Delivery\Http\Requests\UpdateTimeLogRequest;
use App\Features\Delivery\Http\Resources\TimeLogResource;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Time Logs', weight: 33)]
final class TimeLogController extends Controller
{
    use CursorPaginates;

    #[Endpoint(title: 'List task time logs', description: 'Cursor-paginated time logs for a task, sorted by logged_at descending.')]
    public function indexForTask(IndexTaskTimeLogRequest $request, Task $task): JsonResponse
    {
        $query = TimeLog::query()
            ->where('task_id', $task->id)
            ->orderByDesc('logged_at')
            ->orderByDesc('id');

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = TimeLogResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreTimeLogRequest $request, Task $task): JsonResponse
    {
        $timeLog = TimeLog::query()->create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'hours' => $request->validated('hours'),
            'description' => $request->validated('description'),
            'logged_at' => $request->validated('logged_at'),
        ]);

        return (new TimeLogResource($timeLog))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): TimeLogResource
    {
        $timeLog->update($request->validated());

        return new TimeLogResource($timeLog->fresh());
    }

    public function destroy(DestroyTimeLogRequest $request, TimeLog $timeLog): MessageResource
    {
        $timeLog->delete();

        return new MessageResource([
            'message' => 'Time log deleted successfully.',
        ]);
    }
}
