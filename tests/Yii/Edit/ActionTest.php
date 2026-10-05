<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Edit;

use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
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
        $renderer = $this->renderer();
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->__invoke('checkout.v2');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->renderedView(), 'edit');
        /** @var FlagForm $form */
        $form = $this->renderedParameters()['form'];
        Assert::same($form->name, 'checkout.v2');
    }

    public function newActionInvokesRespondNew(): void
    {
        $renderer = $this->renderer();
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer, $this->writableProvider()),
        );

        $response = $action->new();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::true($this->renderedParameters()['isNew']);
    }
}
