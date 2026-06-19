<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;

#[CoversClass(FlagChanged::class)]
final class FlagChangedTest extends TestCase
{
    #[Test]
    public function savedCarriesNameAndActor(): void
    {
        $event = FlagChanged::saved(name: 'checkout.v2', actor: 'user-1');

        $this->assertSame('checkout.v2', $event->name);
        $this->assertSame(FlagChanged::OPERATION_SAVED, $event->operation);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function deletedCarriesNameAndActor(): void
    {
        $event = FlagChanged::deleted(name: 'checkout.v2', actor: 'user-1');

        $this->assertSame(FlagChanged::OPERATION_DELETED, $event->operation);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function defaultsActorToNull(): void
    {
        $event = FlagChanged::saved(name: 'x');

        $this->assertNull($event->actor);
    }

    #[Test]
    public function constructorDefaults(): void
    {
        $event = new FlagChanged(name: 'x', operation: FlagChanged::OPERATION_SAVED);

        $this->assertNull($event->actor);
    }
}
