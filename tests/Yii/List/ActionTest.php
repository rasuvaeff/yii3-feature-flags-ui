<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\List;

use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\List\Action as YiiListAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiListAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesResponderWithoutRequestArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiListAction(
            responder: $this->listResponder($renderer, $this->writableProvider()),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'list');
    }
}
