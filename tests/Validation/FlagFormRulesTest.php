<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Validation;

use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormRules;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Validator\Validator;

#[Test]
#[Covers(FlagFormRules::class)]
final class FlagFormRulesTest
{
    private Validator $validator;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->validator = new Validator();
    }

    #[DataProvider('validNameProvider')]
    public function acceptsValidName(string $name): void
    {
        Assert::true($this->validateField('name', $name)->isValid());
    }

    public static function validNameProvider(): iterable
    {
        yield 'simple lowercase' => ['checkout'];
        yield 'dotted' => ['checkout.v2'];
        yield 'hyphen' => ['search-beta'];
        yield 'underscore' => ['feature_fresh'];
        yield 'digits' => ['f.1.2'];
    }

    #[DataProvider('invalidNameProvider')]
    public function rejectsInvalidName(string $name): void
    {
        Assert::false($this->validateField('name', $name)->isValid());
    }

    public function emptyNameShowsRequiredMessage(): void
    {
        $result = $this->validateField('name', '');

        Assert::false($result->isValid());
        Assert::same($result->getErrorMessages()[0], 'Flag name is required');
    }

    public function uppercaseNameShowsRegexMessage(): void
    {
        $result = $this->validateField('name', 'Checkout');

        Assert::false($result->isValid());
        Assert::string($result->getErrorMessages()[0])->contains('lowercase');
    }

    public static function invalidNameProvider(): iterable
    {
        yield 'uppercase' => ['Checkout'];
        yield 'leading digit' => ['1flag'];
        yield 'space' => ['invalid name'];
        yield 'empty' => [''];
    }

    public function acceptsIntRollout(): void
    {
        Assert::true($this->validateField('rollout', 42)->isValid());
    }

    public function rejectsNonStringNonIntRollout(): void
    {
        Assert::false($this->validateField('rollout', true)->isValid());
    }

    #[DataProvider('validRolloutProvider')]
    public function acceptsValidRollout(string $rollout): void
    {
        Assert::true($this->validateField('rollout', $rollout)->isValid());
    }

    public static function validRolloutProvider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'half' => ['50'];
        yield 'full' => ['100'];
        yield 'with spaces' => [' 75 '];
    }

    #[DataProvider('invalidRolloutProvider')]
    public function rejectsInvalidRollout(string $rollout): void
    {
        Assert::false($this->validateField('rollout', $rollout)->isValid());
    }

    public static function invalidRolloutProvider(): iterable
    {
        yield 'negative' => ['-1'];
        yield 'over 100' => ['101'];
        yield 'text' => ['abc'];
        yield 'empty' => [''];
        yield 'float' => ['1.5'];
    }

    #[DataProvider('validEnvironmentsProvider')]
    public function acceptsValidEnvironments(string $environments): void
    {
        Assert::true($this->validateField('environments', $environments)->isValid());
    }

    public static function validEnvironmentsProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'empty array' => ['[]'];
        yield 'single' => ['["prod"]'];
        yield 'multiple' => ['["prod", "staging"]'];
        yield 'with spaces' => ['   '];
    }

    #[DataProvider('invalidEnvironmentsProvider')]
    public function rejectsInvalidEnvironments(string $environments): void
    {
        Assert::false($this->validateField('environments', $environments)->isValid());
    }

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
