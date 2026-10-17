<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\EditFlagResponder;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private EditFlagResponder $responder,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responder->respondExisting(name: (string) $this->currentRoute->getArgument('name'));
    }

    public function new(): ResponseInterface
    {
        return $this->responder->respondNew();
    }
}
