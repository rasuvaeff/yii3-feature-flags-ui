<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;

#[CoversClass(FlagsGridFactory::class)]
final class FlagsGridFactoryTest extends TestCase
{
    private FlagsGridFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new FlagsGridFactory(new TestContainer());
    }

    #[Test]
    public function rendersTableWithBootstrapClassesAndColumns(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'feature.x', writable: true)], true);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('table-striped', $html);
        $this->assertStringContainsString('table-hover', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Rollout', $html);
        $this->assertStringContainsString('Environments', $html);
    }

    #[Test]
    public function rendersKillSwitchBadgeWhenActive(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'billing', killSwitch: true, writable: true)], true);

        $this->assertStringContainsString('KILLED', $html);
        $this->assertStringContainsString('text-bg-danger', $html);
    }

    #[Test]
    public function rendersOffBadgeWhenDisabled(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', enabled: false, writable: true)], true);

        $this->assertStringContainsString('OFF', $html);
        $this->assertStringContainsString('text-bg-secondary', $html);
    }

    #[Test]
    public function rendersEnvironmentsAllBadgeForEmptyList(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: true)], true);

        $this->assertStringContainsString('>all<', $html);
    }

    #[Test]
    public function rendersEnvironmentsAsList(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', environments: ['prod', 'staging'], writable: true)], true);

        $this->assertStringContainsString('prod, staging', $html);
    }

    #[Test]
    public function rendersWriteControlsWhenWritable(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: true)], true);

        $this->assertStringContainsString('btn-outline-primary', $html);
        $this->assertStringNotContainsString('btn-outline-danger', $html);
        $this->assertStringNotContainsString('href="/delete"', $html);
    }

    #[Test]
    public function omitsWriteControlsWhenReadOnly(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', writable: false)], false);

        $this->assertStringNotContainsString('btn-outline-primary', $html);
        $this->assertStringNotContainsString('btn-outline-danger', $html);
    }

    #[Test]
    public function escapesUntrustedEnvironmentValues(): void
    {
        $html = $this->factory->render([$this->presenter(name: 'x', environments: ['<script>alert(1)</script>'], writable: true)], true);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * @param list<string> $environments
     */
    private function presenter(
        string $name,
        bool $enabled = true,
        bool $killSwitch = false,
        array $environments = [],
        bool $writable = true,
    ): FlagPresenter {
        $flag = new Flag(name: $name, enabled: $enabled, killSwitch: $killSwitch, environments: $environments);

        return new FlagPresenter(
            flag: $flag,
            isWritable: $writable,
            editUrl: '/edit',
            deleteUrl: '/delete',
            environments: $environments,
        );
    }
}
