<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;

#[CoversClass(FlagPresenter::class)]
final class FlagPresenterTest extends TestCase
{
    #[Test]
    public function mapsFlagFieldsToViewProperties(): void
    {
        $flag = new Flag(name: 'checkout.v2', enabled: true, salt: 'salt', rollout: 75, killSwitch: false, environments: ['prod']);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/edit', deleteUrl: '/delete', environments: ['prod']);

        $this->assertSame('checkout.v2', $presenter->name);
        $this->assertTrue($presenter->enabled);
        $this->assertSame(75, $presenter->rollout);
        $this->assertSame('salt', $presenter->salt);
        $this->assertFalse($presenter->killSwitch);
        $this->assertSame(['prod'], $presenter->environments);
        $this->assertTrue($presenter->isWritable);
        $this->assertSame('/edit', $presenter->editUrl);
        $this->assertSame('/delete', $presenter->deleteUrl);
    }

    #[Test]
    public function environmentsLabelShowsAllForEmptyList(): void
    {
        $flag = new Flag(name: 'x', environments: []);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/e', deleteUrl: '/d');

        $this->assertSame('all', $presenter->environmentsLabel());
    }

    #[Test]
    public function environmentsLabelJoinsWithComma(): void
    {
        $flag = new Flag(name: 'x', environments: ['prod', 'staging']);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/e', deleteUrl: '/d', environments: ['prod', 'staging']);

        $this->assertSame('prod, staging', $presenter->environmentsLabel());
    }
}
