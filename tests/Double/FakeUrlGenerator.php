<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double;

use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Stringable;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Deterministic URL generator over the default flag route names, mounted at
 * `/admin/flags`. Lets tests assert link/redirect targets without a router.
 */
final class FakeUrlGenerator implements UrlGeneratorInterface
{
    private string $uriPrefix = '';

    #[\Override]
    public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
    {
        $flagName = (string) ($arguments['name'] ?? '');

        return match ($name) {
            FlagRoutes::LIST => '/admin/flags',
            FlagRoutes::CREATE => '/admin/flags/new',
            FlagRoutes::EDIT => '/admin/flags/' . rawurlencode($flagName) . '/edit',
            FlagRoutes::UPDATE => '/admin/flags/' . rawurlencode($flagName),
            FlagRoutes::DELETE => '/admin/flags/' . rawurlencode($flagName) . '/delete',
            default => '/' . $name,
        };
    }

    #[\Override]
    public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
    {
        return 'https://example.test' . $this->generate($name, $arguments, $queryParameters, $hash);
    }

    #[\Override]
    public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
    {
        return $this->generate($fallbackRouteName ?? FlagRoutes::LIST, $replacedArguments, $queryParameters, $hash);
    }

    #[\Override]
    public function getUriPrefix(): string
    {
        return $this->uriPrefix;
    }

    #[\Override]
    public function setUriPrefix(string $name): void
    {
        $this->uriPrefix = $name;
    }

    #[\Override]
    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
}
