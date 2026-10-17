<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private UpdateFlagProcessor $processor,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processor->processExisting(
            name: (string) $this->currentRoute->getArgument('name'),
            request: $request,
        );
    }

    public function new(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processor->processNew(request: $request);
    }
}
