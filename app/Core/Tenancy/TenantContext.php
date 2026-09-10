<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

final class TenantContext
{
    private ?int $freelancerId = null;

    public function setFreelancerId(int $id): void
    {
        $this->freelancerId = $id;
    }

    public function freelancerId(): ?int
    {
        return $this->freelancerId;
    }

    public function hasFreelancer(): bool
    {
        return $this->freelancerId !== null;
    }

    public function clear(): void
    {
        $this->freelancerId = null;
    }
}
