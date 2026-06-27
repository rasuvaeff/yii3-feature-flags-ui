<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Service;

use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeUrlGenerator;
use Stringable;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;

#[Test]
#[Covers(FlagUrls::class)]
final class FlagUrlsTest
{
    public function generatesUrlsForDefaultRouteNames(): void
    {
        $urls = new FlagUrls(urlGenerator: new FakeUrlGenerator());

        Assert::same($urls->list(), '/admin/flags');
        Assert::same($urls->create(), '/admin/flags/new');
        Assert::same($urls->edit('checkout.v2'), '/admin/flags/checkout.v2/edit');
        Assert::same($urls->update('checkout.v2'), '/admin/flags/checkout.v2');
        Assert::same($urls->delete('checkout.v2'), '/admin/flags/checkout.v2/delete');
    }

    public function forwardsConfiguredRouteNamesToGenerator(): void
    {
        $recorder = new class implements UrlGeneratorInterface {
            /** @var list<string> */
            public array $names = [];

            #[\Override]
            public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
            {
                $this->names[] = $name;

                return '/';
            }

            #[\Override]
            public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
            {
                return '/';
            }

            #[\Override]
            public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
            {
                return '/';
            }

            #[\Override]
            public function getUriPrefix(): string
            {
                return '';
            }

            #[\Override]
            public function setUriPrefix(string $name): void {}

            #[\Override]
            public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
        };

        $urls = new FlagUrls(
            urlGenerator: $recorder,
            routeNames: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
        );

        $urls->list();
        $urls->edit('k');
        $urls->update('k');

        Assert::same(
            $recorder->names,
            ['admin/flags', 'admin/flags/edit', FlagRoutes::UPDATE],
        );
    }
}
