<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormRules;
use Yiisoft\Validator\Validator;

#[CoversClass(FlagFormRules::class)]
final class FlagFormRulesTest extends TestCase
{
    private Validator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    #[Test]
    #[DataProvider('validNameProvider')]
    public function acceptsValidName(string $name): void
    {
        $this->assertTrue($this->validateField('name', $name)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validNameProvider(): iterable
    {
        yield 'simple lowercase' => ['checkout'];
        yield 'dotted' => ['checkout.v2'];
        yield 'hyphen' => ['search-beta'];
        yield 'underscore' => ['feature_fresh'];
        yield 'digits' => ['f.1.2'];
    }

    #[Test]
    #[DataProvider('invalidNameProvider')]
    public function rejectsInvalidName(string $name): void
    {
        $this->assertFalse($this->validateField('name', $name)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNameProvider(): iterable
    {
        yield 'uppercase' => ['Checkout'];
        yield 'leading digit' => ['1flag'];
        yield 'space' => ['invalid name'];
        yield 'empty' => [''];
    }

    #[Test]
    public function acceptsIntRollout(): void
    {
        $this->assertTrue($this->validateField('rollout', 42)->isValid());
    }

    #[Test]
    public function rejectsNonStringNonIntRollout(): void
    {
        $this->assertFalse($this->validateField('rollout', true)->isValid());
    }

    #[Test]
    #[DataProvider('validRolloutProvider')]
    public function acceptsValidRollout(string $rollout): void
    {
        $this->assertTrue($this->validateField('rollout', $rollout)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validRolloutProvider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'half' => ['50'];
        yield 'full' => ['100'];
        yield 'with spaces' => [' 75 '];
    }

    #[Test]
    #[DataProvider('invalidRolloutProvider')]
    public function rejectsInvalidRollout(string $rollout): void
    {
        $this->assertFalse($this->validateField('rollout', $rollout)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidRolloutProvider(): iterable
    {
        yield 'negative' => ['-1'];
        yield 'over 100' => ['101'];
        yield 'text' => ['abc'];
        yield 'empty' => [''];
        yield 'float' => ['1.5'];
    }

    #[Test]
    #[DataProvider('validEnvironmentsProvider')]
    public function acceptsValidEnvironments(string $environments): void
    {
        $this->assertTrue($this->validateField('environments', $environments)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validEnvironmentsProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'empty array' => ['[]'];
        yield 'single' => ['["prod"]'];
        yield 'multiple' => ['["prod", "staging"]'];
        yield 'with spaces' => ['   '];
    }

    #[Test]
    #[DataProvider('invalidEnvironmentsProvider')]
    public function rejectsInvalidEnvironments(string $environments): void
    {
        $this->assertFalse($this->validateField('environments', $environments)->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEnvironmentsProvider(): iterable
    {
        yield 'invalid json' => ['not-json'];
        yield 'json object' => ['{"prod": true}'];
        yield 'json object with string values' => ['{"prod":"value"}'];
        yield 'array of non-strings' => ['[1, 2]'];
        yield 'array with empty string' => ['[""]'];
    }

    private function validateField(string $field, mixed $value): \Yiisoft\Validator\Result
    {
        $rules = [$field => FlagFormRules::all()[$field]];

        return $this->validator->validate([$field => $value], $rules);
    }
}
