<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;

#[CoversClass(UpdateFlagProcessor::class)]
final class UpdateFlagProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    private FakeTemplateRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider(flags: $this->flags());
        $this->events = new RecordingEventDispatcher();
        $this->renderer = new FakeTemplateRenderer($this->http);
    }

    #[Test]
    public function returns404ForUnknownName(): void
    {
        $response = $this->processor()->processExisting(
            'nope',
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'nope', 'rollout' => '100']]),
        );

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function read_only_providerReturns403(): void
    {
        $readOnlyProvider = new readonly class ($this->flags()) implements \Rasuvaeff\Yii3FeatureFlags\FlagProvider {
            /** @param array<string, Flag> $flags */
            public function __construct(private array $flags) {}

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }
        };

        $processor = new UpdateFlagProcessor(
            provider: $readOnlyProvider,
            responseFactory: $this->http,
            urls: $this->urls(),
            editPage: $this->editPage($this->renderer),
            validator: new Validator(),
            normalizer: new FlagFormNormalizer(),
        );

        $response = $processor->processExisting('checkout.v2', $this->request('POST', parsedBody: ['Flag' => ['name' => 'checkout.v2', 'rollout' => '100']]));

        $this->assertSame(Status::FORBIDDEN, $response->getStatusCode());
    }

    #[Test]
    public function validFormSavesAndRedirects(): void
    {
        $response = $this->processor()->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => '75',
                'salt' => 'checkout',
                'environments' => '',
            ]]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame('/admin/flags', $response->getHeaderLine('Location'));
        $this->assertSame(['checkout.v2'], $this->provider->saveCalls);
    }

    #[Test]
    public function ignoresSubmittedNameOnEditExisting(): void
    {
        $this->processor()->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'injected.name',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        $this->assertSame(['checkout.v2'], $this->provider->saveCalls);
    }

    #[Test]
    public function invalidRolloutReRendersWithError(): void
    {
        $response = $this->processor()->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => 'abc',
            ]]),
        );

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $this->renderer->view);
        $this->assertNotNull($this->renderer->parameters['error']);
        $this->assertStringContainsString('Rollout', $this->renderer->parameters['error']);
        $this->assertFalse($this->renderer->parameters['isNew']);
        $this->assertTrue($this->renderer->parameters['isWritable']);
        $this->assertSame([], $this->provider->saveCalls);
    }

    #[Test]
    public function invalidEnvironmentsReRendersWithError(): void
    {
        $response = $this->processor()->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => '100',
                'environments' => 'not-json',
            ]]),
        );

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame([], $this->provider->saveCalls);
    }

    #[Test]
    public function createNewFlagViaProcessNew(): void
    {
        $response = $this->processor()->processNew(
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'feature.new',
                'enabled' => '1',
                'rollout' => '50',
            ]]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(['feature.new'], $this->provider->saveCalls);
    }

    #[Test]
    public function invalidNameOnCreateReRenders(): void
    {
        $response = $this->processor()->processNew(
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'Invalid Name',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $this->renderer->view);
        $this->assertTrue($this->renderer->parameters['isNew']);
        $this->assertTrue($this->renderer->parameters['isWritable']);
        $this->assertSame([], $this->provider->saveCalls);
    }

    #[Test]
    public function absentBodyRedirects(): void
    {
        $response = $this->processor()->processExisting('checkout.v2', $this->request('POST'));

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame([], $this->provider->saveCalls);
    }

    #[Test]
    public function dispatchesSavedEventWithActor(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        $this->assertCount(1, $this->events->events);
        $event = $this->events->events[0] ?? null;
        $this->assertInstanceOf(FlagChanged::class, $event);
        $this->assertSame('checkout.v2', $event->name);
        $this->assertSame(FlagChanged::OPERATION_SAVED, $event->operation);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function dispatchesSavedEventWithNullActorWhenNoCurrentUser(): void
    {
        $processor = new UpdateFlagProcessor(
            provider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            editPage: $this->editPage($this->renderer),
            validator: new Validator(),
            normalizer: new FlagFormNormalizer(),
            eventDispatcher: $this->events,
        );

        $processor->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        $this->assertCount(1, $this->events->events);
        /** @var FlagChanged $event */
        $event = $this->events->events[0];
        $this->assertNull($event->actor);
    }

    #[Test]
    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new UpdateFlagProcessor(
            provider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            editPage: $this->editPage($this->renderer),
            validator: new Validator(),
            normalizer: new FlagFormNormalizer(),
        );

        $response = $processor->processExisting(
            'checkout.v2',
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'checkout.v2',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(['checkout.v2'], $this->provider->saveCalls);
        $this->assertSame([], $this->events->events);
    }

    private function processor(?CurrentUser $currentUser = null): UpdateFlagProcessor
    {
        return $this->updateProcessor(
            provider: $this->provider,
            renderer: $this->renderer,
            currentUser: $currentUser,
            eventDispatcher: $this->events,
        );
    }
}
