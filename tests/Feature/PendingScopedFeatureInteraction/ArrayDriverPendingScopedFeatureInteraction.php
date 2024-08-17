<?php

namespace Tests\Feature\PendingScopedFeatureInteraction;

use Illuminate\Support\Facades\Config;
class ArrayDriverPendingScopedFeatureInteraction extends AbstractPendingScopedFeatureInteraction
{
    protected function setDriver(): void
    {
        Config::set('pennant.default', 'array');
    }
}
