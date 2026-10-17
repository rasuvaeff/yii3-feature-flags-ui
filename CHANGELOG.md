# Changelog

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

