<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Yii\List;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private ListFlagsResponder $responder,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responder->respond();
    }
}
