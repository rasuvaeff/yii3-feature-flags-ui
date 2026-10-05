<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(UpdateFlagProcessor::class)]
#[Covers(Status::class)]
final class UpdateFlagProcessorTest extends ActionTestCase
{
    private WritableFlagProvider $provider;

    private EventDispatcherInterface $events;

    private TemplateRendererInterface $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->writableProvider();
        $this->events = $this->eventDispatcher();
        $this->renderer = $this->renderer();
    }

    public function returns404ForUnknownName(): void
    {
        $response = $this->processor()->processExisting(
            'nope',
            $this->request('POST', parsedBody: ['Flag' => ['name' => 'nope', 'rollout' => '100']]),
        );

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
        Assert::same(Status::NOT_FOUND, 404);
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
        Assert::same(Status::FORBIDDEN, 403);
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
        Assert::same(Status::FOUND, 302);
        Assert::same($response->getHeaderLine('Location'), '/admin/flags');
        Assert::same($this->savedFlagNames(), ['checkout.v2']);
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

        Assert::same($this->savedFlagNames(), ['checkout.v2']);
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
        Assert::same(Status::OK, 200);
        Assert::same($this->renderedView(), 'edit');
        Assert::notNull($this->renderedParameters()['error']);
        Assert::string($this->renderedParameters()['error'])->contains('Rollout');
        Assert::false($this->renderedParameters()['isNew']);
        Assert::true($this->renderedParameters()['isWritable']);
        verify(fn() => $this->provider->save(Arg::any()), never: true);
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
        verify(fn() => $this->provider->save(Arg::any()), never: true);
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
        Assert::same($this->savedFlagNames(), ['feature.new']);
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
        Assert::same($this->renderedView(), 'edit');
        Assert::true($this->renderedParameters()['isNew']);
        Assert::true($this->renderedParameters()['isWritable']);
        verify(fn() => $this->provider->save(Arg::any()), never: true);
    }

    public function absentBodyRedirects(): void
    {
        $response = $this->processor()->processExisting('checkout.v2', $this->request('POST'));

        Assert::same($response->getStatusCode(), Status::FOUND);
        verify(fn() => $this->provider->save(Arg::any()), never: true);
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

        Assert::count($this->dispatchedEvents(), 1);
        $event = $this->dispatchedEvents()[0] ?? null;
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

        Assert::count($this->dispatchedEvents(), 1);
        /** @var FlagChanged $event */
        $event = $this->dispatchedEvents()[0];
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
        Assert::same($this->savedFlagNames(), ['checkout.v2']);
        verify(fn() => $this->events->dispatch(Arg::any()), never: true);
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

    /**
     * @return list<string>
     */
    private function savedFlagNames(): array
    {
        return array_map(
            static fn(Invocation $call): string => $call->arg('flag')->name,
            Understudy::calls(fn() => $this->provider->save(Arg::any())),
        );
    }

    /**
     * @return list<object>
     */
    private function dispatchedEvents(): array
    {
        return array_map(
            static fn(Invocation $call): object => $call->arg('event'),
            Understudy::calls(fn() => $this->events->dispatch(Arg::any())),
        );
    }
}
