# rasuvaeff/yii3-feature-flags-ui

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Build](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-feature-flags-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)

Admin UI panel for managing Yii3 feature flags.

> Using an AI coding assistant? [llms.txt](llms.txt) contains a compact API reference you can share with the model.

A drop-in admin panel for [`rasuvaeff/yii3-feature-flags`](https://github.com/rasuvaeff/yii3-feature-flags):
list flags in a sortable grid (with kill switch and disabled badges), create
and edit them (name, enabled, rollout, salt, kill switch, environments), delete
them, and emit `FlagChanged` events for audit trail / cache invalidation. Read-only
providers automatically get disabled controls.

## Requirements

- PHP 8.3+
- `rasuvaeff/yii3-feature-flags` ^1.0 - `Flag`, `FlagProvider`, `WritableFlagProvider`
- A writable provider backend (usually `rasuvaeff/yii3-feature-flags-db` ^1.0) bound to `FlagProvider` and `WritableFlagProvider`
- `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
- `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
- A concrete router implementation (e.g. `yiisoft/router-fastroute`) - provided by your application
- Bootstrap 5 CSS loaded by the host application (views use Bootstrap classes, no inline styles)

The list grid is rendered server-side from the application DI container
(`FlagsGridFactory`), so the host does **not** need to bootstrap `WidgetFactory`.

## Installation

```bash
composer require rasuvaeff/yii3-feature-flags-ui
```

## Usage

The package ships Yii3 config-plugin wiring (`di`, `params`). Add your params
to the merge chain:

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

`layout` controls the shared wrapper. `views.list` and `views.edit` override only the corresponding templates; they do not replace the layout.

Bind the flag contracts to your provider. With `rasuvaeff/yii3-feature-flags-db` ^1.0
this is automatic — its config-plugin binds `WritableFlagProvider` and `FlagProvider`
to the same `DbFlagProvider`. For a custom backend, bind them yourself:

```php
use Rasuvaeff\Yii3FeatureFlags\{FlagProvider, WritableFlagProvider};
use Rasuvaeff\Yii3FeatureFlagsDb\DbFlagProvider;

return [
    FlagProvider::class => DbFlagProvider::class,
    WritableFlagProvider::class => Reference::to(FlagProvider::class),
];
```

## Routes

| Method | Path | Action | Default name |
|---|---|---|---|
| GET | `{prefix}` | `Yii\List\Action` | `flags/list` |
| GET | `{prefix}/new` | `Yii\Edit\Action::new()` | `flags/create` |
| GET | `{prefix}/{name}/edit` | `Yii\Edit\Action` | `flags/edit` |
| POST | `{prefix}/new` | `Yii\Update\Action::new()` | `flags/store` |
| POST | `{prefix}/{name}` | `Yii\Update\Action` | `flags/update` |
| POST | `{prefix}/{name}/delete` | `Yii\Delete\Action` | `flags/delete` |

`middlewares.{all,list,edit,create,store,update,delete}` — add middlewares per slot without forking the routes. `RequestBodyParser` is added automatically to the POST routes (store, update, delete); set `'body_parser' => false` in params to opt out.

URLs and redirects are generated through the router (`UrlGeneratorInterface`) by route name; links stay correct under any prefix or subdomain. Override `route_names` in params when your app uses a different naming convention.

### Flat-route wiring

Wire the bundled `config/routes.php` explicitly in `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-feature-flags-ui/config/routes.php',
```

The route prefix, names and middlewares are read from params (`FlagRoutes::PARAM_KEY`).

### Group-based admin panel

Inside a `Group` (the typical approach for a shared-prefix admin area):

```php
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...FlagRoutes::fromParams($params),
);
```

`fromParams()` reads prefix, names, middlewares and body-parser opt-out from
`$params[FlagRoutes::PARAM_KEY]`, so route registration and `FlagUrls` URL generation
are always in sync.

For full control over names, use `create()` directly and add matching `route_names` to params:

```php
FlagRoutes::create(
    prefix: '/flags',
    names: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```

## Authorization

The package does not enforce access control internally. Protect routes via
`middlewares.all` (or per-route keys). The package provides `CurrentUser` injection
for audit events only.

## Public API

| Class | Description |
|---|---|
| `FlagRoutes` | Builds the 6 routes; `fromParams($params)` for group-based panels, `create()` for full control |
| `Yii\List\Action` | GET grid of all flags, with KILLED/OFF badges |
| `Yii\Edit\Action` | GET edit form for existing flag; `::new()` for create form |
| `Yii\Update\Action` | POST validate + save; `::new()` for create; re-render on invalid |
| `Yii\Delete\Action` | POST remove flag -> redirect to list |
| `Form\FlagForm` | Submitted edit input (name, enabled, rollout, salt, killSwitch, environments) |
| `Validation\FlagFormNormalizer` | Casts validated form to a `Flag` |
| `Renderer\TemplateRendererInterface` | Rendering seam (testable actions) |
| `Renderer\ViewTemplateRenderer` | Default renderer over `WebViewRenderer` |
| `Event\FlagChanged` | PSR-14 event after save/delete (name, operation, actor) |

## Read-only providers

If your `FlagProvider` does not implement `WritableFlagProvider`:

- `config/routes.php` still registers all routes; runtime `instanceof` checks return 403 on POST.
- List view shows a "Read-only provider" badge and the create button is hidden.
- Edit view disables all fields and shows a warning.

This lets `ConfigFlagProvider` (config-only flags) be browsed in the UI without write support.

## Security

| Concern | Behaviour |
|---|---|
| Read-only providers | `Update`/`Delete` rejected with HTTP 403 |
| Unknown flag name | `Edit` returns 404, `Update`/`Delete` return 404 |
| Invalid input | Re-renders the edit page with HTTP 200, no write |
| Flag name injection | On edit existing, the submitted `name` is ignored; the route name is pinned |
| Kill switch warning | The edit form always renders the warning; users cannot disable the warning |
| CSRF | Enforced by your application middleware; the form emits a hidden `_csrf` field when a `csrf_token` request attribute is present |
| Output | All values pass through `Yiisoft\Html\Html::encode()` / Html widgets / GridView encoding |

## Customising views

Override `views.list` and/or `views.edit` in params with absolute paths to your own templates. The templates receive the same variables as the bundled ones — see `resources/views/`.

The edit form uses input names scoped under `Flag[...]` (e.g. `Flag[name]`, `Flag[enabled]`). Custom edit templates must preserve this scope for `FlagForm::fromParsedBody()` to work.

**Flash messages** are not built in — the package does not know about the host app's session. Subscribe to `FlagChanged` in your app to add flash notifications, cache invalidation, or audit trail entries.

## Why `FlagChanged` Exists

The package emits `FlagChanged` after save/delete so the host app can react without
coupling itself to the UI actions. Typical uses are cache invalidation, audit
logging, metrics counters, and dependent reconfiguration. The `actor` field
carries the current user ID; `null` for guests.
