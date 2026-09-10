<?php

declare(strict_types=1);

namespace Database\Factories\Reporting;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\SavedReport;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReport>
 */
class SavedReportFactory extends Factory
{
    protected $model = SavedReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'created_by_user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'report_type' => ReportType::TimeLogs,
            'filters' => [],
        ];
    }

    public function platform(): static
    {
        return $this->state(fn () => [
            'freelancer_id' => null,
            'report_type' => ReportType::FreelancerList,
        ]);
    }
}
