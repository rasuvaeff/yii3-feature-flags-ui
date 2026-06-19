<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private UpdateFlagProcessor $processor,
    ) {}

    public function __invoke(
        #[RouteArgument('name')]
        string $name,
        ServerRequestInterface $request,
    ): ResponseInterface {
        return $this->processor->processExisting(name: $name, request: $request);
    }

    public function new(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processor->processNew(request: $request);
    }
}
