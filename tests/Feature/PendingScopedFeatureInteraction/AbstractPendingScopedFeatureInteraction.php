<?php

namespace Tests\Feature\PendingScopedFeatureInteraction;

use Illuminate\Support\Facades\Event;
use Laravel\Pennant\Events\FeatureUnavailableForScope;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

/**
 * These test cases are designed to ensure that PendingScopedFeatureInteraction
 * functionality works as expected regardless of the driver.
 */
abstract class AbstractPendingScopedFeatureInteraction extends TestCase
{
    abstract protected function setDriver(): void;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setDriver();
    }

    public function testCanGetAllWhenFeaturesAreDefinedForDifferentScopes(): void
    {
        // Given features of varying scopes
        Feature::define('for-teams', fn(Team $team) => true);
        Feature::define('for-users', fn(User $user) => true);
        Feature::define('for-nullable-users', fn(?User $user) => false);
        Feature::define('for-null', fn() => false);

        // And we are faking events
        Event::fake([FeatureUnavailableForScope::class]);

        // And we have a user
        $user = new User;

        // When
        $features = Feature::for($user)->all();

        // Then we only see scopes relevant to the User type
        $this->assertEqualsCanonicalizing(
            [
                'for-users' => true,
                'for-null' => false,
                'for-nullable-users' => false,
            ],
            $features
        );

        // And an event was dispatched indicating that we tried to retrieve a feature not matched to scope
        Event::assertDispatchedTimes(FeatureUnavailableForScope::class, 1);
        Event::assertDispatched(function (FeatureUnavailableForScope $event) use ($user) {
            return $event->feature === 'for-teams'
                && $event->scope === $user;
        });
    }

    public function testInvalidScopedFeatureReturnsFalse(): void
    {
        // Given scope belonging to a Team scope
        Feature::define('yooo', fn(Team $team) => true);

        // When attempting to fetch that feature for a User scope
        $result = Feature::for(new User)->active('yooo');

        // Then
        $this->assertFalse($result);
    }

    public function testValuesReturnsFalseForFeaturesWhichDoNotBelongToScope(): void
    {
        // Given features with varying scopes
        Feature::define('foo', fn(User $user) => true);
        Feature::define('bar', fn(Team $team) => true);
        Feature::define('zed', fn(mixed $v) => true);
        Feature::define('elephant', fn($v) => true);
        Feature::define('cat', fn(array $t) => true);
        Feature::define('woof', fn(string $str) => true);

        // When
        $features = Feature::for(new User)->values(['foo', 'bar', 'zed', 'elephant', 'cat', 'woof']);

        // Then
        $this->assertEqualsCanonicalizing([
            'foo' => true,
            'bar' => false,
            'zed' => true,
            'elephant' => true,
            'cat' => false,
            'woof' => false,
        ], $features);
    }

    public function testSomeAreActiveWithMismatchedScopeTreatsAsFalse(): void
    {
        // Given features with varying scopes
        Feature::define('for-teams', fn(Team $team) => true);
        Feature::define('for-nullable', fn() => false);

        // When
        $result = Feature::for(new User)->someAreActive(['for-teams', 'for-nullable']);

        // Then
        $this->assertFalse($result);
    }

    public function testAllAreActiveTreatsMismatchedScopeAsFalse(): void
    {
        // Given features with varying scopes
        Feature::define('for-team', fn(Team $team) => true);
        Feature::define('for-user', fn(User $user) => true);

        // When
        $result = Feature::for(new User)->allAreActive(['for-team', 'for-user']);

        // Then
        $this->assertFalse($result);
    }

    public function testSomeAreInactiveWithMismatchedScopeTreatsAsFalse(): void
    {
        // Given features with varying scopes
        Feature::define('for-teams', fn(Team $team) => true);
        Feature::define('for-user', fn(User $user) => true);
        Feature::define('for-null-scope', fn() => true);

        // When
        $result = Feature::for(new User)->someAreInactive([
            'for-teams', 'for-user', 'for-null-scope'
        ]);

        // Then
        $this->assertTrue($result);
    }

    public function testAllAreInactiveWithMismatchedScope(): void
    {
        // Given features with varying scopes
        Feature::define('for-teams', fn(Team $team) => true);
        Feature::define('for-user', fn(User $user) => false);
        Feature::define('for-null-scope', fn() => false);

        // When
        $result = Feature::for(new User)->allAreInactive(['for-teams', 'for-user', 'for-null-scope']);

        // Then
        $this->assertTrue($result);
    }

    public function test_mismatchedScopes_eagerLoaded_returnsFalseThatScopeIsActive(): void
    {
        // Given
        Feature::define('for-teams', fn(Team $team) => true);
        Feature::define('for-user', fn(User $user) => false);
        Feature::define('for-null-scope', fn() => false);

        // And we have eager loaded scopes
        Feature::for([$user = new User])->load(['for-teams']);

        // When
        $result = Feature::for($user)->active('for-teams');

        // Then
        $this->assertFalse($result);
    }
}
