<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ListFlagsResponder::class)]
final class ListFlagsResponderTest extends ActionTestCase
{
    public function rendersFlagPresenterListAndGrid(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->listResponder($renderer, $this->writableProvider())->respond();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'list');
        Assert::true(array_key_exists('flags', $renderer->parameters));
        Assert::true(array_key_exists('gridHtml', $renderer->parameters));
        Assert::true($renderer->parameters['gridHtml'] !== '');
        Assert::true($renderer->parameters['isWritable']);
        Assert::same($renderer->parameters['createUrl'], '/admin/flags/new');

        /** @var list<FlagPresenter> $flags */
        $flags = $renderer->parameters['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);
        Assert::contains($names, 'checkout.v2');
        Assert::contains($names, 'billing.maintenance');

        foreach ($flags as $flag) {
            Assert::true($flag->isWritable);
        }
    }

    public function sortsFlagsByName(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var list<FlagPresenter> $flags */
        $flags = $renderer->parameters['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);

        $expected = $names;
        sort($expected);

        Assert::same($names, $expected);
    }

    public function gridRendersKillSwitchBadge(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        Assert::string($gridHtml)->contains('KILLED');
        Assert::string($gridHtml)->contains('text-bg-danger');
    }

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

        Assert::false($renderer->parameters['isWritable']);

        /** @var list<FlagPresenter> $flags */
        $flags = $renderer->parameters['flags'];
        foreach ($flags as $flag) {
            Assert::false($flag->isWritable);
        }

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        Assert::string($gridHtml)->notContains('btn-outline-danger');
        Assert::string($gridHtml)->notContains('btn-outline-primary');
    }
}
