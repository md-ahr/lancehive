<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Auth\Models\User;
use App\Features\Delivery\Models\Task;
use App\Features\Delivery\Models\TimeLog;
use App\Features\Tenancy\Models\Freelancer;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class TimeLogSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = Freelancer::query()->where('slug', DemoData::WORKSPACE_SLUG)->firstOrFail();
        $owner = User::query()->where('email', DemoData::OWNER_EMAIL)->firstOrFail();
        $member = User::query()->where('email', DemoData::MEMBER_EMAIL)->firstOrFail();

        $designTask = Task::query()
            ->whereHas('project', fn ($query) => $query->where('freelancer_id', $freelancer->id))
            ->where('title', 'Design homepage mockups')
            ->firstOrFail();

        $layoutTask = Task::query()
            ->whereHas('project', fn ($query) => $query->where('freelancer_id', $freelancer->id))
            ->where('title', 'Implement responsive layout')
            ->firstOrFail();

        $authTask = Task::query()
            ->whereHas('project', fn ($query) => $query->where('freelancer_id', $freelancer->id))
            ->where('title', 'Build authentication flow')
            ->firstOrFail();

        $this->seedLog($designTask, $owner, 6, 'Initial wireframes and high-fidelity mockups', now()->subDays(10));
        $this->seedLog($designTask, $owner, 2, 'Client feedback revisions', now()->subDays(7));
        $this->seedLog($layoutTask, $member, 4, 'Header and navigation components', now()->subDays(3));
        $this->seedLog($layoutTask, $owner, 3, 'Homepage layout implementation', now()->subDay());
        $this->seedLog($authTask, $member, 5, 'Login and registration screens', now()->subDays(5));
    }

    private function seedLog(Task $task, User $user, float $hours, string $description, \DateTimeInterface $loggedAt): void
    {
        TimeLog::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'logged_at' => $loggedAt,
            ],
            [
                'hours' => $hours,
                'description' => $description,
                'client_invoice_item_id' => null,
            ],
        );
    }
}
