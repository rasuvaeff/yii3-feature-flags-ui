# Changelog

## 2.0.0 — 2026-10-17

- Actions no longer read their route argument through the `#[RouteArgument]`
  attribute hydration. The attribute is resolved only when the host app's
  middleware dispatcher carries a parameters resolver that understands hydrator
  attributes — `Yiisoft\Input\Http\HydratorAttributeParametersResolver` from
  `yiisoft/input-http`, the wiring the `yiisoft/app` template ships. Neither
  this package nor its README required it, so in any app without that binding
  every route with an argument (edit, update, delete) failed before the action
  was ever invoked: the dispatcher could not resolve the string parameter and
  the request ended 400. `Yii\Edit\Action`, `Yii\Update\Action` and
  `Yii\Delete\Action` now take a constructor-injected `CurrentRoute`
  (yiisoft/router is already a direct dependency) and read the `name` argument
  from it, making `yiisoft/input-http` unnecessary entirely. An unmatched route
  argument degrades to `''`, which hits the existing unknown-name → 404 path.
  The `->action(SomeAction::class)` route form the package ships needs
  `yiisoft/middleware-dispatcher` 5.2+ (invokable class-name definitions); the
  `require-dev` floor now pins it so the suite tests against a dispatcher the
  routes actually work on.
  **BC break:** the `__invoke()` signatures of the three actions changed and
  their constructors now require `CurrentRoute`; `new()` methods are unchanged
  (Fixes #16).

## 1.0.4 — 2026-10-17

- Fix a 500 on the flag edit/new pages (`GET /admin/flags/{name}/edit`,
  `GET /admin/flags/new`) in applications that do not configure
  `yiisoft/yii-view-renderer`'s `CsrfViewInjection`: `resources/views/edit.php`
  read `$csrf` unguarded, so PHP raised "Undefined variable $csrf" when the
  injection was absent. The view now follows the `yii3-settings-ui` pattern —
  `@var Csrf|null $csrf` and `($csrf ?? null) !== null` — and renders without
  the injection (Fixes #15).

## 1.0.3 — 2026-07-25

- Reject flag names containing `\n` or `\r` in `FlagFormRules`. PCRE `$` matches
  before a single trailing `\n`, so a smuggled `"<name>\n"` could otherwise pass
  the existing `NAME_PATTERN` regex. A new explicit newline check runs before
  the regex so any LF/CR fails form validation; `NAME_PATTERN` itself is
  unchanged to preserve the public contract.

## 1.0.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.0.0 — 2026-06-19

Initial release: a Bootstrap 5 admin UI for managing `rasuvaeff/yii3-feature-flags`
runtime settings.

