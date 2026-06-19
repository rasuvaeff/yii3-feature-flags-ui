<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests\Double;

use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\DataView\Filter\Factory\FilterFactoryInterface;
use Yiisoft\Yii\DataView\Filter\Factory\LikeFilterFactory;
use Yiisoft\Yii\DataView\ValuePresenter\SimpleValuePresenter;
use Yiisoft\Yii\DataView\ValuePresenter\ValuePresenterInterface;

/**
 * Minimal PSR-11 container used in tests to bootstrap the GridView widget,
 * which autowires its column renderers through yiisoft/injector.
 */
final class TestContainer implements ContainerInterface
{
    private ?Injector $injector = null;

    /** @var array<string, mixed> */
    private array $explicit;

    public function __construct()
    {
        $this->explicit = [
            ContainerInterface::class => $this,
            ValidatorInterface::class => new Validator(),
            ValuePresenterInterface::class => new SimpleValuePresenter(),
            FilterFactoryInterface::class => new LikeFilterFactory(),
        ];
    }

    #[\Override]
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->explicit)) {
            return $this->explicit[$id];
        }

        \assert(class_exists($id) || interface_exists($id));

        return ($this->injector ??= new Injector($this))->make($id);
    }

    #[\Override]
    public function has(string $id): bool
    {
        if (\array_key_exists($id, $this->explicit)) {
            return true;
        }

        if (interface_exists($id)) {
            return false;
        }

        if (class_exists($id)) {
            return (new \ReflectionClass($id))->isInstantiable();
        }

        return false;
    }
}
