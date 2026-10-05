<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Delete;

use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete\Action as YiiDeleteAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(YiiDeleteAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesProcessorWithRouteArgument(): void
    {
        $provider = $this->writableProvider();
        $action = new YiiDeleteAction(
            processor: $this->deleteProcessor($provider),
        );

        $response = $action->__invoke('checkout.v2');

        Assert::same($response->getStatusCode(), Status::FOUND);
        verify(fn() => $provider->remove('checkout.v2'), times: 1);
    }
}
