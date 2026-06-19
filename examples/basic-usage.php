<?php

declare(strict_types=1);

/**
 * Runnable demo of the yii3-feature-flags-ui services driven over PSR-7, using
 * an in-memory writable provider instead of a database. Run it with:
 *
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic-usage.php
 *
 * It demonstrates: list response, valid update of an existing flag, create of a
 * new flag via `processNew`, rejected invalid input (HTTP 200 re-render), and
 * delete.
 */

use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Renderer\ViewTemplateRenderer;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\DeleteFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagsGridFactory;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\FlagUrls;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\ListFlagsResponder;
use Rasuvaeff\Yii3FeatureFlagsUi\Service\UpdateFlagProcessor;
use Rasuvaeff\Yii3FeatureFlagsUi\Validation\FlagFormNormalizer;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Injector\Injector;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\DataView\Filter\Factory\FilterFactoryInterface;
use Yiisoft\Yii\DataView\Filter\Factory\LikeFilterFactory;
use Yiisoft\Yii\DataView\ValuePresenter\SimpleValuePresenter;
use Yiisoft\Yii\DataView\ValuePresenter\ValuePresenterInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Minimal PSR-11 container used to bootstrap the GridView widget (see
 * FlagsGridFactory). The widget autowires its column renderers through
 * yiisoft/injector; the framework interfaces it needs are pinned explicitly.
 */
$container = new class implements \Psr\Container\ContainerInterface {
    private ?Injector $injector = null;

    /** @var array<string, mixed> */
    private array $explicit;

    public function __construct()
    {
        $this->explicit = [
            \Psr\Container\ContainerInterface::class => $this,
            ValidatorInterface::class => new Validator(),
            ValuePresenterInterface::class => new SimpleValuePresenter(),
            FilterFactoryInterface::class => new LikeFilterFactory(),
        ];
    }

    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->explicit)) {
            return $this->explicit[$id];
        }

        \assert(class_exists($id) || interface_exists($id));

        return ($this->injector ??= new Injector($this))->make($id);
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->explicit) || class_exists($id);
    }
};

$provider = new class implements WritableFlagProvider {
    /** @var array<string, Flag> */
    public array $flags;

    public function __construct()
    {
        $this->flags = [
            'checkout.v2' => new Flag(name: 'checkout.v2', enabled: true, rollout: 100),
            'billing.maintenance' => new Flag(name: 'billing.maintenance', enabled: true, killSwitch: true),
        ];
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function save(Flag $flag): void
    {
        $this->flags[$flag->name] = $flag;
    }

    public function remove(string $name): void
    {
        unset($this->flags[$name]);
    }
};

$psr17 = new Psr17Factory();
$renderer = new ViewTemplateRenderer(
    new WebViewRenderer(
        responseFactory: $psr17,
        streamFactory: $psr17,
        aliases: new Aliases(),
        view: new WebView(),
    ),
);

$prefix = '/admin/flags';

$urlGenerator = new class ($prefix) implements UrlGeneratorInterface {
    public function __construct(private readonly string $prefix) {}

    public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
    {
        $flagName = (string) ($arguments['name'] ?? '');

        return match ($name) {
            FlagRoutes::CREATE => $this->prefix . '/new',
            FlagRoutes::EDIT => $this->prefix . '/' . rawurlencode($flagName) . '/edit',
            FlagRoutes::UPDATE => $this->prefix . '/' . rawurlencode($flagName),
            FlagRoutes::DELETE => $this->prefix . '/' . rawurlencode($flagName) . '/delete',
            default => $this->prefix,
        };
    }

    public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
    {
        return $this->generate($name, $arguments);
    }

    public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
    {
        return $this->prefix;
    }

    public function getUriPrefix(): string
    {
        return '';
    }

    public function setUriPrefix(string $name): void {}

    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
};

$urls = new FlagUrls($urlGenerator);
$editPage = new EditPageRenderer($renderer, $urls);
$list = new ListFlagsResponder($renderer, $provider, $urls, new FlagsGridFactory($container));
$update = new UpdateFlagProcessor($provider, $psr17, $urls, $editPage, new Validator(), new FlagFormNormalizer());
$delete = new DeleteFlagProcessor($provider, $psr17, $urls);

$post = static fn(array $body) => $psr17
    ->createServerRequest('POST', $prefix)
    ->withParsedBody($body);

echo 'LIST: HTTP ' . $list->respond()->getStatusCode() . "\n";

$resp = $update->processExisting(
    'checkout.v2',
    $post(['Flag' => ['name' => 'checkout.v2', 'enabled' => '1', 'rollout' => '75', 'salt' => 'salt', 'environments' => '["prod"]']]),
);
echo 'UPDATE existing: HTTP ' . $resp->getStatusCode() . ' => rollout=' . $provider->flags['checkout.v2']->rollout . ', environments=' . json_encode($provider->flags['checkout.v2']->environments) . "\n";

$resp = $update->processNew(
    $post(['Flag' => ['name' => 'feature.fresh', 'enabled' => '1', 'rollout' => '50']]),
);
echo 'CREATE new: HTTP ' . $resp->getStatusCode() . ' => flags count=' . count($provider->flags) . "\n";

$resp = $update->processExisting(
    'checkout.v2',
    $post(['Flag' => ['name' => 'checkout.v2', 'enabled' => '1', 'rollout' => 'abc']]),
);
echo 'UPDATE invalid rollout: HTTP ' . $resp->getStatusCode() . ' (rejected, rollout unchanged: ' . $provider->flags['checkout.v2']->rollout . ")\n";

$resp = $delete->process('billing.maintenance');
echo 'DELETE: HTTP ' . $resp->getStatusCode() . ' => flags count=' . count($provider->flags) . "\n";
