<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withSkip([
        // @var mixed tags in fromParams() are load-bearing for Psalm level 1:
        // array<string, mixed> access returns mixed, and explicit @var mixed prevents
        // MixedAssignment without @psalm-suppress.
        RemoveUselessVarTagRector::class => [
            __DIR__ . '/src/FlagRoutes.php',
        ],
    ]);
