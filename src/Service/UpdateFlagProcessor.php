<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Event\FlagChanged;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Http\Status;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;

/**
 * Validates submitted flag input and writes it via the writable provider.
 *
 *  - read-only provider (not a {@see WritableFlagProvider}) -> HTTP 403
 *  - invalid input -> re-render the edit page (HTTP 200) with errors, no write
 *  - valid input -> save, dispatch {@see FlagChanged::saved}, redirect to list
 *
 * @internal
 */
final readonly class UpdateFlagProcessor
{
    public function __construct(
        private FlagProvider $provider,
        private ResponseFactoryInterface $responseFactory,
        private FlagUrls $urls,
        private EditPageRenderer $editPage,
        private ValidatorInterface $validator,
        private FlagFormNormalizer $normalizer,
        private ?CurrentUser $currentUser = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function processExisting(string $name, ServerRequestInterface $request): ResponseInterface
    {
        $existing = $this->provider->getFlags()[$name] ?? null;

        if ($existing === null) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        return $this->process(request: $request, existing: $existing);
    }

    public function processNew(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process(request: $request, existing: null);
    }

    private function process(ServerRequestInterface $request, ?Flag $existing): ResponseInterface
    {
        if (!$this->provider instanceof WritableFlagProvider) {
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        $form = FlagForm::fromParsedBody($request->getParsedBody());

        if (!$form->present) {
            return $this->redirect();
        }

        // On edit existing, the name field is disabled; ignore submitted name and pin the route name.
        if ($existing instanceof \Rasuvaeff\Yii3FeatureFlags\Flag) {
            $form = $this->cloneWithRouteName($form, $existing->name);
        }

        $result = $this->validator->validate($form, $form->getRules());

        if (!$result->isValid()) {
            $messages = $result->getErrorMessages();

            return $this->renderOnError($existing, $form, $messages[0] ?? 'Invalid input');
        }

        try {
            $flag = $this->normalizer->toFlag($form);
        } catch (\InvalidArgumentException $e) {
            return $this->renderOnError($existing, $form, $e->getMessage());
        }

        $this->provider->save($flag);

        $this->eventDispatcher?->dispatch(
            FlagChanged::saved(name: $flag->name, actor: $this->currentUser?->getId()),
        );

        return $this->redirect();
    }

    private function renderOnError(?Flag $existing, FlagForm $form, string $error): ResponseInterface
    {
        return $existing instanceof \Rasuvaeff\Yii3FeatureFlags\Flag
            ? $this->editPage->renderExisting($existing, $form, true, $error)
            : $this->editPage->renderNew($form, true, $error);
    }

    private function redirect(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urls->list());
    }

    private function cloneWithRouteName(FlagForm $form, string $name): FlagForm
    {
        return new FlagForm(
            present: $form->present,
            name: $name,
            enabled: $form->enabled,
            rollout: $form->rollout,
            salt: $form->salt,
            killSwitch: $form->killSwitch,
            environments: $form->environments,
        );
    }
}
