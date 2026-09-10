<?php

declare(strict_types=1);

namespace App\Core\ClientPortal;

final class ClientContext
{
    private ?int $clientId = null;

    public function setClientId(int $id): void
    {
        $this->clientId = $id;
    }

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    public function hasClient(): bool
    {
        return $this->clientId !== null;
    }

    public function clear(): void
    {
        $this->clientId = null;
    }
}
