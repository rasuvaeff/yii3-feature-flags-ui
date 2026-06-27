<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Service;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(FlagsGridFactory::class)]
final class FlagsGridFactoryTest
{
    private FlagsGridFactory $factory;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->factory = new FlagsGridFactory(new TestContainer());
    }

    public function rendersTableWithBootstrapClassesAndColumns(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'feature.x', writable: true)], true);

        Assert::string($html)->contains('<table');
        Assert::string($html)->contains('table-striped');
        Assert::string($html)->contains('table-hover');
        Assert::string($html)->contains('Name');
        Assert::string($html)->contains('Rollout');
        Assert::string($html)->contains('Environments');
        Assert::string($html)->contains('<strong>feature.x</strong>');
        Assert::string($html)->contains('100%');
    }

    public function rendersKillSwitchBadgeWhenActive(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'billing', killSwitch: true, writable: true)], true);

        Assert::string($html)->contains('KILLED');
        Assert::string($html)->contains('text-bg-danger');
        Assert::string($html)->contains('<strong>billing</strong>');
    }

    public function rendersOffBadgeWhenDisabled(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', enabled: false, writable: true)], true);

        Assert::string($html)->contains('OFF');
        Assert::string($html)->contains('text-bg-secondary');
    }

    public function rendersEnvironmentsAllBadgeForEmptyList(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: true)], true);

        Assert::string($html)->contains('<span class="badge text-bg-info">all</span>');
    }

    public function rendersEnvironmentsAsList(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', environments: ['prod', 'staging'], writable: true)], true);

        Assert::string($html)->contains('<span class="badge text-bg-info">prod, staging</span>');
    }

    public function rendersWriteControlsWhenWritable(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: true)], true);

        Assert::string($html)->contains('btn-outline-primary');
        Assert::string($html)->contains('href="/edit"');
        Assert::string($html)->notContains('&lt;a');
        Assert::string($html)->notContains('btn-outline-danger');
    }

    public function rendersRolloutPercentageExactly(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', rollout: 75, writable: true)], true);

        Assert::string($html)->contains('75%');
    }

    public function omitsWriteControlsWhenReadOnly(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: false)], false);

        Assert::string($html)->notContains('btn-outline-primary');
        Assert::string($html)->notContains('btn-outline-danger');
    }

    public function escapesUntrustedEnvironmentValues(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', environments: ['<script>alert(1)</script>'], writable: true)], true);

        Assert::string($html)->notContains('<script>alert(1)</script>');
        Assert::string($html)->contains('&lt;script&gt;');
    }

    private function presenter(
        string $name,
        bool $enabled = true,
        bool $killSwitch = false,
        int $rollout = 100,
        array $environments = [],
        bool $writable = true,
    ): FlagPresenter {
        $flag = new Flag(name: $name, enabled: $enabled, killSwitch: $killSwitch, rollout: $rollout, environments: $environments);

        return new FlagPresenter(
            flag: $flag,
            isWritable: $writable,
            editUrl: '/edit',
            deleteUrl: '/delete',
            environments: $environments,
        );
    }
}
