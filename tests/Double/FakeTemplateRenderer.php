<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\TemplateRendererInterface;

final class FakeTemplateRenderer implements TemplateRendererInterface
{
    public ?string $view = null;

    /**
     * @var array<string, mixed>
     */
    public array $parameters = [];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    #[\Override]
    public function render(string $view, array $parameters = []): ResponseInterface
    {
        $this->view = $view;
        $this->parameters = $parameters;

        return $this->responseFactory->createResponse(200);
    }
}
