<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Delete;

use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete\Action as YiiDeleteAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiDeleteAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesProcessorWithRouteArgument(): void
    {
        $provider = new RecordingWritableProvider(flags: $this->flags());
        $action = new YiiDeleteAction(
            processor: $this->deleteProcessor($provider),
        );

        $response = $action->__invoke('checkout.v2');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($provider->removeCalls, ['checkout.v2']);
    }
}
