<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\View;

use Rasuvaeff\Yii3FeatureFlags\Flag;

/**
 * Read-model row for the list grid. Wraps a {@see Flag} and exposes scalar
 * fields suitable for table cells and templates. The grid's content callbacks
 * consume this and the edit URL is pre-computed via the router.
 *
 * @internal
 */
final readonly class FlagPresenter
{
    public string $name;

    public bool $enabled;

    public int $rollout;

    public string $salt;

    public bool $killSwitch;

    /**
     * @param list<string> $environments
     */
    public function __construct(
        Flag $flag,
        public bool $isWritable,
        public string $editUrl,
        public string $deleteUrl,
        public array $environments = [],
    ) {
        $this->name = $flag->name;
        $this->enabled = $flag->enabled;
        $this->rollout = $flag->rollout;
        $this->salt = $flag->salt;
        $this->killSwitch = $flag->killSwitch;
    }

    public function environmentsLabel(): string
    {
        return $this->environments === [] ? 'all' : implode(', ', $this->environments);
    }
}
