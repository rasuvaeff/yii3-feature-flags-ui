<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;

#[CoversClass(FlagForm::class)]
final class FlagFormTest extends TestCase
{
    #[Test]
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

        $this->assertTrue($form->present);
        $this->assertSame('checkout.v2', $form->name);
        $this->assertTrue($form->enabled);
        $this->assertSame('75', $form->rollout);
        $this->assertSame('salt-1', $form->salt);
        $this->assertTrue($form->killSwitch);
        $this->assertSame('["prod"]', $form->environments);
    }

    #[Test]
    public function absentWhenBodyIsNotArray(): void
    {
        $form = FlagForm::fromParsedBody(null);

        $this->assertFalse($form->present);
        $this->assertSame('', $form->name);
        $this->assertFalse($form->enabled);
        $this->assertFalse($form->killSwitch);
        $this->assertSame('100', $form->rollout);
        $this->assertSame('', $form->salt);
        $this->assertSame('', $form->environments);
    }

    #[Test]
    public function absentWhenScopeMissing(): void
    {
        $form = FlagForm::fromParsedBody(['Other' => ['name' => 'x']]);

        $this->assertFalse($form->present);
    }

    #[Test]
    public function uncheckedCheckboxesDefaultToFalse(): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'rollout' => '50']]);

        $this->assertTrue($form->present);
        $this->assertFalse($form->enabled);
        $this->assertFalse($form->killSwitch);
    }

    #[Test]
    public function nonStringFieldsCoercedToString(): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'rollout' => 75, 'salt' => 123, 'environments' => false]]);

        $this->assertSame('75', $form->rollout);
        $this->assertSame('123', $form->salt);
        $this->assertSame('', $form->environments);
    }

    #[Test]
    public function getRulesAreAlwaysBuilt(): void
    {
        $form = new FlagForm();

        $rules = [...$form->getRules()];

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('rollout', $rules);
        $this->assertArrayHasKey('environments', $rules);
    }

    #[Test]
    #[DataProvider('checkboxProvider')]
    public function checkboxFieldMapsKnownTruthyValues(mixed $value, bool $expected): void
    {
        $form = FlagForm::fromParsedBody(['Flag' => ['name' => 'x', 'enabled' => $value, 'killSwitch' => $value]]);

        $this->assertSame($expected, $form->enabled);
        $this->assertSame($expected, $form->killSwitch);
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
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
