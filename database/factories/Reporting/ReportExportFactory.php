<?php

declare(strict_types=1);

namespace Database\Factories\Reporting;

use App\Features\Auth\Models\User;
use App\Features\Reporting\Enums\ExportFormat;
use App\Features\Reporting\Enums\ExportStatus;
use App\Features\Reporting\Enums\ReportType;
use App\Features\Reporting\Models\ReportExport;
use App\Features\Tenancy\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'saved_report_id' => null,
            'requested_by_user_id' => User::factory(),
            'report_type' => ReportType::TimeLogs,
            'filters' => [],
            'format' => ExportFormat::Csv,
            'status' => ExportStatus::Pending,
            'file_path' => null,
            'row_count' => null,
            'error_message' => null,
            'expires_at' => now()->addDays(7),
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ExportStatus::Pending]);
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => ExportStatus::Processing]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ExportStatus::Completed,
            'file_path' => 'exports/test.csv',
            'row_count' => 10,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ExportStatus::Failed,
            'error_message' => 'Export failed.',
        ]);
    }

    public function platform(): static
    {
        return $this->state(fn () => [
            'freelancer_id' => null,
            'report_type' => ReportType::FreelancerList,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
