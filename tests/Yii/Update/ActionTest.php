<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Update;

use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update\Action as YiiUpdateAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiUpdateAction::class)]
final class ActionTest extends ActionTestCase
{
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

        Assert::same($response->getStatusCode(), Status::FOUND);
    }

    public function newActionDelegatesToProcessNew(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $this->writableProvider(), renderer: $renderer),
        );

        $response = $action->new(
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'feature.fresh', 'enabled' => '1', 'rollout' => '50']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
    }
}
