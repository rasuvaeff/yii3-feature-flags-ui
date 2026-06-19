<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Edit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit\Action as YiiEditAction;

#[CoversClass(YiiEditAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesRespondExistingWithRouteArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->__invoke('checkout.v2');

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $renderer->view);
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        $this->assertSame('checkout.v2', $form->name);
    }

    #[Test]
    public function newActionInvokesRespondNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->new();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertTrue($renderer->parameters['isNew']);
    }
}
