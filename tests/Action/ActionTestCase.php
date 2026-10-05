<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Action;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Support\Urls;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Testo\Lifecycle\BeforeTest;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;

use function Rasuvaeff\Understudy\when;

abstract class ActionTestCase
{
    protected Psr17Factory $http;

    private Captor $renderViews;

    private Captor $renderParameters;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->http = new Psr17Factory();
    }

    /**
     * @return array<string, Flag>
     */
    protected function flags(): array
    {
        return [
            'checkout.v2' => new Flag(name: 'checkout.v2', enabled: true, rollout: 100),
            'search.beta' => new Flag(name: 'search.beta', enabled: false, rollout: 50, killSwitch: false, environments: ['prod', 'staging']),
            'billing.maintenance' => new Flag(name: 'billing.maintenance', enabled: true, rollout: 100, killSwitch: true),
        ];
    }

    protected function urls(): FlagUrls
    {
        return new FlagUrls(urlGenerator: Urls::generator());
    }

    /**
     * The rendered view name of the last render() call.
     */
    protected function renderedView(): string
    {
        /** @var string */
        return $this->renderViews->last();
    }

    /**
     * @return array<string, mixed>
     */
    protected function renderedParameters(): array
    {
        /** @var array<string, mixed> */
        return $this->renderParameters->last();
    }

    protected function renderer(): TemplateRendererInterface
    {
        $renderer = Understudy::for(TemplateRendererInterface::class);
        $this->renderViews = Arg::captor();
        $this->renderParameters = Arg::captor();
        when(fn() => $renderer->render($this->renderViews->capture(), $this->renderParameters->capture()))
            ->answers(fn(): ResponseInterface => $this->http->createResponse(200));

        return $renderer;
    }

    protected function listResponder(TemplateRendererInterface $renderer, FlagProvider $provider): ListFlagsResponder
    {
        return new ListFlagsResponder(
            renderer: $renderer,
            provider: $provider,
            urls: $this->urls(),
            gridFactory: new FlagsGridFactory(new TestContainer()),
        );
    }

    protected function editResponder(TemplateRendererInterface $renderer, FlagProvider $provider): EditFlagResponder
    {
        return new EditFlagResponder(
            editPage: $this->editPage($renderer),
            responseFactory: $this->http,
            provider: $provider,
        );
    }

    protected function editPage(TemplateRendererInterface $renderer): EditPageRenderer
    {
        return new EditPageRenderer(
            renderer: $renderer,
            urls: $this->urls(),
        );
    }

    protected function updateProcessor(
        FlagProvider $provider,
        TemplateRendererInterface $renderer,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): UpdateFlagProcessor {
        return new UpdateFlagProcessor(
            provider: $provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            editPage: $this->editPage($renderer),
            validator: new Validator(),
            normalizer: new FlagFormNormalizer(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? $this->eventDispatcher(),
        );
    }

    protected function deleteProcessor(
        FlagProvider $provider,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): DeleteFlagProcessor {
        return new DeleteFlagProcessor(
            provider: $provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? $this->eventDispatcher(),
        );
    }

    /**
     * A dispatcher double that records every dispatch and hands the event
     * straight back — what a passthrough dispatcher owes its caller.
     */
    protected function eventDispatcher(): EventDispatcherInterface
    {
        $dispatcher = Understudy::for(EventDispatcherInterface::class);
        when(fn() => $dispatcher->dispatch(Arg::any()))
            ->answers(static fn(Invocation $call): object => $call->arg('event'));

        return $dispatcher;
    }

    protected function currentUser(?string $id): CurrentUser
    {
        $currentUser = new CurrentUser(
            identityRepository: $this->identityRepository(),
            eventDispatcher: $this->eventDispatcher(),
        );

        if ($id !== null) {
            $currentUser->overrideIdentity(new readonly class ($id) implements \Yiisoft\Auth\IdentityInterface {
                public function __construct(private string $id) {}

                #[\Override]
                public function getId(): string
                {
                    return $this->id;
                }
            });
        }

        return $currentUser;
    }

    private function identityRepository(): IdentityRepositoryInterface
    {
        $repository = Understudy::for(IdentityRepositoryInterface::class);
        when(fn() => $repository->findIdentity(Arg::any()))->returns(null);

        return $repository;
    }

    /**
     * @param array<string, Flag>|null $flags
     */
    protected function writableProvider(?array $flags = null): WritableFlagProvider
    {
        $provider = Understudy::for(WritableFlagProvider::class);
        when(fn() => $provider->getFlags())->returns($flags ?? $this->flags());

        return $provider;
    }

    /**
     * @param array<string, mixed>|null $parsedBody
     */
    protected function request(string $method, ?array $parsedBody = null): ServerRequestInterface
    {
        $request = $this->http->createServerRequest($method, '/admin/flags');

        if ($parsedBody !== null) {
            return $request->withParsedBody($parsedBody);
        }

        return $request;
    }
}
