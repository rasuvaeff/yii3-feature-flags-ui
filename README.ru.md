# rasuvaeff/yii3-feature-flags-ui

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Build](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-feature-flags-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

Admin UI-панель для управления feature-флагами Yii3.

> Используете AI-ассистента для написания кода? В [llms.txt](llms.txt) — компактный
> API-справочник, которым можно поделиться с моделью.

Drop-in admin-панель для [`rasuvaeff/yii3-feature-flags`](https://github.com/rasuvaeff/yii3-feature-flags):
список флагов в сортируемой сетке (с kill switch и disabled-бейджами), создание и
редактирование (name, enabled, rollout, salt, kill switch, environments), удаление,
а также эмитирование `FlagChanged`-событий для audit trail / инвалидации кэша.
Read-only-провайдеры автоматически получают отключённые контролы.

## Требования

- PHP 8.3+
- `rasuvaeff/yii3-feature-flags` ^1.0 - `Flag`, `FlagProvider`, `WritableFlagProvider`
- Writable-бэкенд провайдера (обычно `rasuvaeff/yii3-feature-flags-db` ^1.0), привязанный к `FlagProvider` и `WritableFlagProvider`
- `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
- `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
- Конкретная реализация router'а (например `yiisoft/router-fastroute`) — предоставляется приложением
- Bootstrap 5 CSS, загружаемый хост-приложением (views используют классы Bootstrap, без inline-стилей)

Сетка списка рендерится серверсайдно из DI-контейнера приложения
(`FlagsGridFactory`), поэтому хосту **не** нужно загружать `WidgetFactory`.

## Установка

```bash
composer require rasuvaeff/yii3-feature-flags-ui
```

## Использование

Пакет поставляет Yii3 config-plugin wiring (`di`, `params`). Добавьте свои params
в merge-chain:

```php
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;

return [
    FlagRoutes::PARAM_KEY => [
        'route_prefix' => '/admin/flags',
        'layout' => null,
        'views' => [
            'list' => '/abs/path/to/flags-list.php',
            'edit' => '/abs/path/to/flags-edit.php',
        ],
        'middlewares' => [
            'all' => [AuthMiddleware::class],
        ],
        // RequestBodyParser is added automatically to POST routes (store, update, delete).
        // Set 'body_parser' => false if your pipeline already applies it globally.
    ],
];
```

`layout` управляет общей обёрткой. `views.list` и `views.edit` переопределяют
только соответствующие шаблоны; они не заменяют layout.

Привяжите контракты флагов к вашему провайдеру. С
`rasuvaeff/yii3-feature-flags-db` ^1.0 это происходит автоматически — его
config-plugin биндит `WritableFlagProvider` и `FlagProvider` на один и тот же
`DbFlagProvider`. Для кастомного бэкенда забиндите их сами:

```php
use Rasuvaeff\Yii3FeatureFlags\{FlagProvider, WritableFlagProvider};
use Rasuvaeff\Yii3FeatureFlagsDb\DbFlagProvider;

return [
    FlagProvider::class => DbFlagProvider::class,
    WritableFlagProvider::class => Reference::to(FlagProvider::class),
];
```

## Маршруты

| Метод | Путь | Action | Имя по умолчанию |
|---|---|---|---|
| GET | `{prefix}` | `Yii\List\Action` | `flags/list` |
| GET | `{prefix}/new` | `Yii\Edit\Action::new()` | `flags/create` |
| GET | `{prefix}/{name}/edit` | `Yii\Edit\Action` | `flags/edit` |
| POST | `{prefix}/new` | `Yii\Update\Action::new()` | `flags/store` |
| POST | `{prefix}/{name}` | `Yii\Update\Action` | `flags/update` |
| POST | `{prefix}/{name}/delete` | `Yii\Delete\Action` | `flags/delete` |

`middlewares.{all,list,edit,create,store,update,delete}` — добавляйте
middleware'ы на каждый слот без форкования маршрутов. `RequestBodyParser`
добавляется автоматически к POST-маршрутам (store, update, delete); установите
`'body_parser' => false` в params, чтобы отказаться.

URL'ы и редиректы генерируются через router (`UrlGeneratorInterface`) по имени
маршрута; ссылки остаются корректными под любым префиксом или поддоменом.
Переопределите `route_names` в params, если ваше приложение использует другую
схему имён.

### Flat-route wiring

Подключите bundled `config/routes.php` явно в `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-feature-flags-ui/config/routes.php',
```

Префикс маршрута, имена и middleware'ы читаются из params
(`FlagRoutes::PARAM_KEY`).

### Групповая admin-панель

Внутри `Group` (типичный подход для админ-области с общим префиксом):

```php
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...FlagRoutes::fromParams($params),
);
```

`fromParams()` читает префикс, имена, middleware'ы и opt-out body-parser'а из
`$params[FlagRoutes::PARAM_KEY]`, поэтому регистрация маршрутов и URL-генерация
через `FlagUrls` всегда синхронизированы.

Для полного контроля над именами используйте `create()` напрямую и добавьте
соответствующие `route_names` в params:

```php
FlagRoutes::create(
    prefix: '/flags',
    names: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```

## Авторизация

Пакет не осуществляет внутренний контроль доступа. Защищайте маршруты через
`middlewares.all` (или per-route ключи). Пакет предоставляет инжекцию
`CurrentUser` только для audit-событий.

## Публичный API

| Класс | Описание |
|---|---|
| `FlagRoutes` | Строит 6 маршрутов; `fromParams($params)` для групповых панелей, `create()` для полного контроля |
| `Yii\List\Action` | GET-сетка всех флагов с KILLED/OFF-бейджами |
| `Yii\Edit\Action` | GET-форма редактирования существующего флага; `::new()` для формы создания |
| `Yii\Update\Action` | POST validate + save; `::new()` для создания; re-render при невалидном |
| `Yii\Delete\Action` | POST удаление флага → редирект на список |
| `Form\FlagForm` | Отправляемые данные формы (name, enabled, rollout, salt, killSwitch, environments) |
| `Validation\FlagFormNormalizer` | Приводит отвалидированную форму к `Flag` |
| `Renderer\TemplateRendererInterface` | Rendering-seam (тестируемые actions) |
| `Renderer\ViewTemplateRenderer` | Дефолтный renderer над `WebViewRenderer` |
| `Event\FlagChanged` | PSR-14 событие после save/delete (name, operation, actor) |

## Read-only провайдеры

Если ваш `FlagProvider` не реализует `WritableFlagProvider`:

- `config/routes.php` всё равно регистрирует все маршруты; runtime-проверки
  `instanceof` возвращают 403 на POST.
- List-view показывает бейдж «Read-only provider», а кнопка создания скрыта.
- Edit-view отключает все поля и показывает предупреждение.

Это позволяет `ConfigFlagProvider` (флаги только из конфига) просматривать в UI
без поддержки записи.

## Безопасность

| Аспект | Поведение |
|---|---|
| Read-only провайдеры | `Update`/`Delete` отклоняются с HTTP 403 |
| Неизвестное имя флага | `Edit` возвращает 404, `Update`/`Delete` возвращают 404 |
| Невалидный ввод | Ре-рендер страницы редактирования с HTTP 200, без записи |
| Подмена имени флага | При редактировании существующего отправляемое `name` игнорируется; имя из маршрута зафиксировано |
| Предупреждение kill switch | Форма редактирования всегда рендерит предупреждение; пользователи не могут отключить его |
| CSRF | Обеспечивается middleware'ом приложения; форма эмитит hidden-поле `_csrf`, когда присутствует request-атрибут `csrf_token` |
| Вывод | Все значения проходят через `Yiisoft\Html\Html::encode()` / Html-виджеты / GridView-encoding |

## Кастомизация views

Переопределите `views.list` и/или `views.edit` в params абсолютными путями к
своим шаблонам. Шаблоны получают те же переменные, что и bundled — см.
`resources/views/`.

Форма редактирования использует имена инпутов в скоупе `Flag[...]` (например
`Flag[name]`, `Flag[enabled]`). Кастомные edit-шаблоны должны сохранять этот
скоуп, чтобы работал `FlagForm::fromParsedBody()`.

**Flash-сообщения** не встроены — пакет не знает о сессии хост-приложения.
Подпишитесь на `FlagChanged` в своём приложении для flash-уведомлений,
инвалидации кэша или audit-записей.

## Зачем существует `FlagChanged`

Пакет эмитит `FlagChanged` после save/delete, чтобы хост-приложение могло
реагировать без жёсткой связи с UI-actions. Типичные применения — инвалидация
кэша, audit-логирование, метрические счётчики и зависимая реконфигурация. Поле
`actor` несёт ID текущего пользователя; `null` для гостей.
