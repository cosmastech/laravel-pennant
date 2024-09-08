<?php

namespace Laravel\Pennant;

/**
 * Sentinel value to indicate the feature requested does not apply to the given scope.
 */
class FeatureDoesNotMatchScope
{
    protected static FeatureDoesNotMatchScope $instance;

    protected function __construct() {}

    /**
     * @return self
     */
    public static function instance()
    {
        return self::$instance ??= new self;
    }
}
