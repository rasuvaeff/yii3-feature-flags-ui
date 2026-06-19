<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Yii\Delete;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete\Action as YiiDeleteAction;

#[CoversClass(YiiDeleteAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesProcessorWithRouteArgument(): void
    {
        $provider = new RecordingWritableProvider(flags: $this->flags());
        $action = new YiiDeleteAction(
            processor: $this->deleteProcessor($provider),
        );

        $response = $action->__invoke('checkout.v2');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(['checkout.v2'], $provider->removeCalls);
    }
}
