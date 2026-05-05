# Changelog

## [Unreleased](https://github.com/duoncode/sire/compare/0.3.0...HEAD)

### Breaking

- Renamed `Result::isValid()` to `Result::valid()`.
- Renamed field definitions from `Rule` to `Field`.
- Renamed field checks from validators to rules, including `Contract\Validator` to `Contract\Rule`, `ValidatorRegistry` to `RuleRegistry`, `ValidatorParser` to `RuleParser`, and the fluent `Shape::validator*()` methods to `Shape::rule*()` methods.
- Renamed `Contract\Shape` to `Contract\Validator` for reusable shape validators.
- Changed field rule issue and message keys from `validator.*` to `rule.*`.
- Made `Shape` and `Value` final; custom shapes now need composition through `Contract\Validator` instead of subclassing.
- Removed the closure-backed custom rule adapter; custom rules now implement `Contract\Rule`.
- Changed `Contract\Rule::validate()` to return `Contract\Validation` instead of `bool`.
- Removed coercion errors from `Contract\Value`; custom coercers now return `Contract\Coercion` with direct `value`, `pristine`, and `failure` properties.
- Changed `Contract\Coercer::coerce()` to receive only the pristine value.
- Added a required `message` property to `Contract\Coercer`.
- Changed `CoercerRegistry::withDefaults()` to no longer accept message configuration.
- Removed `skipEmpty` from `Contract\Rule`; regular rules now skip empty values and rules that must run on empty values implement `Contract\ValidatesEmpty`.
- Replaced `Shape` constructor configuration, including `list`, `extra`, `title`, registry, parser, and `langs` arguments, with fluent configuration methods.
- Replaced `Shape::keepUnknown()` with `Shape::extra(Extra::Allow)`.
- Renamed `ValidationResult` to `Result` and updated `Contract\Validator::validate()` accordingly.
- Replaced `Violation`, `violations()`, `errors()`, and `map()` with path-aware `Issue` objects plus `Result::issues()`, `messages()`, `first()`, and `has()`.
- Removed `Result::pristineValues()` and removed values from default JSON serialization; serialized results now contain `valid` and `issues` only.
- Removed `Shape::title()` and the validation `level` argument.
- Simplified `Review::addError()` to accept `path`, `message`, `code`, and `params`.
- Renamed field rule parser APIs to `RuleParser`.
- Removed `Shape` subclass hooks and mutable run-state access, including subclass field definitions, protected `review()`, `addError()`, `toSubValues()`, `$errorList`, and `$errorMap`.
- Changed the `in` rule to use strict comparisons, so values must match allowed values without PHP loose coercion.
- Changed built-in default error messages to include labels and named placeholders.
- Changed missing fields to fail validation by default; use `Field::optional()` to omit them or `Field::default()` to fill them.
- Changed missing boolean fields to no longer default to `false`; use `Field::default(false)` for checkbox-style defaults.
- Changed explicit `null` values to fail before coercion unless `Field::nullable()` is used or preparation returns a non-null value.

### Added

- Added fluent `Shape` configuration with `Shape::list()`, `asList()`, `extra()`, `rule()`, `rules()`, `type()`, `types()`, and `ruleParser()`.
- Added the `Extra` enum to control extra input fields with `ignore`, `allow`, and `forbid` modes.
- Added the `number` type for values that may be integers or floats.
- Added `Shape::review()` callbacks with `Review` for post-validation checks after successful normal validation.
- Added `Field::prepare()` to normalize present or defaulted field values before type casting and nested shape validation.
- Added `Field::finalize()` to transform valid output values after field validation and before review callbacks.
- Added `Field::optional()`, `Field::default()`, `Field::empty()`, and `Field::nullable()` for field presence control.
- Added the `Blank` enum for configuring which raw input states count as empty.
- Added `Field::message()` and `Field::messages()` for field-specific type and rule messages.
- Added quoted and escaped arguments for rule DSL definitions.
- Added `Contract\Rule`, `Contract\ValidatesEmpty`, `Contract\Coercion`, `Contract\Validation`, and built-in rule classes.
- Added `Shape::message()` and `Shape::messages()` for coercion and rule error messages.
- Added structured coercion failures through `Failure` and central type message formatting based on the registered type name.
- Added named placeholders such as `{label}`, `{field}`, `{value}`, and `{arg1}` for coercion and rule message templates.
- Added `Shape::parse()` and `Contract\Parser` to return valid values directly or throw `Exception\ValidationError`.

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
