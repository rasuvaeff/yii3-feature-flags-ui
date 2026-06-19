<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Renderer;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * @api
 */
final readonly class ViewTemplateRenderer implements TemplateRendererInterface
{
    private string $viewPath;

    private ?string $layout;

    /**
     * @param array<string, string> $views
     */
    public function __construct(
        private WebViewRenderer $renderer,
        ?string $viewPath = null,
        ?string $layout = null,
        private array $views = [],
    ) {
        $this->viewPath = $viewPath ?? \dirname(__DIR__, 2) . '/resources/views';
        $this->layout = $layout ?? $this->viewPath . '/_layout-standalone.php';
    }

    #[\Override]
    public function render(string $view, array $parameters = []): ResponseInterface
    {
        $templatePath = $this->resolveTemplatePath($view);

        return $this->renderer
            ->withViewPath(\dirname($templatePath))
            ->withLayout($this->layout)
            ->render(\pathinfo($templatePath, \PATHINFO_FILENAME), $parameters);
    }

    private function resolveTemplatePath(string $view): string
    {
        $configuredView = $this->views[$view] ?? null;
        if (!\is_string($configuredView) || $configuredView === '') {
            return $this->viewPath . '/' . $view . '.php';
        }

        if (str_starts_with($configuredView, '/')) {
            return $configuredView;
        }

        return $this->viewPath . '/' . ltrim($configuredView, '/');
    }
}
