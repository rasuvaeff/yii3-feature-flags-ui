<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;

/**
 * Renders the edit form for an existing flag (GET /{name}/edit) and the create
 * form (GET /new). Returns 404 when the route targets a flag name the provider
 * does not know.
 *
 * @internal
 */
final readonly class EditFlagResponder
{
    public function __construct(
        private EditPageRenderer $editPage,
        private ResponseFactoryInterface $responseFactory,
        private FlagProvider $provider,
    ) {}

    public function respondExisting(string $name): ResponseInterface
    {
        $flags = $this->provider->getFlags();
        $flag = $flags[$name] ?? null;

        if ($flag === null) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        $isWritable = $this->provider instanceof WritableFlagProvider;
        $form = $this->formFromFlag($flag);

        return $this->editPage->renderExisting($flag, $form, $isWritable);
    }

    public function respondNew(): ResponseInterface
    {
        $isWritable = $this->provider instanceof WritableFlagProvider;

        return $this->editPage->renderNew(new FlagForm(), $isWritable);
    }

    private function formFromFlag(\Rasuvaeff\Yii3FeatureFlags\Flag $flag): FlagForm
    {
        return new FlagForm(
            present: false,
            name: $flag->name,
            enabled: $flag->enabled,
            rollout: (string) $flag->rollout,
            salt: $flag->salt,
            killSwitch: $flag->killSwitch,
            environments: $flag->environments === [] ? '' : json_encode($flag->environments, JSON_THROW_ON_ERROR),
        );
    }
}
