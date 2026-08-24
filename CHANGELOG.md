# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- `Meta` creates no directory: constructing one against a read-only tree or a phar no longer throws
- `$tmpDir` and `$logDir` keep the spelling they were given, so it no longer depends on whether the directory exists yet
- `$tmpDir` and `$logDir` are not created, so whoever writes to one creates it

### Removed
- The relative-path refusal on `$tmpDir` and `$logDir` (1.13.0): every caller validates before `Meta`, and `$appDir` is still refused

## [1.13.0] - 2026-08-19

### Added
- `$buildDir` on `AbstractAppMeta`: `{appDir}/var/build/{context}`, fixed to `appDir` regardless of `$writeDir` and not created
- `AbstractAppMeta::__wakeup()` re-points paths under `appDir` when the application has moved since serialization
- `Meta` refuses a relative `$appDir`, `$tmpDir` or `$logDir` with `WriteDirNotAbsoluteException`

### Changed
- `Meta::appDir($name)` moved to `AbstractAppMeta` and returns the canonical spelling, as do `$appDir`, `$tmpDir` and `$logDir`
- Canonical spellings make existing compile markers mismatch once: recompile when deploying this release (a writable tree recompiles on first boot; a read-only tree must be rebuilt)

### Deprecated
- `$writeDir` on `AbstractAppMeta`: still filled for released `bear/package` versions that read it; removal is a BC break and waits for the next major

## [1.12.0] - 2026-08-16

### Added
- `Meta::create($name, $context, $appDir, $writeDir)` places an application under `{writeDir}/{Vendor}/{Project}/{context}`, or its own `var/` when null
- `$writeDir` on `AbstractAppMeta`: the base an application writes under, carried rather than derived
- `Meta::appDir($name)` resolves an application directory from its name
- `Meta::create()` refuses a write directory the current directory would resolve, with `WriteDirNotAbsoluteException`

## [1.11.0] - 2026-07-10

### Added
- Optional `$tmpDir` and `$logDir` constructor arguments on `Meta` (defaults unchanged: `{appDir}/var/tmp/{context}` and `{appDir}/var/log/{context}`) (#41)

### Changed
- Centralize directory creation and writability checks in `ensureDir()`
- Default path segments use `/` (portable on Windows PHP)

## [1.10.0] - 2025-10-27

### Added
- Domain type aliases following BEAR.Package conventions (`Types.php`)
- PHP 8.5 support in CI test matrix
- Readonly properties for `ResMeta` value object
- Private constructor to `Types` class to prevent instantiation
- Literal union type for `Scheme` ('app'|'page'|'*')

### Changed
- **BREAKING**: Minimum PHP version upgraded from 8.0 to 8.1
- Lowered `bear/resource` requirement from ^1.26 to ^1.0 for better compatibility
- Lowered `koriym/psr4list` requirement from ^1.4 to ^1.0.2 for better compatibility
- Updated PHPUnit from ^9.5.10 to ^9.6 for PHP 8.1 readonly support
- Updated coding standards dependencies (doctrine/coding-standard ^14.0, squizlabs/php_codesniffer ^4.0)
- Use `@psalm-import-type` pattern across all classes
- Remove duplicate `@psalm-*` annotations in favor of standard PHPDoc

### Removed
- Removed `post-install-cmd` and `post-update-cmd` scripts for CI compatibility
- Removed phpmetrics dependency (incompatible with readonly keyword)

### Fixed
- Windows path separator issues in tests
- Add descriptive assert messages for better debugging

[1.13.0]: https://github.com/bearsunday/BEAR.AppMeta/compare/1.12.0...1.13.0
[1.11.0]: https://github.com/bearsunday/BEAR.AppMeta/compare/1.10.0...1.11.0
[1.10.0]: https://github.com/bearsunday/BEAR.AppMeta/compare/1.9.0...1.10.0
