# AGENTS.md — yii3-feature-flags-ui

Guidance for AI agents working on this package. Read before changing code.

## What this is

Web admin panel for `rasuvaeff/yii3-feature-flags`. PSR-15 actions render a typed
edit UI on top of `FlagProvider` (read model) and `WritableFlagProvider` (writes) —
both usually implemented by `rasuvaeff/yii3-feature-flags-db`. Namespace:
`Rasuvaeff\Yii3FeatureFlagsUi`.

Public API: `Yii\List\Action`, `Yii\Edit\Action` (with `::new()` for create),
`Yii\Update\Action` (with `::new()` for create), `Yii\Delete\Action`
(PSR-15 handlers), `FlagRoutes`, `Form\FlagForm` (a `FormModel`),
`Validation\FlagFormNormalizer`, `Renderer\TemplateRendererInterface` +
`ViewTemplateRenderer`, `Event\FlagChanged`. Config-plugin groups: `di`, `params`, `routes`.
Internals (`@internal`): `Renderer\EditPageRenderer`, `Service\FlagsGridFactory`,
`Validation\FlagFormRules`, `View\FlagPresenter`, and the `Service\*`
responders/processors/`FlagUrls`.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Kill switch warning always renders.** The `Kill switch` form field must
   always carry its danger warning explaining it overrides rollout, targeting
   and forced values. Do not remove the warning even if the host app hides the
   checkbox.
4. **Edit existing pins the route name.** The submitted `name` field is ignored
   when editing an existing flag — the route argument is the source of truth.
   This prevents form-tampering renames.
5. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- The package is RBAC-agnostic. Routes (`config/routes.php`) carry no auth; the
  host app wraps them in its own auth/RBAC middleware via `FlagRoutes`'s
  `middlewares` option. Processors only enforce domain rules: unknown name → 404,
  read-only provider rejects Update/Delete/Create → 403.
- Read-only mode is decided by `instanceof WritableFlagProvider` on the injected
  provider — there is no separate "readonly" flag on `Flag`. If the provider is
  not writable, POST routes return 403 and the edit form disables fields.
- Validation runs through `FlagFormRules` (yiisoft/validator) which mirror the
  `Flag` constructor constraints (name regex, rollout 0..100, environments as a
  JSON list<string>). The normalizer then casts the validated form to a `Flag`;
  a double-check `try/catch` catches any residual `\InvalidArgumentException`
  thrown by the `Flag` constructor and re-renders the form.
- `environments` field is a JSON-encoded array of non-empty strings; blank input
  or `[]` means "all environments". Never pass the raw textarea string to the
  provider.
- Rendering goes through `TemplateRendererInterface`; `WebViewRenderer` is final
  and must not be referenced directly from actions (untestable + needs explicit
  view path/layout). `ViewTemplateRenderer` pins the bundled view path.
- The list view is a `GridView` (yiisoft/yii-dataview) rendered server-side by
  `Service\FlagsGridFactory`, which constructs `new GridView($container)` with
  the application DI container (injected via `config/di.php`) and passes the
  pre-rendered HTML to the template as `gridHtml`. The host does **not** need to
  bootstrap `WidgetFactory`. The edit view uses `yiisoft/html` widgets. Both
  views apply **Bootstrap 5** classes only (no inline styles) — the host app
  must provide Bootstrap CSS. The list template also receives the raw `flags`
  (list<FlagPresenter>) so a custom view can build its own grid.
- CSRF is the application middleware's responsibility; the edit form emits a
  hidden `_csrf` field when a `csrf_token` request attribute is present.
- `yiisoft/router` needs a concrete router implementation
  (e.g. `yiisoft/router-fastroute`) — provided by the host app, and as a dev
  dependency here.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.
- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects the public API or release
  process, also run `make release-check`. Paste the output.
