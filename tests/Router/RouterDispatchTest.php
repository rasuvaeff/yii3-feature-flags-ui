<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Router;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\ViewTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3FeatureFlagsUi\Tests\Support\Urls;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Request\Body\BadRequestHandler;
use Yiisoft\Request\Body\BadRequestHandlerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\User\Guest\GuestIdentityFactory;
use Yiisoft\User\Guest\GuestIdentityFactoryInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function Rasuvaeff\Understudy\when;

/**
 * Dispatches real requests through yiisoft/router and the package's real route
 * definitions with a plain {@see MiddlewareFactory} — no
 * ParametersResolverInterface is registered anywhere, so yiisoft/input-http
 * (not a dependency of this package) cannot be a hidden requirement for
 * hydrating `#[RouteArgument]` parameters (#16).
 */
#[Test]
#[CoversNothing]
final class RouterDispatchTest
{
    public function editRouteRendersFlagWithoutParametersResolver(): void
    {
        $response = $this->dispatch(new ServerRequest('GET', '/admin/flags/checkout.v2/edit'));

        Assert::same($response->getStatusCode(), Status::OK);

        $body = (string) $response->getBody();

        Assert::string($body)->contains('Edit flag');
        Assert::string($body)->contains('value="checkout.v2"');
    }

    public function editRouteWithUnknownFlagRespondsNotFound(): void
    {
        $response = $this->dispatch(new ServerRequest('GET', '/admin/flags/missing-flag/edit'));

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function updateRouteSavesFlagWithoutParametersResolver(): void
    {
        $provider = $this->provider();
        $response = $this->dispatch(
            (new ServerRequest('POST', '/admin/flags/checkout.v2'))
                ->withParsedBody(['Flag' => ['name' => 'checkout.v2', 'enabled' => '1', 'rollout' => '100']]),
            $provider,
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->savedNames($provider), ['checkout.v2']);
    }

    public function deleteRouteRemovesFlagWithoutParametersResolver(): void
    {
        $provider = $this->provider();
        $response = $this->dispatch(
            new ServerRequest('POST', '/admin/flags/checkout.v2/delete'),
            $provider,
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->removedNames($provider), ['checkout.v2']);
    }

    private function dispatch(ServerRequestInterface $request, ?WritableFlagProvider $provider = null): ResponseInterface
    {
        $http = new Psr17Factory();
        $currentRoute = new CurrentRoute();
        $provider ??= $this->provider();

        $container = new TestContainer([
            CurrentRoute::class => $currentRoute,
            ResponseFactoryInterface::class => $http,
            StreamFactoryInterface::class => $http,
            BadRequestHandlerInterface::class => new BadRequestHandler($http),
            FlagProvider::class => $provider,
            UrlGeneratorInterface::class => Urls::generator(),
            TemplateRendererInterface::class => new ViewTemplateRenderer(
                renderer: new WebViewRenderer(
                    responseFactory: $http,
                    streamFactory: $http,
                    aliases: new Aliases(),
                    view: new WebView(),
                ),
            ),
            EventDispatcherInterface::class => new class implements EventDispatcherInterface {
                #[\Override]
                public function dispatch(object $event): object
                {
                    return $event;
                }
            },
            IdentityRepositoryInterface::class => new class implements IdentityRepositoryInterface {
                #[\Override]
                public function findIdentity(string $id): ?IdentityInterface
                {
                    return null;
                }
            },
            GuestIdentityFactoryInterface::class => new GuestIdentityFactory(),
        ]);

        $collector = new RouteCollector();
        foreach (FlagRoutes::create() as $route) {
            $collector->addRoute($route);
        }

        $router = new Router(
            matcher: new UrlMatcher(new RouteCollection($collector)),
            responseFactory: $http,
            middlewareFactory: new MiddlewareFactory($container),
            currentRoute: $currentRoute,
        );

        return $router->process($request, new class ($http) implements RequestHandlerInterface {
            public function __construct(
                private ResponseFactoryInterface $responseFactory,
            ) {}

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->responseFactory->createResponse(Status::NOT_FOUND);
            }
        });
    }

    private function provider(): WritableFlagProvider
    {
        $provider = Understudy::for(WritableFlagProvider::class);
        when(fn() => $provider->getFlags())->returns([
            'checkout.v2' => new Flag(name: 'checkout.v2', enabled: true, rollout: 100),
        ]);

        return $provider;
    }

    /**
     * @return list<string>
     */
    private function savedNames(WritableFlagProvider $provider): array
    {
        return array_map(
            static fn(Invocation $call): string => $call->arg('flag')->name,
            Understudy::calls(fn() => $provider->save(Arg::any())),
        );
    }

    /**
     * @return list<string>
     */
    private function removedNames(WritableFlagProvider $provider): array
    {
        return array_map(
            static fn(Invocation $call): string => $call->arg('name'),
            Understudy::calls(fn() => $provider->remove(Arg::any())),
        );
    }
}
