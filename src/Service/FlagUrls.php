<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Service;

use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Generates flag URLs via the router (named routes), so links and redirects
 * stay correct regardless of the mount prefix / subdomain.
 *
 * @internal
 */
final readonly class FlagUrls
{
    /**
     * @param array{list?: string, edit?: string, create?: string, update?: string, delete?: string} $routeNames
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private array $routeNames = [],
    ) {}

    public function list(): string
    {
        return $this->urlGenerator->generate($this->name('list', FlagRoutes::LIST));
    }

    public function edit(string $name): string
    {
        return $this->urlGenerator->generate($this->name('edit', FlagRoutes::EDIT), ['name' => $name]);
    }

    public function create(): string
    {
        return $this->urlGenerator->generate($this->name('create', FlagRoutes::CREATE));
    }

    public function update(string $name): string
    {
        return $this->urlGenerator->generate($this->name('update', FlagRoutes::UPDATE), ['name' => $name]);
    }

    public function delete(string $name): string
    {
        return $this->urlGenerator->generate($this->name('delete', FlagRoutes::DELETE), ['name' => $name]);
    }

    private function name(string $key, string $default): string
    {
        $name = $this->routeNames[$key] ?? null;

        return \is_string($name) && $name !== '' ? $name : $default;
    }
}
