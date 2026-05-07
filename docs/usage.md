---
title: Usage
---

# Usage

This guide covers the day-to-day Sire API, including shape creation, validation execution, result handling, nested shapes, and extension points.

## Validate data with a shape

Create a `Shape`, define fields with `add()`, attach field constraints with `rules()`, and call `validate()` to get a `Result` object.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('email', 'string')->rules('required', 'email')->label('Email address');
$shape->add('age', 'int')->rules('min:18');

$result = $shape->validate([
    'email' => 'test@example.com',
    'age' => '21',
]);

if (!$result->valid()) {
    var_dump($result->issues());
}

var_dump($result->values());
```

## Parse valid values or throw

Use `parse()` when invalid input should stop the current flow. It returns the same coerced and finalized values as `Result::values()` after validation and review both succeed. It throws `Duon\Sire\Exception\ValidationError` when the data is invalid.

```php
<?php

use Duon\Sire\Exception\ValidationError;
use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('email', 'string')->rules('required', 'email');

try {
    $values = $shape->parse(['email' => 'test@example.com']);
} catch (ValidationError $error) {
    var_dump($error->result()->issues());
}
```

Use `validate()` when you need to branch on validation state without exceptions.

## Configure shape behavior

`new Shape()` creates an object shape. Configure shape behavior with fluent methods.

```php
<?php

use Duon\Sire\Extra;
use Duon\Sire\Shape;

$shape = Shape::list()
    ->extra(Extra::Allow);
```

Use `asList(false)` to switch a configured list shape back to object mode.

Sire coerces field values by default. Use `strict()` when fields should reject values that are not already the target PHP type, and use `coerce()` to switch back to coercing mode. Shape-level mode applies to fields on that shape; field-level mode overrides the shape default.

```php
<?php

use Duon\Sire\Shape;

$shape = (new Shape())->strict();
$shape->add('age', 'int'); // accepts only native ints
$shape->add('count', 'int')->coerce(); // accepts numeric strings too
```

Strict mode checks the prepared or defaulted value after field-level empty handling. For `float`, strict mode accepts only native floats. Use `number` when native integers and floats should both be valid. Nested shapes use their own coercion mode configuration.

`extra()` controls input fields that do not have a field:

- `Extra::Ignore` drops extra fields. This is the default.
- `Extra::Allow` keeps extra fields as-is in `values()`.
- `Extra::Forbid` reports extra fields as validation errors.

You can also pass the strings `ignore`, `allow`, and `forbid`. Configure the forbid error with `message('extra', 'Field "{field}" is not allowed')`. Extra messages can use `{field}` and `{value}`.

## Use built-in types and rules

Sire supports a small set of built-in types and rules out of the box, so you can start without additional configuration.

- Built-in types: `string`, `int`, `float`, `number`, `bool`, `list`
- Built-in rules: `required`, `email`, `minlen`, `maxlen`, `min`, `max`, `regex`, `in`

`string` accepts strings, numbers, and stringable objects in coercing mode. It preserves string values, including `''` and `'0'`, and rejects booleans, arrays, and non-stringable objects. In strict mode, it accepts only native strings.

`bool` accepts common PHP and HTML form boolean values in coercing mode: `true`, `false`, `1`, `0`, `'1'`, `'0'`, `'true'`, `'false'`, `'on'`, `'off'`, `'yes'`, and `'no'`. String values are trimmed and matched case-insensitively. In strict mode, it accepts only native booleans. `null` is controlled by `nullable()`; missing fields are controlled by `default()` and `optional()`. Empty strings still fail type validation unless field-level `empty()` handling catches them first.

Add rules to a field with `Field::rules()`. The rule DSL uses `:` to separate the rule name from arguments.

- `required`
- `min:10`
- `email:checkdns`
- `in:active,inactive`

The `in` rule uses strict comparison against the typed value. Prepare or coerce input to the expected type before using `in` for non-string values.

## Use quoted and escaped DSL arguments

You can keep commas and colons inside argument values by quoting or escaping them.

- Quoted comma values: `in:"ACME, Inc",Globex`
- Escaped comma values: `in:ACME\, Inc,Globex`
- Quoted colon values: `starts_with:"http://"`
- Escaped colon values: `starts_with:http\://`

Sire throws a `ValueError` if a rule definition is malformed, for example for unclosed quotes or a missing rule name.

## Control field presence

Fields are required by default. A missing field reports a validation error and is omitted from `values()`.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('title', 'string')->label('Title');

$result = $shape->validate([]);

var_dump($result->first('title')); // "Title is required"
```

Use `optional()` when a missing field should have no effect on validation output.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('subtitle', 'string')->optional();

$result = $shape->validate([]);

var_dump($result->values()); // []
```

Use `default()` when an empty field should be filled. By default, only missing input counts as empty. Defaults run through the same preparation, nullability, type handling or nested validation, rule, and finalization pipeline as submitted values. Present non-empty input wins over the default.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('status', 'string')->default('draft');
$shape->add('count', 'int')->default('0');

$result = $shape->validate([]);

var_dump($result->values()); // ['status' => 'draft', 'count' => 0]
```

Use `empty()` to configure which field value states count as empty for a field. Present values run through `prepare()` first, then Sire checks them against the configured empty states. Missing input is handled separately because there is no value to prepare. Empty values use the default when one exists, are omitted when the field is optional, or report the normal missing-field error otherwise. The enum is named `Blank` because `empty` is a PHP language construct.

```php
<?php

use Duon\Sire\Blank;
use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('status', 'string')
    ->empty(Blank::Missing, Blank::Null, Blank::Whitespace)
    ->default('draft');

var_dump($shape->validate([])->values()['status']); // "draft"
var_dump($shape->validate(['status' => null])->values()['status']); // "draft"
var_dump($shape->validate(['status' => '  '])->values()['status']); // "draft"
```

`empty()` replaces the field's empty-value set. Include `Blank::Missing` when a default should still apply to missing input.

- `Blank::Missing` matches an absent field.
- `Blank::Null` matches explicit `null` after preparation.
- `Blank::String` matches exact `''` after preparation.
- `Blank::Whitespace` matches strings where `trim($value) === ''`, including `''`, after preparation.
- `Blank::List` matches exact `[]` after preparation.

You can pass enum cases or strings such as `'null'` and `'whitespace'`.

Use `nullable()` when explicit `null` should be accepted instead of treated as empty. `default(null)` implies `nullable()`.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('discount_code', 'string')->rules('maxlen:64') ->default('') ->nullable();
```

The `required` rule checks the typed value's `Value::$empty` flag. Built-in coercers mark `null` as empty; `string` also marks `''` as empty, and `list` also marks `[]` as empty. They treat successful values such as `false`, `0`, `0.0`, `'0'`, `'false'`, `'off'`, and `'no'` as present. Blank strings are valid empty string values, but they are type errors for `bool`, `int`, `float`, `number`, and `list` unless field-level empty handling catches them first. Field rules, including `required`, do not run after failed type coercion, failed strict type checks, or failed nested validation. Custom coercers control this flag through `Coercion`.

For a validation run, Sire applies shape preparation first. For present fields, Sire then applies field preparation, field-level empty handling, `default()` or `optional()` if needed, nullability, type coercion or strict type checking, nested validation when the field type is a shape, field rules, `finalize()`, and finally review callbacks. Missing fields do not run field preparation unless a default is used. Defaults run through field preparation, nullability, type handling or nested validation, rules, and finalizers.

## Prepare shape input before fields

Use `Shape::prepare()` when the whole input payload needs migration or normalization before Sire reads fields, handles extra fields, or validates list items. Shape prepare callbacks run in registration order and receive the current shape input array. They must return the next input array.

```php
<?php

use Duon\Sire\Extra;
use Duon\Sire\Shape;

$shape = (new Shape())
    ->extra(Extra::Forbid)
    ->prepare(static function (array $data): array {
        if (array_key_exists('firstName', $data) && !array_key_exists('first_name', $data)) {
            $data['first_name'] = $data['firstName'];
            unset($data['firstName']);
        }

        return $data;
    });

$shape->add('first_name', 'string');
```

For `Shape::list()`, the callback receives the whole list, not each item. Map the list inside the callback when you need per-item migration.

```php
<?php

use Duon\Sire\Shape;

$users = Shape::list()->prepare(static fn(array $items): array => array_map(
    static fn(mixed $item): array => is_string($item) ? ['email' => $item] : $item,
    $items,
));

$users->add('email', 'string')->rules('email');
```

If a shape prepare callback throws, the exception is not caught by Sire.

## Prepare field values before validation

Use `Field::prepare()` when a field value needs normalization before Sire checks field-level emptiness, coerces or strictly checks the type, or validates it. Field prepare callbacks run in registration order for present fields and for fields filled by `default()`. They receive the input data after shape preparation. They do not run for missing `optional()` fields or missing fields that report an error.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape
    ->add('age', 'int')->rules('min:18')
    ->prepare(static fn(mixed $value): mixed => trim((string) $value));

$result = $shape->validate(['age' => ' 21 ']);

var_dump($result->values()['age']); // 21
```

Field prepare callbacks receive the current field value and the current shape item data after shape preparation. This lets you derive a value from another field before validation. If the prepared value matches the field's `empty()` configuration, Sire applies `default()`, omits an optional field, or reports the normal missing-field error before coercion.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('title', 'string');
$shape->add('slug', 'string')->rules('required')
    ->default('')
    ->prepare(static function (mixed $value, array $data): string {
        if ($value !== '') {
            return (string) $value;
        }

        return strtolower(str_replace(' ', '-', (string) $data['title']));
    });

$result = $shape->validate(['title' => 'Hello World']);

var_dump($result->values()['slug']); // "hello-world"
```

Prepare callbacks also run before nested shape validation. This is useful when external input needs a small shape adjustment before a nested shape sees it.

```php
<?php

use Duon\Sire\Shape;

$profile = new Shape();
$profile->add('bio', 'string')->optional();

$user = new Shape();
$user
    ->add('profile', $profile)
    ->prepare(static fn(mixed $value): mixed => $value ?? []);
```

If a prepare callback throws, the exception is not caught by Sire. If it returns an invalid value for the field type, type handling or nested validation reports the validation error and field rules do not run for that value.

## Finalize output after validation

Use `Field::finalize()` when a field needs a final output transform after successful type handling or nested validation and field rules. Finalize callbacks run only when validation has no errors, before review callbacks, and in registration order for each field.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('title', 'string');
$shape->add('slug', 'string')
    ->default('')
    ->finalize(static function (mixed $value, array $values): string {
        if ($value !== '') {
            return (string) $value;
        }

        return strtolower(str_replace(' ', '-', (string) $values['title']));
    });

$result = $shape->validate(['title' => 'Hello World']);

var_dump($result->values()['slug']); // "hello-world"
```

Finalize callbacks receive the current field value and the current validated values for the same shape item. In list shapes, the values array contains the current list item. They run for present fields and defaulted fields, but not for omitted optional fields.

If a finalize callback throws, the exception is not caught by Sire.

## Read validation results

The `Result` object is the primary output of validation. Use it as your source of truth when you call `validate()`. Use `Shape::parse()` when you want valid values directly or a `ValidationError` exception.

- `valid()` returns `true` when no issues exist.
- `issues()` returns typed `Issue` objects with `path`, `code`, `message`, and `params`.
- `messages($path)` returns all messages for one exact path.
- `first($path)` returns the first message for one exact path, or `null`.
- `has($path)` returns whether one exact path has messages.
- `values()` returns coerced and finalized values.

Paths can be dot strings such as `address.zip` or arrays such as `[0, 'email']` for list items. Calling `messages()` or `first()` without a path reads root-level form errors.

`Result` and `Issue` implement `JsonSerializable`. JSON output contains `valid` and `issues`; it does not include submitted or validated values.

## Customize messages

Use `message()` or `messages()` to override coercion and rule errors for a shape. Built-in type keys are `type.string`, `type.int`, `type.float`, `type.number`, `type.bool`, and `type.list`. Field presence keys are `missing` and `null`. Built-in rule keys use the rule name, for example `rule.required`, `rule.email`, `rule.min`, and `rule.max`. Custom coercers and rules use their registered names, for example `type.slug` and `rule.starts_with`.

```php
<?php

use Duon\Sire\Shape;

$shape = (new Shape())
    ->message('type.int', '{label} must be a whole number')
    ->messages([
        'type.bool' => '{label} must be yes or no',
        'missing' => '{label} is required',
        'rule.required' => '{label} is required',
    ]);

$shape->add('age', 'int')->rules('required')->label('Age');
$shape->add('enabled', 'bool')->label('Enabled');
```

Use `Field::message()` or `Field::messages()` for field-specific messages. Field messages override shape messages for the same field.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape
    ->add('age', 'int')->rules('max:120')
    ->label('Age')
    ->messages([
        'type' => '{label} must be a whole number',
        'max' => '{label} must be at most {arg1}',
    ]);
```

In field messages, `type` means the field's own type, `missing` and `null` mean field presence errors, and rule names such as `max`, `required`, or `email` mean that rule. Explicit keys such as `type.int` and `rule.max` also work.

Message templates can use named placeholders:

- `{label}` is the field label, or the field name when no label is set.
- `{field}` is the field name.
- `{value}` is the pristine value that reached validation.
- `{arg1}`, `{arg2}`, and later values come from custom `Failure` arguments for coercers or rule DSL arguments for rules.

Use `{{` and `}}` for literal braces. Do not mix named and `sprintf()` placeholders in one template. Existing `sprintf()` templates still work, with `%1$s`, `%2$s`, `%3$s`, and `%4$s` mapping to `{label}`, `{field}`, `{value}`, and `{arg1}`.

When no field or shape-level message is configured, Sire uses the coercer or rule's `message` property.

## Review validated values

Use `Shape::review()` for cross-field checks or other post-validation checks. Review callbacks run last and see finalized values. They run only after normal validation succeeds. If one review callback adds an error, later review callbacks still run so the result can contain all review errors.

```php
<?php

use Duon\Sire\Review;
use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('password', 'string')->rules('required');
$shape->add('confirm', 'string')->rules('required')->label('Password confirmation');

$shape->review(static function (Review $review): void {
    $values = $review->values();

    if ($values['password'] === $values['confirm']) {
        return;
    }

    $review->addError(
        'confirm',
        'Passwords do not match',
        'password.confirmed',
    );
});
```

`Review` exposes the validated values, list flag, and `addError()`.

## Validate nested objects and lists

You can use another shape as a field type to validate nested structures. Create a list shape with `Shape::list()`.

```php
<?php

use Duon\Sire\Shape;

$address = new Shape();
$address->add('street', 'string')->rules('required');
$address->add('zip', 'string')->rules('required', 'minlen:5');

$user = new Shape();
$user->add('name', 'string')->rules('required');
$user->add('address', $address);

$users = Shape::list();
$users->add('name', 'string')->rules('required');
$users->add('address', $address);
```

## Compose reusable custom shapes

`Shape` is final. To create reusable custom shapes, implement `Contract\Validator` and delegate to a configured `Shape` instance.

```php
<?php

use Duon\Sire\Contract;
use Duon\Sire\Shape;
use Duon\Sire\Result;
use Override;

final class LoginShape implements Contract\Validator
{
    private Shape $shape;

    public function __construct()
    {
        $this->shape = new Shape();
        $this->shape->add('email', 'string')->rules('required', 'email');
        $this->shape->add('password', 'string')->rules('required');
    }

    #[Override]
    public function validate(array $data): Result
    {
        return $this->shape->validate($data);
    }
}
```

Delegating shapes can be used anywhere a nested shape is accepted because Sire fields accept `Contract\Validator`. If a custom shape also exposes `parse()`, implement `Contract\Parser` and delegate both methods.

## Extend rules and coercers

Configure a shape fluently when you need project-specific rules, coercion behavior, or DSL parsing.

- Use `rule()` to add or replace one rule.
- Use `Shape::rules()` to replace the rule registry.
- Use `type()` to add or replace one base type with its coercer.
- Use `types()` to replace the coercer registry.
- Use `strict()` and `coerce()` to set the shape-level coercion mode.
- Use `message()` to override one type, rule, or extra-field message.
- Use `messages()` to override many type, rule, or extra-field messages.
- Use `ruleParser()` if you need a different DSL split strategy.

Custom rules implement `Duon\Sire\Contract\Rule`, expose a default `message`, and return `Duon\Sire\Contract\Validation`; use `Duon\Sire\Validation` when the default immutable result object is enough. Rules do not run after failed type handling or failed nested validation. Rules also skip values where `Contract\Value::$empty` is `true` by default; implement `Duon\Sire\Contract\ValidatesEmpty` when a rule must run for successfully typed empty values. Custom coercers implement `Duon\Sire\Contract\Coercer`, expose a default `message`, and return `Duon\Sire\Contract\Coercion`; use `Duon\Sire\Coercion` when the default immutable result object is enough. Sire passes the resolved `Duon\Sire\CoercionMode` to `coerce()`, so custom coercers can reject non-native values in strict mode. Pass `empty: true` to `Coercion` when the coerced value has no meaningful content for its type. Return `Failure::invalid()` when a coercer or rule cannot produce a valid value. Use `Failure::key()` only when one coercer or rule has multiple distinct failure modes.

```php
<?php

use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Duon\Sire\Shape;
use Duon\Sire\Validation;
use Override;

$shape = new Shape();
$shape->rule(
    'starts_with',
    new class implements Contract\Rule {
        public string $message {
            get => 'Must start with {arg1}';
        }

        #[Override]
        public function validate(Contract\Value $value, string ...$args): Contract\Validation
        {
            return Validation::from(
                str_starts_with((string) $value->value, $args[0] ?? ''),
            );
        }
    },
);

$shape
    ->message('type.slug', '{label} must contain only letters, numbers, and dashes')
    ->type(
        'slug',
        new class implements Contract\Coercer {
            public string $message {
                get => 'Invalid slug';
            }

            #[Override]
            public function coerce(
                mixed $pristine,
                CoercionMode $mode = CoercionMode::Coerce,
            ): Contract\Coercion {
                if ($mode === CoercionMode::Strict && !is_string($pristine)) {
                    return new Coercion(
                        $pristine,
                        $pristine,
                        Failure::invalid(),
                    );
                }

                $value = strtolower(trim((string) $pristine));

                if ($value !== '' && !preg_match('/^[a-z0-9-]+$/', $value)) {
                    return new Coercion(
                        $pristine,
                        $pristine,
                        Failure::invalid(),
                    );
                }

                return new Coercion($value, $pristine, empty: $value === '');
            }
        },
    );
```

## Next steps

Continue with the [development guide](development.md) for local workflows, tests, and quality checks.
