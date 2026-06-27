<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Event;

use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FlagChanged::class)]
final class FlagChangedTest
{
    public function savedCarriesNameAndActor(): void
    {
        $event = FlagChanged::saved(name: 'checkout.v2', actor: 'user-1');

        Assert::same($event->name, 'checkout.v2');
        Assert::same($event->operation, FlagChanged::OPERATION_SAVED);
        Assert::same($event->actor, 'user-1');
    }

    public function deletedCarriesNameAndActor(): void
    {
        $event = FlagChanged::deleted(name: 'checkout.v2', actor: 'user-1');

        Assert::same($event->operation, FlagChanged::OPERATION_DELETED);
        Assert::same($event->actor, 'user-1');
    }

    public function defaultsActorToNull(): void
    {
        $event = FlagChanged::saved(name: 'x');

        Assert::null($event->actor);
    }

    public function constructorDefaults(): void
    {
        $event = new FlagChanged(name: 'x', operation: FlagChanged::OPERATION_SAVED);

        Assert::null($event->actor);
    }
}
