<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;

#[CoversClass(ListFlagsResponder::class)]
final class ListFlagsResponderTest extends ActionTestCase
{
    #[Test]
    public function rendersFlagPresenterListAndGrid(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->listResponder($renderer, $this->writableProvider())->respond();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('list', $renderer->view);
        $this->assertArrayHasKey('flags', $renderer->parameters);
        $this->assertArrayHasKey('gridHtml', $renderer->parameters);
        $this->assertNotEmpty($renderer->parameters['gridHtml']);

        /** @var list<FlagPresenter> $flags */
        $flags = $renderer->parameters['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);
        $this->assertContains('checkout.v2', $names);
        $this->assertContains('billing.maintenance', $names);
    }

    #[Test]
    public function sortsFlagsByName(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var list<FlagPresenter> $flags */
        $flags = $renderer->parameters['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);

        $expected = $names;
        sort($expected);

        $this->assertSame($expected, $names);
    }

    #[Test]
    public function gridRendersKillSwitchBadge(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        $this->assertStringContainsString('KILLED', $gridHtml);
        $this->assertStringContainsString('text-bg-danger', $gridHtml);
    }

    #[Test]
    public function gridHidesWriteControlsForReadOnlyProvider(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $readOnlyProvider = new readonly class ($this->flags()) implements \Rasuvaeff\Yii3FeatureFlags\FlagProvider {
            /** @param array<string, Flag> $flags */
            public function __construct(private array $flags) {}

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }
        };

        $this->listResponder($renderer, $readOnlyProvider)->respond();

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        $this->assertStringNotContainsString('btn-outline-danger', $gridHtml);
        $this->assertStringNotContainsString('btn-outline-primary', $gridHtml);
    }
}
