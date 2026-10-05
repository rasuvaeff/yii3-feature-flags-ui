<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(EditPageRenderer::class)]
#[Covers(EditFlagResponder::class)]
final class EditFlagResponderTest extends ActionTestCase
{
    public function returns404ForUnknownName(): void
    {
        $renderer = $this->renderer();

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('does.not.exist');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function rendersExistingFlag(): void
    {
        $renderer = $this->renderer();

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('checkout.v2');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->renderedView(), 'edit');
        Assert::false($this->renderedParameters()['isNew']);
        /** @var FlagForm $form */
        $form = $this->renderedParameters()['form'];
        Assert::false($form->present);
        Assert::same($form->name, 'checkout.v2');
        Assert::true($this->renderedParameters()['isWritable']);
        Assert::same($this->renderedParameters()['updateUrl'], '/admin/flags/checkout.v2');
        Assert::same($this->renderedParameters()['deleteUrl'], '/admin/flags/checkout.v2/delete');
        Assert::same($this->renderedParameters()['listUrl'], '/admin/flags');
    }

    public function rendersExistingFlagEnvironmentsAsJson(): void
    {
        $renderer = $this->renderer();

        $this->editResponder($renderer, $this->writableProvider())->respondExisting('search.beta');

        /** @var FlagForm $form */
        $form = $this->renderedParameters()['form'];
        Assert::same($form->environments, '["prod","staging"]');
    }

    public function rendersCreateFormForNew(): void
    {
        $renderer = $this->renderer();

        $response = $this->editResponder($renderer, $this->writableProvider())->respondNew();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::true($this->renderedParameters()['isNew']);
        /** @var FlagForm $form */
        $form = $this->renderedParameters()['form'];
        Assert::same($form->name, '');
        Assert::null($this->renderedParameters()['flag']);
        Assert::null($this->renderedParameters()['deleteUrl']);
        Assert::same($this->renderedParameters()['updateUrl'], '/admin/flags/new');
        Assert::same($this->renderedParameters()['listUrl'], '/admin/flags');
        Assert::true($this->renderedParameters()['isWritable']);
    }

    public function newFormFlagsProviderAsReadOnlyWhenProviderNotWritable(): void
    {
        $renderer = $this->renderer();

        $readOnlyProvider = new readonly class ($this->flags()) implements \Rasuvaeff\Yii3FeatureFlags\FlagProvider {
            /** @param array<string, \Rasuvaeff\Yii3FeatureFlags\Flag> $flags */
            public function __construct(private array $flags) {}

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }
        };

        $this->editResponder($renderer, $readOnlyProvider)->respondNew();

        Assert::false($this->renderedParameters()['isWritable']);
    }

    public function readOnlyProviderDisablesFormFields(): void
    {
        $renderer = $this->renderer();

        $readOnlyProvider = new readonly class ($this->flags()) implements \Rasuvaeff\Yii3FeatureFlags\FlagProvider {
            /** @param array<string, \Rasuvaeff\Yii3FeatureFlags\Flag> $flags */
            public function __construct(private array $flags) {}

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }
        };

        $this->editResponder($renderer, $readOnlyProvider)->respondExisting('checkout.v2');

        Assert::false($this->renderedParameters()['isWritable']);
    }
}
