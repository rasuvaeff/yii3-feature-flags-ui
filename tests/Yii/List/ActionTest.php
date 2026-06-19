<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\List;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\List\Action as YiiListAction;

#[CoversClass(YiiListAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesResponderWithoutRequestArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiListAction(
            responder: $this->listResponder($renderer, $this->writableProvider()),
        );

        $response = $action->__invoke();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('list', $renderer->view);
    }
}
