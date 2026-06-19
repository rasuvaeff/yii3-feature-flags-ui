<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private EditFlagResponder $responder,
    ) {}

    public function __invoke(
        #[RouteArgument('name')]
        string $name,
    ): ResponseInterface {
        return $this->responder->respondExisting(name: $name);
    }

    public function new(): ResponseInterface
    {
        return $this->responder->respondNew();
    }
}
