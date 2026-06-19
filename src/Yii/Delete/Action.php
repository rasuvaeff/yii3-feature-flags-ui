<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Delete;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private DeleteFlagProcessor $processor,
    ) {}

    public function __invoke(
        #[RouteArgument('name')]
        string $name,
    ): ResponseInterface {
        return $this->processor->process(name: $name);
    }
}
