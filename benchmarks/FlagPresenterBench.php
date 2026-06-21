<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Benchmarks;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;
use Testo\Bench;

final class FlagPresenterBench
{
    #[Bench(
        callables: [
            'with-environments' => [self::class, 'constructWithEnvironments'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function constructSimple(): FlagPresenter
    {
        return new FlagPresenter(
            flag: new Flag(name: 'dark-mode', enabled: true, rollout: 100),
            isWritable: true,
            editUrl: '/flags/dark-mode/edit',
            deleteUrl: '/flags/dark-mode/delete',
        );
    }

    public static function constructWithEnvironments(): FlagPresenter
    {
        return new FlagPresenter(
            flag: new Flag(name: 'dark-mode', enabled: true, rollout: 50),
            isWritable: true,
            editUrl: '/flags/dark-mode/edit',
            deleteUrl: '/flags/dark-mode/delete',
            environments: ['production', 'staging', 'development', 'testing', 'qa'],
        );
    }
}
