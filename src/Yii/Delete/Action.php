<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private DeleteFlagProcessor $processor,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->processor->process(name: (string) $this->currentRoute->getArgument('name'));
    }
}
