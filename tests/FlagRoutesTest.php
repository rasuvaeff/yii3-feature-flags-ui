<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlagsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Edit\Action as EditAction;
use Rasuvaeff\Yii3FeatureFlagsUi\Yii\Update\Action as UpdateAction;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Route;

#[CoversClass(FlagRoutes::class)]
final class FlagRoutesTest extends TestCase
{
    #[Test]
    public function buildsRoutesWithDefaultNamesAndPrefix(): void
    {
        $routes = FlagRoutes::create();

        $this->assertCount(6, $routes);

        $list = $routes[0];
        $createEdit = $routes[1];
        $edit = $routes[2];
        $createStore = $routes[3];
        $update = $routes[4];
        $delete = $routes[5];

        $this->assertSame(FlagRoutes::LIST, $list->getData('name'));
        $this->assertSame('/admin/flags', $list->getData('pattern'));
        $this->assertSame(['GET'], $list->getData('methods'));

        $this->assertSame(FlagRoutes::CREATE, $createEdit->getData('name'));
        $this->assertSame('/admin/flags/new', $createEdit->getData('pattern'));
        $this->assertSame(['GET'], $createEdit->getData('methods'));

        $this->assertSame(FlagRoutes::EDIT, $edit->getData('name'));
        $this->assertSame('/admin/flags/{name}/edit', $edit->getData('pattern'));
        $this->assertSame(['GET'], $edit->getData('methods'));

        $this->assertSame(FlagRoutes::STORE, $createStore->getData('name'));
        $this->assertSame('/admin/flags/new', $createStore->getData('pattern'));
        $this->assertSame(['POST'], $createStore->getData('methods'));

        $this->assertSame(FlagRoutes::UPDATE, $update->getData('name'));
        $this->assertSame('/admin/flags/{name}', $update->getData('pattern'));
        $this->assertSame(['POST'], $update->getData('methods'));

        $this->assertSame(FlagRoutes::DELETE, $delete->getData('name'));
        $this->assertSame('/admin/flags/{name}/delete', $delete->getData('pattern'));
        $this->assertSame(['POST'], $delete->getData('methods'));
    }

    #[Test]
    public function createRoutesTargetNewActionMethods(): void
    {
        $routes = FlagRoutes::create();

        $this->assertSame([EditAction::class, 'new'], $this->lastMiddleware($routes[1]));
        $this->assertSame([UpdateAction::class, 'new'], $this->lastMiddleware($routes[3]));
    }

    #[Test]
    public function getRoutesHaveNoExtraMiddlewaresByDefault(): void
    {
        $getRoutes = [FlagRoutes::create()[0], FlagRoutes::create()[1], FlagRoutes::create()[2]];

        foreach ($getRoutes as $route) {
            $this->assertCount(1, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function postRoutesHaveBodyParserByDefault(): void
    {
        $postRoutes = [FlagRoutes::create()[3], FlagRoutes::create()[4], FlagRoutes::create()[5]];

        foreach ($postRoutes as $route) {
            $middlewares = $route->getData('enabledMiddlewares');
            $this->assertContains(RequestBodyParser::class, $middlewares);
        }
    }

    #[Test]
    public function withBodyParserFalseSkipsBodyParser(): void
    {
        foreach (FlagRoutes::create(withBodyParser: false) as $route) {
            $this->assertCount(1, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
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

        $this->assertContains($create, $storeMiddlewares);
        $this->assertNotContains($update, $storeMiddlewares);
        $this->assertContains($update, $updateMiddlewares);
        $this->assertNotContains($create, $updateMiddlewares);
    }

    #[Test]
    public function appliesCustomPrefixAndNames(): void
    {
        $routes = FlagRoutes::create(
            prefix: '/flags',
            names: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
        );

        $this->assertSame('admin/flags', $routes[0]->getData('name'));
        $this->assertSame('/flags', $routes[0]->getData('pattern'));
        $this->assertSame('admin/flags/edit', $routes[2]->getData('name'));
        $this->assertSame('/flags/{name}/edit', $routes[2]->getData('pattern'));
        $this->assertSame(FlagRoutes::UPDATE, $routes[4]->getData('name'));
    }

    #[Test]
    public function attachesAllMiddlewareToEveryRoute(): void
    {
        $mw = static fn(): string => 'noop';
        $routes = FlagRoutes::create(middlewares: ['all' => [$mw]]);

        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains($mw, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function fromParamsReadsConfigFromParamsArray(): void
    {
        $routes = FlagRoutes::fromParams([
            FlagRoutes::PARAM_KEY => [
                'route_prefix' => '/my-flags',
                'route_names' => ['list' => 'my/flags/list'],
                'body_parser' => false,
            ],
        ]);

        $this->assertSame('/my-flags', $routes[0]->getData('pattern'));
        $this->assertSame('my/flags/list', $routes[0]->getData('name'));
        // body_parser: false — POST routes have only the action
        $this->assertCount(1, $routes[3]->getData('enabledMiddlewares'));
    }

    #[Test]
    public function emptyStringNameFallsBackToDefault(): void
    {
        $routes = FlagRoutes::create(names: ['list' => '']);

        $this->assertSame(FlagRoutes::LIST, $routes[0]->getData('name'));
    }

    #[Test]
    public function fromParamsUsesDefaultsWhenConfigIsEmpty(): void
    {
        $routes = FlagRoutes::fromParams([]);

        $this->assertSame('/admin/flags', $routes[0]->getData('pattern'));
        $this->assertSame(FlagRoutes::LIST, $routes[0]->getData('name'));
    }

    private function lastMiddleware(Route $route): mixed
    {
        $middlewares = $route->getData('enabledMiddlewares');
        $last = array_key_last($middlewares);

        if ($last === null) {
            self::fail('Route has no middleware action');
        }

        return $middlewares[$last];
    }
}
