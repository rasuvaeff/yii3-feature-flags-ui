<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;

final class RecordingWritableProvider implements WritableFlagProvider
{
    /**
     * @param array<string, Flag> $flags
     */
    public function __construct(
        private array $flags = [],
    ) {}

    /**
     * @return array<string, Flag>
     */
    #[\Override]
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @return list<string>
     */
    public array $saveCalls = [];

    /**
     * @return list<string>
     */
    public array $removeCalls = [];

    #[\Override]
    public function save(Flag $flag): void
    {
        $this->saveCalls[] = $flag->name;
        $this->flags[$flag->name] = $flag;
    }

    #[\Override]
    public function remove(string $name): void
    {
        $this->removeCalls[] = $name;
        unset($this->flags[$name]);
    }
}
