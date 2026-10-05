<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Support;

use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Yiisoft\Router\UrlGeneratorInterface;

use function Rasuvaeff\Understudy\when;

/**
 * The deterministic URL generator of the test suite, as an understudy double:
 * deterministic link/redirect targets over the default flag route names,
 * mounted at /admin/flags, without a router. Replaces the deleted
 * FakeUrlGenerator.
 */
final class Urls
{
    public static function generator(): UrlGeneratorInterface
    {
        $generator = Understudy::for(UrlGeneratorInterface::class);
        when(fn() => $generator->generate(Arg::any(), Arg::any(), Arg::any(), Arg::any()))
            ->answers(static fn(Invocation $call): string => self::urlFor($call->arg('name'), $call->arg('arguments')));

        return $generator;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function urlFor(string $route, array $arguments): string
    {
        $flagName = rawurlencode((string) ($arguments['name'] ?? ''));

        return match ($route) {
            FlagRoutes::LIST => '/admin/flags',
            FlagRoutes::CREATE => '/admin/flags/new',
            FlagRoutes::EDIT => '/admin/flags/' . $flagName . '/edit',
            FlagRoutes::UPDATE => '/admin/flags/' . $flagName,
            FlagRoutes::DELETE => '/admin/flags/' . $flagName . '/delete',
            default => '/' . $route,
        };
    }
}
