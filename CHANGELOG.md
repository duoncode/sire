# Changelog

## [Unreleased](https://github.com/duoncode/sire/compare/0.3.0...HEAD)

### Breaking

- Made `Shape` and `Value` final; custom shapes now need composition through `Contract\Shape` instead of subclassing.
- Removed the closure-backed `Validator` adapter; custom validators now implement `Contract\Validator`.
- Changed `Contract\Validator::validate()` to return `Contract\Validation` instead of `bool`.
- Removed coercion errors from `Contract\Value`; custom coercers now return `Contract\Coercion` with direct `value`, `pristine`, and `failure` properties.
- Changed `Contract\Coercer::coerce()` to receive only the pristine value.
- Added a required `message` property to `Contract\Coercer`.
- Changed `CoercerRegistry::withDefaults()` to no longer accept message configuration.
- Removed `skipEmpty` from `Contract\Validator`; regular validators now skip empty values and validators that must run on empty values implement `Contract\ValidatesEmpty`.
- Replaced `Shape` constructor configuration, including `list`, `extra`, `title`, registry, parser, and `langs` arguments, with fluent configuration methods.
- Replaced `Shape::keepUnknown()` with `Shape::extra(Extra::Allow)`.
- Renamed `ValidationResult` to `Result` and updated `Contract\Shape::validate()` accordingly.
- Renamed `ValidatorDefinitionParser` to `ValidatorParser`.
- Removed `Shape` subclass hooks and mutable run-state access, including `rules()`, protected `review()`, `addError()`, `toSubValues()`, `$errorList`, and `$errorMap`.
- Changed the `in` validator to use strict comparisons, so values must match allowed values without PHP loose coercion.
- Changed built-in default error messages to include labels and named placeholders.
- Changed missing fields to fail validation by default; use `Rule::optional()` to omit them or `Rule::default()` to fill them.
- Changed missing boolean fields to no longer default to `false`; use `Rule::default(false)` for checkbox-style defaults.
- Changed explicit `null` values to fail before coercion unless `Rule::nullable()` is used or preparation returns a non-null value.

### Added

- Added fluent `Shape` configuration with `Shape::list()`, `asList()`, `extra()`, `title()`, `validator()`, `validators()`, `type()`, `types()`, and `validatorParser()`.
- Added the `Extra` enum to control extra input fields with `ignore`, `allow`, and `forbid` modes.
- Added the `number` type for values that may be integers or floats.
- Added `Shape::review()` callbacks with `Review` for post-validation checks after successful normal validation.
- Added `Rule::prepare()` to normalize present or defaulted field values before type casting and nested shape validation.
- Added `Rule::finalize()` to transform valid output values after field validation and before review callbacks.
- Added `Rule::optional()`, `Rule::default()`, `Rule::empty()`, and `Rule::nullable()` for field presence control.
- Added the `Blank` enum for configuring which raw input states count as empty.
- Added `Rule::message()` and `Rule::messages()` for field-specific type and validator messages.
- Added quoted and escaped arguments for validator DSL definitions.
- Added `Contract\Validator`, `Contract\ValidatesEmpty`, `Contract\Coercion`, `Contract\Validation`, and built-in validator classes.
- Added `Shape::message()` and `Shape::messages()` for coercion and validator error messages.
- Added structured coercion failures through `Failure` and central type message formatting based on the registered type name.
- Added named placeholders such as `{label}`, `{field}`, `{value}`, and `{arg1}` for coercion and validator message templates.

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
