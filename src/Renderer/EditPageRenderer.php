<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Renderer;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlagsUi\Form\FlagForm;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;

/**
 * Renders the flag edit/create page. Shared by the GET edit action and the
 * POST update action (which re-renders it with a validation error).
 *
 * @internal
 */
final readonly class EditPageRenderer
{
    public function __construct(
        private TemplateRendererInterface $renderer,
        private FlagUrls $urls,
    ) {}

    public function renderNew(FlagForm $form, bool $isWritable, ?string $error = null): ResponseInterface
    {
        return $this->renderer->render('edit', [
            'form' => $form,
            'flag' => null,
            'isNew' => true,
            'isWritable' => $isWritable,
            'updateUrl' => $this->urls->create(),
            'deleteUrl' => null,
            'listUrl' => $this->urls->list(),
            'error' => $error,
        ]);
    }

    public function renderExisting(Flag $flag, FlagForm $form, bool $isWritable, ?string $error = null): ResponseInterface
    {
        return $this->renderer->render('edit', [
            'form' => $form,
            'flag' => $flag,
            'isNew' => false,
            'isWritable' => $isWritable,
            'updateUrl' => $this->urls->update($flag->name),
            'deleteUrl' => $this->urls->delete($flag->name),
            'listUrl' => $this->urls->list(),
            'error' => $error,
        ]);
    }
}
