<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
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
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('does.not.exist');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function rendersExistingFlag(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('checkout.v2');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'edit');
        Assert::false($renderer->parameters['isNew']);
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        Assert::false($form->present);
        Assert::same($form->name, 'checkout.v2');
        Assert::true($renderer->parameters['isWritable']);
        Assert::same($renderer->parameters['updateUrl'], '/admin/flags/checkout.v2');
        Assert::same($renderer->parameters['deleteUrl'], '/admin/flags/checkout.v2/delete');
        Assert::same($renderer->parameters['listUrl'], '/admin/flags');
    }

    public function rendersExistingFlagEnvironmentsAsJson(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->editResponder($renderer, $this->writableProvider())->respondExisting('search.beta');

        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        Assert::same($form->environments, '["prod","staging"]');
    }

    public function rendersCreateFormForNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondNew();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::true($renderer->parameters['isNew']);
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        Assert::same($form->name, '');
        Assert::null($renderer->parameters['flag']);
        Assert::null($renderer->parameters['deleteUrl']);
        Assert::same($renderer->parameters['updateUrl'], '/admin/flags/new');
        Assert::same($renderer->parameters['listUrl'], '/admin/flags');
        Assert::true($renderer->parameters['isWritable']);
    }

    public function newFormFlagsProviderAsReadOnlyWhenProviderNotWritable(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

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

        Assert::false($renderer->parameters['isWritable']);
    }

    public function readOnlyProviderDisablesFormFields(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

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

        Assert::false($renderer->parameters['isWritable']);
    }
}
