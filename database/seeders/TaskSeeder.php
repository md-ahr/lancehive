<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Delivery\Enums\TaskStatus;
use App\Features\Delivery\Models\Project;
use App\Features\Delivery\Models\Task;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();

        $website = Project::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', 'Website Redesign')
            ->firstOrFail();

        $mobile = Project::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', 'Mobile App MVP')
            ->firstOrFail();

        $api = Project::query()
            ->where('freelancer_id', $freelancer->id)
            ->where('name', 'API Integration')
            ->firstOrFail();

        $this->seedTask($website, 'Design homepage mockups', TaskStatus::Done, 8, now()->addWeek());
        $this->seedTask($website, 'Implement responsive layout', TaskStatus::InProgress, 16, now()->addWeeks(2));
        $this->seedTask($website, 'Deploy staging environment', TaskStatus::Todo, 4, now()->addWeeks(3));

        $this->seedTask($mobile, 'Set up React Native project', TaskStatus::Done, 6, now()->subWeek());
        $this->seedTask($mobile, 'Build authentication flow', TaskStatus::InProgress, 12, now()->addWeeks(2));

        $this->seedTask($api, 'Map third-party endpoints', TaskStatus::Todo, 10, null);
    }

    private function seedTask(
        Project $project,
        string $title,
        TaskStatus $status,
        float $estimatedHours,
        ?\DateTimeInterface $dueDate,
    ): void {
        Task::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => $title],
            [
                'status' => $status,
                'due_date' => $dueDate?->format('Y-m-d'),
                'estimated_hours' => $estimatedHours,
            ],
        );
    }
}
