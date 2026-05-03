# Changelog

## [Unreleased](https://github.com/duoncode/sire/compare/0.3.0...HEAD)

### Breaking

- Made `Shape` and `Value` final; custom shapes now need composition through `Contract\Shape` instead of subclassing.
- Removed the closure-backed `Validator` adapter; custom validators now implement `Contract\Validator`.
- Replaced `Shape` constructor configuration, including `list`, `keepUnknown`, `title`, registry, parser, and `langs` arguments, with fluent configuration methods.
- Renamed `ValidationResult` to `Result` and updated `Contract\Shape::validate()` accordingly.
- Renamed `ValidatorDefinitionParser` to `ValidatorParser`.
- Removed `Shape` subclass hooks and mutable run-state access, including `rules()`, protected `review()`, `addError()`, `toSubValues()`, `$errorList`, and `$errorMap`.
- Changed the `in` validator to use strict comparisons, so values must match allowed values without PHP loose coercion.

### Added

- Added fluent `Shape` configuration with `Shape::list()`, `asList()`, `keepUnknown()`, `title()`, `validator()`, `validators()`, `type()`, `types()`, and `validatorParser()`.
- Added `Shape::review()` callbacks with `Review` for post-validation checks after successful normal validation.
- Added `Rule::prepare()` to normalize present field values before type casting and nested shape validation.
- Added quoted and escaped arguments for validator DSL definitions.
- Added `Contract\Validator` and built-in validator classes.

## [0.3.0](https://github.com/duoncode/sire/releases/tag/0.3.0) (2026-02-21)

Codename: Jonas

### Changed

- Breaking: Renamed `Schema` class to `Shape` and `Contract\Schema` interface to `Contract\Shape`.
- Breaking: `Rule::type()` now returns `'shape'` instead of `'schema'` for sub-shape fields.
- Breaking: Exception messages updated to reference "shape" instead of "schema" (`"Shape definition error: field must not be empty"`, `"Wrong shape type"`).

## [0.2.0](https://github.com/duoncode/sire/releases/tag/0.2.0) (2026-02-01)

### Changed

- Breaking: Updated `symfony/html-sanitizer` requirement to `^8.0` (Symfony 7 is no longer supported).

## [0.1.0](https://github.com/duoncode/sire/releases/tag/0.1.0) (2026-01-31)

Initial release.

### Added

- PHP validation library with fluent API
- Built-in validators for common data types
- HTML sanitization via symfony/html-sanitizer integration
