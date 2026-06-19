<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\ViewTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete\Action as YiiDeleteAction;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit\Action as YiiEditAction;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\List\Action as YiiListAction;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update\Action as YiiUpdateAction;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/** @var array $params */

$uiConfig = $params[FlagRoutes::PARAM_KEY] ?? [];
$views = $uiConfig['views'] ?? [];
$layout = $uiConfig['layout'] ?? null;
$routeNames = \is_array($uiConfig['route_names'] ?? null) ? $uiConfig['route_names'] : [];

return [
    TemplateRendererInterface::class => static fn (WebViewRenderer $renderer): ViewTemplateRenderer => new ViewTemplateRenderer(
        renderer: $renderer,
        layout: is_string($layout) ? $layout : null,
        views: is_array($views) ? $views : [],
    ),

    FlagFormNormalizer::class => FlagFormNormalizer::class,

    FlagUrls::class => static fn (UrlGeneratorInterface $urlGenerator): FlagUrls => new FlagUrls(
        urlGenerator: $urlGenerator,
        routeNames: $routeNames,
    ),

    FlagsGridFactory::class => static fn (ContainerInterface $container): FlagsGridFactory => new FlagsGridFactory(
        container: $container,
    ),

    EditPageRenderer::class => static fn (
        TemplateRendererInterface $renderer,
        FlagUrls $urls,
    ): EditPageRenderer => new EditPageRenderer(
        renderer: $renderer,
        urls: $urls,
    ),

    ListFlagsResponder::class => static fn (
        TemplateRendererInterface $renderer,
        FlagProvider $provider,
        FlagUrls $urls,
        FlagsGridFactory $gridFactory,
    ): ListFlagsResponder => new ListFlagsResponder(
        renderer: $renderer,
        provider: $provider,
        urls: $urls,
        gridFactory: $gridFactory,
    ),

    EditFlagResponder::class => static fn (
        EditPageRenderer $editPage,
        ResponseFactoryInterface $responseFactory,
        FlagProvider $provider,
    ): EditFlagResponder => new EditFlagResponder(
        editPage: $editPage,
        responseFactory: $responseFactory,
        provider: $provider,
    ),

    UpdateFlagProcessor::class => static fn (
        FlagProvider $provider,
        ResponseFactoryInterface $responseFactory,
        FlagUrls $urls,
        EditPageRenderer $editPage,
        ValidatorInterface $validator,
        FlagFormNormalizer $normalizer,
        CurrentUser $currentUser,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): UpdateFlagProcessor => new UpdateFlagProcessor(
        provider: $provider,
        responseFactory: $responseFactory,
        urls: $urls,
        editPage: $editPage,
        validator: $validator,
        normalizer: $normalizer,
        currentUser: $currentUser,
        eventDispatcher: $eventDispatcher,
    ),

    DeleteFlagProcessor::class => static fn (
        FlagProvider $provider,
        ResponseFactoryInterface $responseFactory,
        FlagUrls $urls,
        CurrentUser $currentUser,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): DeleteFlagProcessor => new DeleteFlagProcessor(
        provider: $provider,
        responseFactory: $responseFactory,
        urls: $urls,
        currentUser: $currentUser,
        eventDispatcher: $eventDispatcher,
    ),

    YiiListAction::class => YiiListAction::class,
    YiiEditAction::class => YiiEditAction::class,
    YiiUpdateAction::class => YiiUpdateAction::class,
    YiiDeleteAction::class => YiiDeleteAction::class,
];

