<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Update;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update\Action as YiiUpdateAction;

#[CoversClass(YiiUpdateAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokeDelegatesToProcessExisting(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $this->writableProvider(), renderer: $renderer),
        );

        $response = $action->__invoke(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'checkout.v2', 'enabled' => '1', 'rollout' => '100']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
    }

    #[Test]
    public function newActionDelegatesToProcessNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $this->writableProvider(), renderer: $renderer),
        );

        $response = $action->new(
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'feature.fresh', 'enabled' => '1', 'rollout' => '50']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
    }
}
