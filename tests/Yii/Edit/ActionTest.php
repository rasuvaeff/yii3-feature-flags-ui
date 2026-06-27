<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Edit;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit\Action as YiiEditAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiEditAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesRespondExistingWithRouteArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->__invoke('checkout.v2');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'edit');
        /** @var FlagForm $form */
        $form = $renderer->parameters['form'];
        Assert::same($form->name, 'checkout.v2');
    }

    public function newActionInvokesRespondNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->new();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::true($renderer->parameters['isNew']);
    }
}
