<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests;

use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit\Action as EditAction;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update\Action as UpdateAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Route;

#[Test]
#[Covers(FlagRoutes::class)]
final class FlagRoutesTest
{
    public function buildsRoutesWithDefaultNamesAndPrefix(): void
    {
        $routes = FlagRoutes::create();

        Assert::count($routes, 6);

        $list = $routes[0];
        $createEdit = $routes[1];
        $edit = $routes[2];
        $createStore = $routes[3];
        $update = $routes[4];
        $delete = $routes[5];

        Assert::same($list->getData('name'), FlagRoutes::LIST);
        Assert::same($list->getData('pattern'), '/admin/flags');
        Assert::same($list->getData('methods'), ['GET']);

        Assert::same($createEdit->getData('name'), FlagRoutes::CREATE);
        Assert::same($createEdit->getData('pattern'), '/admin/flags/new');
        Assert::same($createEdit->getData('methods'), ['GET']);

        Assert::same($edit->getData('name'), FlagRoutes::EDIT);
        Assert::same($edit->getData('pattern'), '/admin/flags/{name}/edit');
        Assert::same($edit->getData('methods'), ['GET']);

        Assert::same($createStore->getData('name'), FlagRoutes::STORE);
        Assert::same($createStore->getData('pattern'), '/admin/flags/new');
        Assert::same($createStore->getData('methods'), ['POST']);

        Assert::same($update->getData('name'), FlagRoutes::UPDATE);
        Assert::same($update->getData('pattern'), '/admin/flags/{name}');
        Assert::same($update->getData('methods'), ['POST']);

        Assert::same($delete->getData('name'), FlagRoutes::DELETE);
        Assert::same($delete->getData('pattern'), '/admin/flags/{name}/delete');
        Assert::same($delete->getData('methods'), ['POST']);
    }

    public function createRoutesTargetNewActionMethods(): void
    {
        $routes = FlagRoutes::create();

        Assert::same(self::lastMiddleware($routes[1]), [EditAction::class, 'new']);
        Assert::same(self::lastMiddleware($routes[3]), [UpdateAction::class, 'new']);
    }

    public function getRoutesHaveNoExtraMiddlewaresByDefault(): void
    {
        $getRoutes = [FlagRoutes::create()[0], FlagRoutes::create()[1], FlagRoutes::create()[2]];

        foreach ($getRoutes as $route) {
            Assert::count($route->getData('enabledMiddlewares'), 1);
        }
    }

    public function postRoutesHaveBodyParserByDefault(): void
    {
        $postRoutes = [FlagRoutes::create()[3], FlagRoutes::create()[4], FlagRoutes::create()[5]];

        foreach ($postRoutes as $route) {
            $middlewares = $route->getData('enabledMiddlewares');
            Assert::contains($middlewares, RequestBodyParser::class);
        }
    }

    public function withBodyParserFalseSkipsBodyParser(): void
    {
        foreach (FlagRoutes::create(withBodyParser: false) as $route) {
            Assert::count($route->getData('enabledMiddlewares'), 1);
        }
    }

    public function createAndStoreMiddlewaresAreAppliedToTheirOwnPostRoutes(): void
    {
        $create = static fn(): string => 'create';
        $update = static fn(): string => 'update';
        $routes = FlagRoutes::create(
            middlewares: [
                'create' => [$create],
                'update' => [$update],
            ],
        );

        $storeMiddlewares = $routes[3]->getData('enabledMiddlewares');
        $updateMiddlewares = $routes[4]->getData('enabledMiddlewares');

        Assert::contains($storeMiddlewares, $create);
        Assert::false(in_array($update, $storeMiddlewares, true));
        Assert::contains($updateMiddlewares, $update);
        Assert::false(in_array($create, $updateMiddlewares, true));
    }

    public function appliesCustomPrefixAndNames(): void
    {
        $routes = FlagRoutes::create(
            prefix: '/flags',
            names: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
        );

        Assert::same($routes[0]->getData('name'), 'admin/flags');
        Assert::same($routes[0]->getData('pattern'), '/flags');
        Assert::same($routes[2]->getData('name'), 'admin/flags/edit');
        Assert::same($routes[2]->getData('pattern'), '/flags/{name}/edit');
        Assert::same($routes[4]->getData('name'), FlagRoutes::UPDATE);
    }

    public function attachesAllMiddlewareToEveryRoute(): void
    {
        $mw = static fn(): string => 'noop';
        $routes = FlagRoutes::create(middlewares: ['all' => [$mw]]);

        foreach ($routes as $route) {
            Assert::instanceOf($route, Route::class);
            Assert::contains($route->getData('enabledMiddlewares'), $mw);
        }
    }

    public function eachRouteReceivesItsOwnSpecificMiddleware(): void
    {
        $list = static fn(): string => 'mw-list';
        $create = static fn(): string => 'mw-create';
        $edit = static fn(): string => 'mw-edit';
        $store = static fn(): string => 'mw-store';
        $update = static fn(): string => 'mw-update';
        $delete = static fn(): string => 'mw-delete';

        $routes = FlagRoutes::create(middlewares: [
            'list' => [$list],
            'create' => [$create],
            'edit' => [$edit],
            'store' => [$store],
            'update' => [$update],
            'delete' => [$delete],
        ]);

        Assert::contains($routes[0]->getData('enabledMiddlewares'), $list);
        Assert::contains($routes[1]->getData('enabledMiddlewares'), $create);
        Assert::contains($routes[2]->getData('enabledMiddlewares'), $edit);

        $storeMiddlewares = $routes[3]->getData('enabledMiddlewares');
        Assert::contains($storeMiddlewares, $store);
        Assert::false(in_array($create, $storeMiddlewares, true));

        Assert::contains($routes[4]->getData('enabledMiddlewares'), $update);
        Assert::contains($routes[5]->getData('enabledMiddlewares'), $delete);
    }

    public function storeRouteFallsBackToCreateMiddlewareWhenStoreAbsent(): void
    {
        $create = static fn(): string => 'mw-create';

        $routes = FlagRoutes::create(middlewares: ['create' => [$create]]);

        Assert::contains($routes[3]->getData('enabledMiddlewares'), $create);
    }

    public function acceptsArrayShapedMiddleware(): void
    {
        $arrayMiddleware = ['SomeMiddleware', 'process'];

        $routes = FlagRoutes::create(middlewares: ['list' => [$arrayMiddleware]]);

        Assert::contains($routes[0]->getData('enabledMiddlewares'), $arrayMiddleware);
    }

    public function fromParamsReadsConfigFromParamsArray(): void
    {
        $routes = FlagRoutes::fromParams([
            FlagRoutes::PARAM_KEY => [
                'route_prefix' => '/my-flags',
                'route_names' => ['list' => 'my/flags/list'],
                'body_parser' => false,
            ],
        ]);

        Assert::same($routes[0]->getData('pattern'), '/my-flags');
        Assert::same($routes[0]->getData('name'), 'my/flags/list');
        Assert::count($routes[3]->getData('enabledMiddlewares'), 1);
    }

    public function emptyStringNameFallsBackToDefault(): void
    {
        $routes = FlagRoutes::create(names: ['list' => '']);

        Assert::same($routes[0]->getData('name'), FlagRoutes::LIST);
    }

    public function fromParamsUsesDefaultsWhenConfigIsEmpty(): void
    {
        $routes = FlagRoutes::fromParams([]);

        Assert::same($routes[0]->getData('pattern'), '/admin/flags');
        Assert::same($routes[0]->getData('name'), FlagRoutes::LIST);
    }

    private static function lastMiddleware(Route $route): mixed
    {
        $middlewares = $route->getData('enabledMiddlewares');
        $last = array_key_last($middlewares);

        if ($last === null) {
            Assert::fail('Route has no middleware action');
        }

        return $middlewares[$last];
    }
}
