<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\View\FlagPresenter;

/**
 * Builds the flag list response: read all flags from the provider, build
 * presenters sorted by name, render the list view with a server-side GridView.
 *
 * @internal
 */
final readonly class ListFlagsResponder
{
    public function __construct(
        private TemplateRendererInterface $renderer,
        private FlagProvider $provider,
        private FlagUrls $urls,
        private FlagsGridFactory $gridFactory,
    ) {}

    public function respond(): ResponseInterface
    {
        $presenters = $this->buildPresenters();
        $isWritable = $this->provider instanceof WritableFlagProvider;

        return $this->renderer->render('list', [
            'flags' => $presenters,
            'isWritable' => $isWritable,
            'createUrl' => $this->urls->create(),
            'gridHtml' => $this->gridFactory->render($presenters, $isWritable),
        ]);
    }

    /**
     * @return list<FlagPresenter>
     */
    private function buildPresenters(): array
    {
        $presenters = [];
        $isWritable = $this->provider instanceof WritableFlagProvider;

        foreach ($this->provider->getFlags() as $flag) {
            $presenters[] = new FlagPresenter(
                flag: $flag,
                isWritable: $isWritable,
                editUrl: $this->urls->edit($flag->name),
                deleteUrl: $this->urls->delete($flag->name),
                environments: $flag->environments,
            );
        }

        usort($presenters, static fn(FlagPresenter $a, FlagPresenter $b): int => strcmp($a->name, $b->name));

        return $presenters;
    }
}
