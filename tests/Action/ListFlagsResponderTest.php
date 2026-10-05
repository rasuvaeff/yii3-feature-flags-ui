<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
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
        $renderer = $this->renderer();

        $response = $this->listResponder($renderer, $this->writableProvider())->respond();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->renderedView(), 'list');
        Assert::true(array_key_exists('flags', $this->renderedParameters()));
        Assert::true(array_key_exists('gridHtml', $this->renderedParameters()));
        Assert::true($this->renderedParameters()['gridHtml'] !== '');
        Assert::true($this->renderedParameters()['isWritable']);
        Assert::same($this->renderedParameters()['createUrl'], '/admin/flags/new');

        /** @var list<FlagPresenter> $flags */
        $flags = $this->renderedParameters()['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);
        Assert::contains($names, 'checkout.v2');
        Assert::contains($names, 'billing.maintenance');

        foreach ($flags as $flag) {
            Assert::true($flag->isWritable);
        }
    }

    public function sortsFlagsByName(): void
    {
        $renderer = $this->renderer();

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var list<FlagPresenter> $flags */
        $flags = $this->renderedParameters()['flags'];
        $names = array_map(static fn(FlagPresenter $f): string => $f->name, $flags);

        $expected = $names;
        sort($expected);

        Assert::same($names, $expected);
    }

    public function gridRendersKillSwitchBadge(): void
    {
        $renderer = $this->renderer();

        $this->listResponder($renderer, $this->writableProvider())->respond();

        /** @var string $gridHtml */
        $gridHtml = $this->renderedParameters()['gridHtml'];
        Assert::string($gridHtml)->contains('KILLED');
        Assert::string($gridHtml)->contains('text-bg-danger');
    }

    public function gridHidesWriteControlsForReadOnlyProvider(): void
    {
        $renderer = $this->renderer();

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

        Assert::false($this->renderedParameters()['isWritable']);

        /** @var list<FlagPresenter> $flags */
        $flags = $this->renderedParameters()['flags'];
        foreach ($flags as $flag) {
            Assert::false($flag->isWritable);
        }

        /** @var string $gridHtml */
        $gridHtml = $this->renderedParameters()['gridHtml'];
        Assert::string($gridHtml)->notContains('btn-outline-danger');
        Assert::string($gridHtml)->notContains('btn-outline-primary');
    }
}
