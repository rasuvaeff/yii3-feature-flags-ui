<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;

#[CoversClass(EditPageRenderer::class)]
#[CoversClass(EditFlagResponder::class)]
final class EditFlagResponderTest extends ActionTestCase
{
    #[Test]
    public function returns404ForUnknownName(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('does.not.exist');

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function rendersExistingFlag(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondExisting('checkout.v2');

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $renderer->view);
        $this->assertFalse($renderer->parameters['isNew']);
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        $this->assertFalse($form->present);
        $this->assertSame('checkout.v2', $form->name);
        $this->assertTrue($renderer->parameters['isWritable']);
        $this->assertSame('/admin/flags/checkout.v2', $renderer->parameters['updateUrl']);
        $this->assertSame('/admin/flags/checkout.v2/delete', $renderer->parameters['deleteUrl']);
        $this->assertSame('/admin/flags', $renderer->parameters['listUrl']);
    }

    #[Test]
    public function rendersExistingFlagEnvironmentsAsJson(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->editResponder($renderer, $this->writableProvider())->respondExisting('search.beta');

        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        $this->assertSame('["prod","staging"]', $form->environments);
    }

    #[Test]
    public function rendersCreateFormForNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer, $this->writableProvider())->respondNew();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertTrue($renderer->parameters['isNew']);
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        $this->assertSame('', $form->name);
        $this->assertNull($renderer->parameters['flag']);
        $this->assertNull($renderer->parameters['deleteUrl']);
        $this->assertSame('/admin/flags/new', $renderer->parameters['updateUrl']);
        $this->assertSame('/admin/flags', $renderer->parameters['listUrl']);
        $this->assertTrue($renderer->parameters['isWritable']);
    }

    #[Test]
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

        $this->assertFalse($renderer->parameters['isWritable']);
    }

    #[Test]
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

        $this->assertFalse($renderer->parameters['isWritable']);
    }
}
