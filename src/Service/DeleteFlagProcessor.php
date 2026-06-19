<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Yiisoft\User\CurrentUser;

/**
 * Deletes a flag via the writable provider.
 *
 *  - read-only provider (not a {@see WritableFlagProvider}) -> HTTP 403
 *  - unknown name -> HTTP 404
 *  - success -> dispatch {@see FlagChanged::deleted}, redirect to list
 *
 * @internal
 */
final readonly class DeleteFlagProcessor
{
    public function __construct(
        private FlagProvider $provider,
        private ResponseFactoryInterface $responseFactory,
        private FlagUrls $urls,
        private ?CurrentUser $currentUser = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function process(string $name): ResponseInterface
    {
        if (!$this->provider instanceof WritableFlagProvider) {
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        $flags = $this->provider->getFlags();

        if (!isset($flags[$name])) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        $this->provider->remove($name);

        $this->eventDispatcher?->dispatch(
            FlagChanged::deleted(name: $name, actor: $this->currentUser?->getId()),
        );

        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urls->list());
    }
}
