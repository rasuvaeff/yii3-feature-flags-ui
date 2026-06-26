<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FlagPresenter::class)]
final class FlagPresenterTest
{
    public function mapsFlagFieldsToViewProperties(): void
    {
        $flag = new Flag(name: 'checkout.v2', enabled: true, salt: 'salt', rollout: 75, killSwitch: false, environments: ['prod']);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/edit', deleteUrl: '/delete', environments: ['prod']);

        Assert::same($presenter->name, 'checkout.v2');
        Assert::true($presenter->enabled);
        Assert::same($presenter->rollout, 75);
        Assert::same($presenter->salt, 'salt');
        Assert::false($presenter->killSwitch);
        Assert::same($presenter->environments, ['prod']);
        Assert::true($presenter->isWritable);
        Assert::same($presenter->editUrl, '/edit');
        Assert::same($presenter->deleteUrl, '/delete');
    }

    public function environmentsLabelShowsAllForEmptyList(): void
    {
        $flag = new Flag(name: 'x', environments: []);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/e', deleteUrl: '/d');

        Assert::same($presenter->environmentsLabel(), 'all');
    }

    public function environmentsLabelJoinsWithComma(): void
    {
        $flag = new Flag(name: 'x', environments: ['prod', 'staging']);
        $presenter = new FlagPresenter(flag: $flag, isWritable: true, editUrl: '/e', deleteUrl: '/d', environments: ['prod', 'staging']);

        Assert::same($presenter->environmentsLabel(), 'prod, staging');
    }
}
