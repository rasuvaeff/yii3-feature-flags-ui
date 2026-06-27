<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;

#[Test]
#[Covers(DeleteFlagProcessor::class)]
final class DeleteFlagProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider(flags: $this->flags());
        $this->events = new RecordingEventDispatcher();
    }

    public function returns404ForUnknownName(): void
    {
        $response = $this->processor()->process('nope');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

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

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
    }

    public function deletesFlagAndRedirects(): void
    {
        $response = $this->processor()->process('checkout.v2');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($response->getHeaderLine('Location'), '/admin/flags');
        Assert::same($this->provider->removeCalls, ['checkout.v2']);
    }

    public function dispatchesDeletedEventWithActor(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process('checkout.v2');

        Assert::count($this->events->events, 1);
        $event = $this->events->events[0] ?? null;
        Assert::instanceOf($event, FlagChanged::class);
        Assert::same($event->name, 'checkout.v2');
        Assert::same($event->operation, FlagChanged::OPERATION_DELETED);
        Assert::same($event->actor, 'user-1');
    }

    public function dispatchesDeletedEventWithNullActorWhenNoCurrentUser(): void
    {
        $processor = new DeleteFlagProcessor(
            provider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            eventDispatcher: $this->events,
        );

        $processor->process('checkout.v2');

        Assert::count($this->events->events, 1);
        /** @var FlagChanged $event */
        $event = $this->events->events[0];
        Assert::null($event->actor);
    }

    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new DeleteFlagProcessor(
            provider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
        );

        $response = $processor->process('checkout.v2');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->removeCalls, ['checkout.v2']);
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
