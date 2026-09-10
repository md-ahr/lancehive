<?php

declare(strict_types=1);

namespace App\Features\Delivery\Http\Controllers;

use App\Core\Pagination\Concerns\CursorPaginates;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Http\Requests\DestroyTaskRequest;
use App\Features\Delivery\Http\Requests\IndexProjectTaskRequest;
use App\Features\Delivery\Http\Requests\ShowTaskRequest;
use App\Features\Delivery\Http\Requests\StoreTaskRequest;
use App\Features\Delivery\Http\Requests\UpdateTaskRequest;
use App\Features\Delivery\Http\Resources\TaskResource;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[HeaderParameter('X-Freelancer-Id', description: 'Active freelancer workspace ID', required: true)]
#[Group('Tasks', weight: 32)]
final class TaskController extends Controller
{
    use CursorPaginates;

    #[Endpoint(title: 'List project tasks', description: 'Cursor-paginated tasks nested under a project.')]
    public function indexForProject(IndexProjectTaskRequest $request, Project $project): JsonResponse
    {
        $query = Task::query()
            ->where('project_id', $project->id)
            ->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $page = $this->cursorPaginate($query, $request);
        $page['data'] = TaskResource::collection($page['data'])->resolve();

        return response()->json($page);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => $request->string('title')->toString(),
            'status' => $request->validated('status') ?? TaskStatus::Todo,
            'due_date' => $request->validated('due_date'),
            'estimated_hours' => $request->validated('estimated_hours'),
        ]);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowTaskRequest $request, Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->fresh());
    }

    public function destroy(DestroyTaskRequest $request, Task $task): MessageResource
    {
        $task->delete();

        return new MessageResource([
            'message' => 'Task deleted successfully.',
        ]);
    }
}
