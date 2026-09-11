<?php

declare(strict_types=1);

namespace App\Features\Settings\Observers;

use App\Features\Settings\Cache\WorkspaceSettingsCache;
use App\Features\Tenancy\Models\Freelancer;

final class FreelancerWorkspaceSettingsObserver
{
    /** @var list<string> */
    private const array SETTINGS_COLUMNS = [
        'default_currency',
        'invoice_number_prefix',
        'default_tax_rate',
        'invoice_footer_notes',
        'business_name',
        'business_email',
        'business_address',
    ];

    public function __construct(private readonly WorkspaceSettingsCache $cache) {}

    public function saved(Freelancer $freelancer): void
    {
        if ($freelancer->wasRecentlyCreated || $freelancer->wasChanged(self::SETTINGS_COLUMNS)) {
            $this->cache->forget($freelancer->id);
        }
    }
}
