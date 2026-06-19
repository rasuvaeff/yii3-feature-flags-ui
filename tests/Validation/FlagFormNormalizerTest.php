<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;

#[CoversClass(FlagFormNormalizer::class)]
final class FlagFormNormalizerTest extends TestCase
{
    private FlagFormNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->normalizer = new FlagFormNormalizer();
    }

    #[Test]
    public function castsStringRolloutToInt(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: '75'));

        $this->assertSame(75, $flag->rollout);
    }

    #[Test]
    public function emptyRolloutFallsBackTo100(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: ''));

        $this->assertSame(100, $flag->rollout);
    }

    #[Test]
    public function trimsRolloutWhitespace(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: ' 25 '));

        $this->assertSame(25, $flag->rollout);
    }

    #[Test]
    public function whitespaceOnlyRolloutFallsBackTo100(): void
    {
        $flag = $this->normalizer->toFlag($this->form(rollout: '   '));

        $this->assertSame(100, $flag->rollout);
    }

    #[Test]
    public function whitespaceOnlyEnvironmentsYieldsEmptyArray(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: '   '));

        $this->assertSame([], $flag->environments);
    }

    #[Test]
    public function emptySaltFallsBackToName(): void
    {
        $flag = $this->normalizer->toFlag($this->form(name: 'feature.x', salt: ''));

        $this->assertSame('feature.x', $flag->salt);
    }

    #[Test]
    public function emptyEnvironmentsYieldsEmptyArray(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: ''));

        $this->assertSame([], $flag->environments);
    }

    #[Test]
    public function jsonEnvironmentsDecodedToList(): void
    {
        $flag = $this->normalizer->toFlag($this->form(environments: '["prod", "staging"]'));

        $this->assertSame(['prod', 'staging'], $flag->environments);
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
