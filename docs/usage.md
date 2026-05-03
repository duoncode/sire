---
title: Usage
---

# Usage

This guide covers the day-to-day Sire API, including shape creation, validation execution, result handling, nested shapes, and extension points.

## Validate data with a shape

Create a `Shape`, define rules with `add()`, and call `validate()` to get a `Result` object.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('email', 'text', 'required', 'email')->label('Email address');
$shape->add('age', 'int', 'min:18');

$result = $shape->validate([
    'email' => 'test@example.com',
    'age' => '21',
]);

if (!$result->isValid()) {
    var_dump($result->errors());
}

var_dump($result->values());
```

## Configure shape behavior

`new Shape()` creates an object shape. Configure shape metadata and behavior with fluent methods.

```php
<?php

use Duon\Sire\Shape;

$shape = Shape::list()
    ->title('Users')
    ->keepUnknown();
```

Use `asList(false)` to switch a configured list shape back to object mode.

## Use built-in types and validators

Sire supports a small set of built-in types and validators out of the box, so you can start without additional configuration.

- Built-in types: `text`, `int`, `float`, `bool`, `list`
- Built-in validators: `required`, `email`, `minlen`, `maxlen`, `min`, `max`, `regex`, `in`

The validator DSL uses `:` to separate the validator name from arguments.

- `required`
- `min:10`
- `email:checkdns`
- `in:active,inactive`

The `in` validator uses strict comparison against the cast value. Prepare or cast input to the expected type before using `in` for non-text values.

## Use quoted and escaped DSL arguments

You can keep commas and colons inside argument values by quoting or escaping them.

- Quoted comma values: `in:"ACME, Inc",Globex`
- Escaped comma values: `in:ACME\, Inc,Globex`
- Quoted colon values: `starts_with:"http://"`
- Escaped colon values: `starts_with:http\://`

Sire throws a `ValueError` if a validator definition is malformed, for example for unclosed quotes or a missing validator name.

## Prepare input before validation

Use `Rule::prepare()` when a present field value needs normalization before Sire casts or validates it. Prepare callbacks run in registration order, only for known fields that are present in the input.

```php
<?php

use Duon\Sire\Shape;

$shape = new Shape();
$shape
    ->add('age', 'int', 'min:18')
    ->prepare(static fn(mixed $value): mixed => trim((string) $value));

$result = $shape->validate(['age' => ' 21 ']);

var_dump($result->values()['age']); // 21
```

Prepare callbacks also run before nested shape validation. This is useful when external input needs a small shape adjustment before a nested shape sees it.

```php
<?php

use Duon\Sire\Shape;

$profile = new Shape();
$profile->add('bio', 'text');

$user = new Shape();
$user
    ->add('profile', $profile)
    ->prepare(static fn(mixed $value): mixed => $value ?? []);
```

If a prepare callback throws, the exception is not caught by Sire. If it returns an invalid value for the rule type, normal coercion or nested validation reports the validation error.

## Read validation results

The `Result` object is the primary output of validation. Use it as your source of truth in application code.

- `isValid()` returns `true` when no violations exist.
- `violations()` returns typed `Violation` objects.
- `errors()` returns a structured array output.
- `errors(grouped: true)` groups errors by shape section.
- `map()` returns a field-to-messages map.
- `values()` returns coerced values.
- `pristineValues()` returns values before coercion. If `Rule::prepare()` is used, these are the prepared values, not the original raw input.

`Result` and `Violation` implement `JsonSerializable`, so you can return them directly from JSON APIs.

## Review validated values

Use `Shape::review()` for cross-field checks or other post-validation checks. Review callbacks run only after normal validation succeeds. If one review callback adds an error, later review callbacks still run so the result can contain all review errors.

```php
<?php

use Duon\Sire\Review;
use Duon\Sire\Shape;

$shape = new Shape();
$shape->add('password', 'text', 'required');
$shape->add('confirm', 'text', 'required')->label('Password confirmation');

$shape->review(static function (Review $review): void {
    $values = $review->values();

    if (($values['password'] ?? null) === ($values['confirm'] ?? null)) {
        return;
    }

    $review->addError(
        'confirm',
        'Password confirmation',
        'Passwords do not match',
    );
});
```

`Review` exposes the validated values, pristine values, list flag, shape title, validation level, and `addError()`.

## Validate nested objects and lists

You can use another shape as a field type to validate nested structures. Create a list shape with `Shape::list()`.

```php
<?php

use Duon\Sire\Shape;

$address = new Shape();
$address->add('street', 'text', 'required');
$address->add('zip', 'text', 'required', 'minlen:5');

$user = new Shape();
$user->add('name', 'text', 'required');
$user->add('address', $address);

$users = Shape::list();
$users->add('name', 'text', 'required');
$users->add('address', $address);
```

## Compose reusable custom shapes

`Shape` is final. To create reusable custom shapes, implement `Contract\Shape` and delegate to a configured `Shape` instance.

```php
<?php

use Duon\Sire\Contract;
use Duon\Sire\Shape;
use Duon\Sire\Result;
use Override;

final class LoginShape implements Contract\Shape
{
    private Shape $shape;

    public function __construct()
    {
        $this->shape = new Shape();
        $this->shape->add('email', 'text', 'required', 'email');
        $this->shape->add('password', 'text', 'required');
    }

    #[Override]
    public function validate(array $data, int $level = 1): Result
    {
        return $this->shape->validate($data, $level);
    }
}
```

Delegating shapes can be used anywhere a nested shape is accepted because Sire rules accept `Contract\Shape`.

## Extend validators and coercers

Configure a shape fluently when you need project-specific rules, coercion behavior, or DSL parsing.

- Use `validator()` to add or replace one validator.
- Use `validators()` to replace the validator registry.
- Use `type()` to add or replace one base type with its coercer.
- Use `types()` to replace the coercer registry.
- Use `validatorParser()` if you need a different DSL split strategy.

Custom validators should type against `Duon\Sire\Contract\Value`. Validators skip empty values by default; implement `Duon\Sire\Contract\ValidatesEmpty` when a validator must run for empty values. Coercers return `Duon\Sire\Contract\Coercion`; use `Duon\Sire\Coercion` when the default immutable result object is enough.

```php
<?php

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Shape;
use Override;

$shape = new Shape();
$shape->validator(
    'starts_with',
    new class implements Contract\Validator {
        public string $message = 'Must start with %4$s';

        #[Override]
        public function validate(Contract\Value $value, string ...$args): bool
        {
            return str_starts_with((string) $value->value, $args[0] ?? '');
        }
    },
);

$shape->type(
    'slug',
    new class implements Contract\Coercer {
        #[Override]
        public function coerce(mixed $pristine, string $label): Contract\Coercion
        {
            $value = strtolower(trim((string) $pristine));

            return new Coercion($value, $pristine);
        }
    },
);
```

## Next steps

Continue with the [development guide](development.md) for local workflows, tests, and quality checks.
