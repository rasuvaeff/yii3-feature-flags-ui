<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Validation;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(FlagFormNormalizer::class)]
final class FlagFormNormalizerTest
{
    private FlagFormNormalizer $normalizer;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->normalizer = new FlagFormNormalizer();
    }

    public function castsStringRolloutToInt(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: '75'));

        Assert::same($flag->rollout, 75);
    }

    public function emptyRolloutFallsBackTo100(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: ''));

        Assert::same($flag->rollout, 100);
    }

    public function trimsRolloutWhitespace(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: ' 25 '));

        Assert::same($flag->rollout, 25);
    }

    public function whitespaceOnlyRolloutFallsBackTo100(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: '   '));

        Assert::same($flag->rollout, 100);
    }

    public function whitespaceOnlyEnvironmentsYieldsEmptyArray(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: '   '));

        Assert::same($flag->environments, []);
    }

    public function emptySaltFallsBackToName(): void
    {
        $flag = $this->normalizer->toFlag($this->form(name: 'feature.x', salt: ''));

        Assert::same($flag->salt, 'feature.x');
    }

    public function emptyEnvironmentsYieldsEmptyArray(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: ''));

        Assert::same($flag->environments, []);
    }

    public function jsonEnvironmentsDecodedToList(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: '["prod", "staging"]'));

        Assert::same($flag->environments, ['prod', 'staging']);
    }

    private function form(
        string $name = 'feature.x',
        bool $enabled = true,
        string $rollout = '100',
        string $salt = '',
        bool $killSwitch = false,
        string $environments = '',
    ): FlagForm {
        return new FlagForm(
            present: true,
            name: $name,
            enabled: $enabled,
            rollout: $rollout,
            salt: $salt,
            killSwitch: $killSwitch,
            environments: $environments,
        );
    }
}
