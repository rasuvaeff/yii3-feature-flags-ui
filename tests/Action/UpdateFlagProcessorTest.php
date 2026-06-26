<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;

#[Test]
#[Covers(UpdateFlagProcessor::class)]
final class UpdateFlagProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    private FakeTemplateRenderer $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider(flags: $this->flags());
        $this->events = new RecordingEventDispatcher();
        $this->renderer = new FakeTemplateRenderer($this->http);
    }

    public function returns404ForUnknownName(): void
    {
        $response = $this->processor()->processExisting(
            'nope',
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'nope', 'rollout' => '100']]),
        );

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

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

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
    }

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

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($response->getHeaderLine('Location'), '/admin/flags');
        Assert::same($this->provider->saveCalls, ['checkout.v2']);
    }

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

        Assert::same($this->provider->saveCalls, ['checkout.v2']);
    }

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

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->renderer->view, 'edit');
        Assert::notNull($this->renderer->parameters['error']);
        Assert::string($this->renderer->parameters['error'])->contains('Rollout');
        Assert::false($this->renderer->parameters['isNew']);
        Assert::true($this->renderer->parameters['isWritable']);
        Assert::same($this->provider->saveCalls, []);
    }

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

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->provider->saveCalls, []);
    }

    public function createNewFlagViaProcessNew(): void
    {
        $response = $this->processor()->processNew(
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'feature.new',
                'enabled' => '1',
                'rollout' => '50',
            ]]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->saveCalls, ['feature.new']);
    }

    public function invalidNameOnCreateReRenders(): void
    {
        $response = $this->processor()->processNew(
            $this->request('POST', parsedBody: ['Flag' => [
                'name' => 'Invalid Name',
                'enabled' => '1',
                'rollout' => '100',
            ]]),
        );

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->renderer->view, 'edit');
        Assert::true($this->renderer->parameters['isNew']);
        Assert::true($this->renderer->parameters['isWritable']);
        Assert::same($this->provider->saveCalls, []);
    }

    public function absentBodyRedirects(): void
    {
        $response = $this->processor()->processExisting('checkout.v2', $this->request('POST'));

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->saveCalls, []);
    }

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

        Assert::count($this->events->events, 1);
        $event = $this->events->events[0] ?? null;
        Assert::instanceOf($event, FlagChanged::class);
        Assert::same($event->name, 'checkout.v2');
        Assert::same($event->operation, FlagChanged::OPERATION_SAVED);
        Assert::same($event->actor, 'user-1');
    }

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

        Assert::count($this->events->events, 1);
        /** @var FlagChanged $event */
        $event = $this->events->events[0];
        Assert::null($event->actor);
    }

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

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->saveCalls, ['checkout.v2']);
        Assert::same($this->events->events, []);
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
