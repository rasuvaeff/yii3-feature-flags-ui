<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Renderer;

use Psr\Http\Message\ResponseInterface;

/**
 * Renders a package view template into an HTTP response.
 *
 * The seam keeps actions independent of the concrete (final) view renderer,
 * so they stay unit-testable and the view path is configured in one place.
 *
 * @api
 */
interface TemplateRendererInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function render(string $view, array $parameters = []): ResponseInterface;
}
