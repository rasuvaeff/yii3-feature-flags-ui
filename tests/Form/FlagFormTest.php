<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Form;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(FlagForm::class)]
final class FlagFormTest
{
    public function readsFlagFieldsFromScopedBody(): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => [
            'name' => 'checkout.v2',
            'enabled' => '1',
            'rollout' => '75',
            'salt' => 'salt-1',
            'killSwitch' => '1',
            'environments' => '["prod"]',
        ]]);

        Assert::true($form->present);
        Assert::same($form->name, 'checkout.v2');
        Assert::true($form->enabled);
        Assert::same($form->rollout, '75');
        Assert::same($form->salt, 'salt-1');
        Assert::true($form->killSwitch);
        Assert::same($form->environments, '["prod"]');
    }

    public function absentWhenBodyIsNotArray(): void
    {
        $form = FlagForm::fromParsedBody(null);

        Assert::false($form->present);
        Assert::same($form->name, '');
        Assert::false($form->enabled);
        Assert::false($form->killSwitch);
        Assert::same($form->rollout, '100');
        Assert::same($form->salt, '');
        Assert::same($form->environments, '');
    }

    public function absentWhenScopeMissing(): void
    {
        $form = FlagForm::fromParsedBody(['Other' => ['name' => 'x']]);

        Assert::false($form->present);
    }

    public function uncheckedCheckboxesDefaultToFalse(): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'rollout' => '50']]);

        Assert::true($form->present);
        Assert::false($form->enabled);
        Assert::false($form->killSwitch);
    }

    public function nonStringFieldsCoercedToString(): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'rollout' => 75, 'salt' => 123, 'environments' => false]]);

        Assert::same($form->rollout, '75');
        Assert::same($form->salt, '123');
        Assert::same($form->environments, '');
    }

    public function getRulesAreAlwaysBuilt(): void
    {
        $form = new FlagForm();

        $rules = [...$form->getRules()];

        Assert::true(array_key_exists('name', $rules));
        Assert::true(array_key_exists('rollout', $rules));
        Assert::true(array_key_exists('environments', $rules));
    }

    #[DataProvider('checkboxProvider')]
    public function checkboxFieldMapsKnownTruthyValues(mixed $value, bool $expected): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'enabled' => $value, 'killSwitch' => $value]]);

        Assert::same($form->enabled, $expected);
        Assert::same($form->killSwitch, $expected);
    }

    public static function checkboxProvider(): iterable
    {
        yield 'bool true' => [true, true];
        yield 'int 1' => [1, true];
        yield 'string 1' => ['1', true];
        yield 'string on' => ['on', true];
        yield 'string true' => ['true', true];
        yield 'bool false' => [false, false];
        yield 'int 0' => [0, false];
        yield 'string off' => ['off', false];
        yield 'string yes' => ['yes', false];
        yield 'null' => [null, false];
    }
}
