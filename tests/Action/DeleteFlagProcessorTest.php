<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Yiisoft\User\CurrentUser;

#[CoversClass(DeleteFlagProcessor::class)]
final class DeleteFlagProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider(flags: $this->flags());
        $this->events = new RecordingEventDispatcher();
    }

    #[Test]
    public function returns404ForUnknownName(): void
    {
        $response = $this->processor()->process('nope');

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function read_only_providerReturns403(): void
    {
        $readOnlyProvider = new readonly class ($this->flags()) implements \Rasuvaeff\Yii3FeatureFlags\FlagProvider {
            /** @param array<string, \Rasuvaeff\Yii3FeatureFlags\Flag> $flags */
            public function __construct(private array $flags) {}

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }
        };

        $processor = new DeleteFlagProcessor(
            provider: $readOnlyProvider,
            responseFactory: $this->http,
            urls: $this->urls(),
        );

        $response = $processor->process('checkout.v2');

        $this->assertSame(Status::FORBIDDEN, $response->getStatusCode());
    }

    #[Test]
    public function deletesFlagAndRedirects(): void
    {
        $response = $this->processor()->process('checkout.v2');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame('/admin/flags', $response->getHeaderLine('Location'));
        $this->assertSame(['checkout.v2'], $this->provider->removeCalls);
    }

    #[Test]
    public function dispatchesDeletedEventWithActor(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process('checkout.v2');

        $this->assertCount(1, $this->events->events);
        $event = $this->events->events[0] ?? null;
        $this->assertInstanceOf(FlagChanged::class, $event);
        $this->assertSame('checkout.v2', $event->name);
        $this->assertSame(FlagChanged::OPERATION_DELETED, $event->operation);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new DeleteFlagProcessor(
            provider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
        );

        $response = $processor->process('checkout.v2');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(['checkout.v2'], $this->provider->removeCalls);
    }

    private function processor(?CurrentUser $currentUser = null): DeleteFlagProcessor
    {
        return $this->deleteProcessor(
            provider: $this->provider,
            currentUser: $currentUser,
            eventDispatcher: $this->events,
        );
    }
}
