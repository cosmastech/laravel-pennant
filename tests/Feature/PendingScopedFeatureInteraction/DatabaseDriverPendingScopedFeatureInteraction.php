<?php

namespace Tests\Feature\PendingScopedFeatureInteraction;

use Illuminate\Support\Facades\Config;

class DatabaseDriverPendingScopedFeatureInteraction extends AbstractPendingScopedFeatureInteraction
{
    protected function setDriver(): void
    {
        Config::set('pennant.default', 'database');
    }
}
