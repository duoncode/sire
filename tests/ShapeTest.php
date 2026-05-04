<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\CoercerRegistry;
use Duon\Sire\Coercion;
use Duon\Sire\Contract\Coercer;
use Duon\Sire\Contract\Validator;
use Duon\Sire\Contract\ValidatorParser;
use Duon\Sire\Contract\Value;
use Duon\Sire\Extra;
use Duon\Sire\Failure;
use Duon\Sire\Review;
use Duon\Sire\Shape;
use Duon\Sire\Validation;
use Duon\Sire\ValidatorRegistry;
use Override;
use ValueError;

class ShapeTest extends TestCase
{
	public function testTypeInt(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->label('Age');
		$shape->add('count', 'int');

		$result = $shape->validate([
			'age' => 'old',
			'count' => '13',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be a whole number', $result->map()['age'][0]);
		$this->assertSame(13, $result->values()['count']);
		$this->assertSame('13', $result->pristineValues()['count']);
	}

	public function testCustomTypeMessage(): void
	{
		$shape = new Shape()->message(
			'type.int',
			'{label} ({field}) must be numeric, got {value}',
		);
		$shape->add('age', 'int')->label('Age');

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age (age) must be numeric, got old', $result->map()['age'][0]);
	}

	public function testCustomTypeMessages(): void
	{
		$shape = new Shape()->messages([
			'type.bool' => '%1$s must be yes or no',
		]);
		$shape->add('enabled', 'bool')->label('Enabled');

		$result = $shape->validate(['enabled' => 'maybe']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Enabled must be yes or no', $result->map()['enabled'][0]);
	}

	public function testCustomValidatorMessage(): void
	{
		$shape = new Shape()->message('validator.required', '{label} is mandatory');
		$shape->add('name', 'text', 'required')->label('Name');

		$result = $shape->validate(['name' => '']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Name is mandatory', $result->map()['name'][0]);
	}

	public function testCustomValidatorMessageWithArgs(): void
	{
		$shape = new Shape()->message('validator.min', '{label} must be at least {arg1}, got {value}');
		$shape->add('age', 'int', 'min:18')->label('Age');

		$result = $shape->validate(['age' => '12']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be at least 18, got 12', $result->map()['age'][0]);
	}

	public function testRuleTypeMessageOverridesShapeMessage(): void
	{
		$shape = new Shape()->message('type.int', 'Global int error');
		$shape->add('age', 'int')->message('type', 'Age must be a whole number');
		$shape->add('count', 'int');

		$result = $shape->validate([
			'age' => 'old',
			'count' => 'many',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be a whole number', $result->map()['age'][0]);
		$this->assertSame('Global int error', $result->map()['count'][0]);
	}

	public function testRuleValidatorMessageOverridesShapeMessage(): void
	{
		$shape = new Shape()->message('validator.max', 'Global max {arg1}');
		$shape->add('age', 'int', 'max:120')->message('max', 'Age must be at most {arg1}, got {value}');
		$shape->add('score', 'int', 'max:10');

		$result = $shape->validate([
			'age' => '130',
			'score' => '11',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be at most 120, got 130', $result->map()['age'][0]);
		$this->assertSame('Global max 10', $result->map()['score'][0]);
	}

	public function testRuleMessagesSupportExplicitKeys(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int', 'max:120')->messages([
			'type.int' => 'Age must be numeric',
			'validator.max' => 'Age must be at most {arg1}',
		]);

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be numeric', $result->map()['age'][0]);

		$result = $shape->validate(['age' => '121']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be at most 120', $result->map()['age'][0]);
	}

	public function testTypeFloat(): void
	{
		$shape = new Shape();
		$shape->add('price', 'float')->label('Price');
		$shape->add('ratio', 'float');

		$result = $shape->validate([
			'price' => 'old',
			'ratio' => '13.13',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Price must be a number', $result->map()['price'][0]);
		$this->assertSame(13.13, $result->values()['ratio']);
	}

	public function testTypeNumber(): void
	{
		$shape = new Shape();
		$shape->add('amount', 'number')->label('Amount');
		$shape->add('count', 'number');

		$result = $shape->validate([
			'amount' => 'old',
			'count' => '13',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Amount must be a number', $result->map()['amount'][0]);
		$this->assertSame(13, $result->values()['count']);
	}

	public function testTypeBoolean(): void
	{
		$shape = new Shape();
		$shape->add('enabled', 'bool')->label('Enabled');
		$shape->add('published', 'bool');
		$shape->add('archived', 'bool')->default(false);

		$result = $shape->validate([
			'enabled' => 'maybe',
			'published' => 'yes',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Enabled must be true or false', $result->map()['enabled'][0]);
		$this->assertSame(true, $result->values()['published']);
		$this->assertSame(false, $result->values()['archived']);
		$this->assertArrayNotHasKey('archived', $result->pristineValues());
	}

	public function testTypeText(): void
	{
		$shape = new Shape();
		$shape->add('title', 'text')->label('Title');
		$shape->add('description', 'text')->optional();

		$result = $shape->validate(['title' => true]);

		$this->assertTrue($result->isValid());
		$this->assertSame('1', $result->values()['title']);
		$this->assertArrayNotHasKey('description', $result->values());
		$this->assertArrayNotHasKey('description', $result->pristineValues());
	}

	public function testTypeSkipEmpty(): void
	{
		$testData = [
			'valid_text' => '',
		];

		$shape = new Shape();
		$shape->add('valid_text', 'text', 'maxlen');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
	}

	public function testTypeList(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->label('Items');
		$shape->add('tags', 'list');

		$result = $shape->validate([
			'items' => 'invalid',
			'tags' => [1, 2],
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Items must be a list', $result->map()['items'][0]);
		$this->assertSame([1, 2], $result->values()['tags']);
	}

	public function testWrongType(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Wrong shape type');

		$shape = new Shape();
		$shape->add('invalid_field', 'Invalid', 'invalid');
		$shape->validate(['invalid_field' => false]);
	}

	public function testUnknownValidator(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Unknown validator');

		$shape = new Shape();
		$shape->add('field', 'text', 'unknown');
		$shape->validate(['field' => 'value']);
	}

	public function testCustomValidatorRegistry(): void
	{
		$registry = ValidatorRegistry::withDefaults()->with(
			'starts_with',
			self::startsWithValidator(),
		);

		$shape = new Shape()->validators($registry);
		$shape->add('field', 'text', 'required', 'starts_with:foo');

		$result = $shape->validate(['field' => 'foobar']);
		$this->assertTrue($result->isValid());
		$result = $shape->validate(['field' => 'barfoo']);
		$this->assertFalse($result->isValid());
		$this->assertSame('Must start with foo', $result->errors()['map']['field'][0]);
		$result = $shape->validate(['field' => '']);
		$this->assertFalse($result->isValid());
		$this->assertSame('field is required', $result->errors()['map']['field'][0]);
	}

	public function testCustomValidatorParser(): void
	{
		$registry = new ValidatorRegistry([
			'starts_with' => self::startsWithValidator(),
		]);

		$parser = new class implements ValidatorParser {
			#[Override]
			/** @return array{name: string, args: list<string>} */
			public function parse(string $validatorDefinition): array
			{
				$parts = explode('|', $validatorDefinition);

				return [
					'name' => $parts[0],
					'args' => array_slice($parts, 1),
				];
			}
		};

		$shape = new Shape()
			->validators($registry)
			->validatorParser($parser);
		$shape->add('field', 'text', 'starts_with|foo');

		$result = $shape->validate(['field' => 'foobar']);
		$this->assertTrue($result->isValid());
		$result = $shape->validate(['field' => 'barfoo']);
		$this->assertFalse($result->isValid());
		$this->assertSame('Must start with foo', $result->errors()['map']['field'][0]);
	}

	public function testCustomCoercer(): void
	{
		$shape = new Shape()->type(
			'slug',
			new class implements Coercer {
				public string $message {
					get => 'Invalid slug';
				}

				#[Override]
				public function coerce(mixed $pristine): \Duon\Sire\Contract\Coercion
				{
					if (!is_string($pristine) || !preg_match('/^[a-z0-9-]+$/', $pristine)) {
						return new Coercion(
							$pristine,
							$pristine,
							Failure::invalid(),
						);
					}

					return new Coercion($pristine, $pristine);
				}
			},
		);
		$shape->add('slug', 'slug', 'required');

		$result = $shape->validate(['slug' => 'test-slug']);
		$this->assertTrue($result->isValid());
		$result = $shape->validate(['slug' => 'Not A Slug']);
		$this->assertFalse($result->isValid());
		$this->assertSame('Invalid slug', $result->errors()['map']['slug'][0]);
	}

	public function testCustomCoercerUsesTypeMessage(): void
	{
		$shape = new Shape()
			->message('type.slug', 'Custom slug error')
			->type(
				'slug',
				new class implements Coercer {
					public string $message {
						get => 'Invalid slug';
					}

					#[Override]
					public function coerce(mixed $pristine): \Duon\Sire\Contract\Coercion
					{
						return new Coercion(
							$pristine,
							$pristine,
							Failure::invalid(),
						);
					}
				},
			);
		$shape->add('slug', 'slug');

		$result = $shape->validate(['slug' => 'Not A Slug']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Custom slug error', $result->errors()['map']['slug'][0]);
	}

	public function testCustomCoercerRegistry(): void
	{
		$registry = new CoercerRegistry([
			'upper' => new class implements Coercer {
				public string $message {
					get => 'Invalid value';
				}

				#[Override]
				public function coerce(mixed $pristine): \Duon\Sire\Contract\Coercion
				{
					$value = is_string($pristine) ? strtoupper($pristine) : $pristine;

					return new Coercion($value, $pristine);
				}
			},
		]);

		$shape = new Shape()->types($registry);
		$shape->add('field', 'upper');

		$result = $shape->validate(['field' => 'value']);
		$this->assertTrue($result->isValid());
		$this->assertSame('VALUE', $result->values()['field']);
		$this->assertSame('value', $result->pristineValues()['field']);
	}

	public function testResult(): void
	{
		$shape = new Shape();
		$shape->add('email', 'text', 'required', 'email');

		$result = $shape->validate(['email' => 'invalid']);
		$this->assertFalse($result->isValid());
		$this->assertSame('email must be a valid email address', $result->map()['email'][0]);

		$violations = $result->violations();
		$this->assertCount(1, $violations);
		$this->assertSame('email', $violations[0]->field);
		$this->assertSame('email must be a valid email address', $violations[0]->error);

		$this->assertSame('invalid', $result->values()['email']);
		$this->assertSame('invalid', $result->pristineValues()['email']);
	}

	public function testResultBeforeValidation(): void
	{
		$shape = new Shape();

		$result = $shape->validate([]);
		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->violations());
		$this->assertSame([], $result->map());
		$this->assertSame([], $result->values());
		$this->assertSame([], $result->pristineValues());
	}

	public function testUnknownData(): void
	{
		$testData = [
			'unknown_1' => 'Test',
			'unknown_2' => '13',
			'unknown_3' => 'Unknown',
			'unknown_4' => '23',
		];

		$shape = new Shape();
		$shape->add('unknown_1', 'text');
		$shape->add('unknown_2', 'int');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->errors()['errors']);

		$values = $result->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertArrayNotHasKey('unknown_3', $values);

		$pristine = $result->pristineValues();
		$this->assertSame('Test', $pristine['unknown_1']);
		$this->assertSame('13', $pristine['unknown_2']);
		$this->assertArrayNotHasKey('unknown_3', $pristine);

		$shape = new Shape()->extra(Extra::Allow);
		$shape->add('unknown_1', 'text');
		$shape->add('unknown_2', 'int');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->errors()['errors']);

		$values = $result->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertSame('Unknown', $values['unknown_3']);
		$this->assertSame('23', $values['unknown_4']);

		$pristine = $result->pristineValues();
		$this->assertSame('Test', $pristine['unknown_1']);
		$this->assertSame('13', $pristine['unknown_2']);
		$this->assertSame('Unknown', $pristine['unknown_3']);
		$this->assertSame('23', $pristine['unknown_4']);
	}

	public function testForbidsExtraData(): void
	{
		$shape = new Shape()->extra(Extra::Forbid);
		$shape->add('name', 'text');

		$result = $shape->validate([
			'name' => 'Jane',
			'role' => 'admin',
		]);

		$this->assertFalse($result->isValid());
		$this->assertSame(['role' => ['Field "role" is not allowed']], $result->map());
		$this->assertSame(['name' => 'Jane'], $result->values());
		$this->assertSame(['name' => 'Jane'], $result->pristineValues());

		$violations = $result->violations();
		$this->assertSame('Field "role" is not allowed', $violations[0]->error);
		$this->assertSame('role', $violations[0]->field);
		$this->assertSame('role', $violations[0]->label);
	}

	public function testForbidExtraDataUsesConfiguredMessage(): void
	{
		$shape = new Shape()
			->extra('forbid')
			->message('extra', 'Unexpected {field}: {value}');
		$shape->add('name', 'text');

		$result = $shape->validate([
			'name' => 'Jane',
			'role' => 'admin',
		]);

		$this->assertSame(['role' => ['Unexpected role: admin']], $result->map());
	}

	public function testRejectsInvalidExtraMode(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Invalid extra mode "drop"');

		new Shape()->extra('drop');
	}

	public function testRequiredValidator(): void
	{
		$testData = [
			'valid_1' => 'value',
			'valid_2' => false,
			'valid_3' => 0,
			'valid_4' => 0.0,
			'valid_5' => [1],
			'invalid_3' => [],
			'invalid_4' => '',
		];

		$shape = new Shape();
		$shape->add('valid_1', 'text', 'required');
		$shape->add('valid_2', 'bool', 'required');
		$shape->add('valid_3', 'int', 'required');
		$shape->add('valid_4', 'float', 'required');
		$shape->add('valid_5', 'list', 'required');
		$shape->add('invalid_1', 'text', 'required');
		$shape->add('invalid_2', 'float', 'required')->label('Required 2');
		$shape->add('invalid_3', 'list', 'required');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(3, $errors['errors']);
		$this->assertSame('invalid_1 is required', $errors['map']['invalid_1'][0]);
		$this->assertSame('Required 2 is required', $errors['map']['invalid_2'][0]);
		$this->assertSame('invalid_3 is required', $errors['map']['invalid_3'][0]);
	}

	public function testEmailValidator(): void
	{
		$testData = [
			'valid_email' => 'valid@email.com',
			'invalid_email' => 'invalid@email',
		];

		$shape = new Shape();
		$shape->add('invalid_email', 'text', 'email')->label('Email');
		$shape->add('valid_email', 'text', 'email');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Email must be a valid email address', $errors['map']['invalid_email'][0]);
	}

	public function testEmailValidatorWithDnsCheck(): void
	{
		$testData = [
			'valid_email' => 'valid@gmail.com',
			'invalid_email' => 'invalid@test.tld',
		];

		$shape = new Shape();
		$shape->add('invalid_email', 'text', 'email:checkdns');
		$shape->add('valid_email', 'text', 'email:checkdns');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame(
			'invalid_email must be a valid email address',
			$errors['map']['invalid_email'][0],
		);
	}

	public function testMinValueValidator(): void
	{
		$testData = [
			'valid_1' => 13,
			'valid_2' => 13,
			'valid_3' => 10,
			'valid_4' => 10,
			'invalid_1' => 7,
			'invalid_2' => 7.13,
		];

		$shape = new Shape();
		$shape->add('valid_1', 'int', 'min:10');
		$shape->add('valid_2', 'float', 'min:10');
		$shape->add('valid_3', 'int', 'min:10');
		$shape->add('valid_4', 'float', 'min:10');
		$shape->add('invalid_1', 'int', 'min:10')->label('Min');
		$shape->add('invalid_2', 'float', 'min:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('Min must be at least 10', $errors['map']['invalid_1'][0]);
		$this->assertSame('invalid_2 must be at least 10', $errors['map']['invalid_2'][0]);
	}

	public function testMaxValueValidator(): void
	{
		$testData = [
			'valid_1' => 13,
			'valid_2' => 13,
			'valid_3' => 10,
			'valid_4' => 10,
			'invalid_1' => 23,
			'invalid_2' => 23.13,
		];

		$shape = new Shape();
		$shape->add('valid_1', 'int', 'max:13');
		$shape->add('valid_2', 'float', 'max:13');
		$shape->add('valid_3', 'int', 'max:13');
		$shape->add('valid_4', 'float', 'max:13');
		$shape->add('invalid_1', 'int', 'max:13');
		$shape->add('invalid_2', 'float', 'max:13')->label('Max');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('invalid_1 must be at most 13', $errors['map']['invalid_1'][0]);
		$this->assertSame('Max must be at most 13', $errors['map']['invalid_2'][0]);
	}

	public function testMinLengthValidator(): void
	{
		$testData = [
			'valid_1' => 'abcdefghijklm',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghi',
		];

		$shape = new Shape();
		$shape->add('valid_1', 'text', 'minlen:10');
		$shape->add('valid_2', 'text', 'minlen:10');
		$shape->add('invalid', 'text', 'minlen:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame(
			'invalid must be at least 10 characters',
			$errors['map']['invalid'][0],
		);
	}

	public function testMaxLengthValidator(): void
	{
		$testData = [
			'valid_1' => 'abcdefghi',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghiklm',
		];

		$shape = new Shape();
		$shape->add('valid_1', 'text', 'maxlen:10');
		$shape->add('valid_2', 'text', 'maxlen:10');
		$shape->add('invalid', 'text', 'maxlen:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame(
			'invalid must be at most 10 characters',
			$errors['map']['invalid'][0],
		);
	}

	public function testRegexValidator(): void
	{
		$testData = [
			'valid' => 'abcdefghi',
			'invalid' => 'abcdefghiklm',
			'valid_colon' => 'abcdef:ghi:klm:',
			'invalid_colon' => 'abcdef:ghi:klm',
		];

		$shape = new Shape();
		$shape->add('valid', 'text', 'regex:/^abcdefghi$/');
		$shape->add('invalid', 'text', 'regex:/^abcdefghi$/');
		$shape->add('valid_colon', 'text', 'regex:/^[a-z:]+:$/');
		$shape->add('invalid_colon', 'text', 'regex:/^[a-z:]+:$/');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('invalid has an invalid format', $errors['map']['invalid'][0]);
	}

	public function testInValidator(): void
	{
		$testData = [
			'valid1' => 'valid',
			'valid2' => 'alsovalid',
			'invalid' => 'invalid',
		];

		$shape = new Shape();
		$shape->add('valid1', 'text', 'in:valid,alsovalid');
		$shape->add('valid2', 'text', 'in:valid,alsovalid');
		$shape->add('invalid', 'text', 'in:valid,alsovalid');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('invalid must be an allowed value', $errors['map']['invalid'][0]);
	}

	public function testInValidatorWithQuotedAndEscapedValues(): void
	{
		$testData = [
			'quoted_comma' => 'ACME, Inc',
			'escaped_comma' => 'ACME, Inc',
			'quoted_colon' => 'http://',
			'invalid' => 'Nope',
		];

		$shape = new Shape();
		$shape->add('quoted_comma', 'text', 'in:"ACME, Inc",Globex');
		$shape->add('escaped_comma', 'text', 'in:ACME\\, Inc,Globex');
		$shape->add('quoted_colon', 'text', 'in:"http://","https://"');
		$shape->add('invalid', 'text', 'in:"ACME, Inc",Globex');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('invalid must be an allowed value', $errors['map']['invalid'][0]);
		$this->assertArrayNotHasKey('quoted_comma', $errors['map']);
		$this->assertArrayNotHasKey('escaped_comma', $errors['map']);
		$this->assertArrayNotHasKey('quoted_colon', $errors['map']);
	}

	public function testEscapedAndQuotedColonArgument(): void
	{
		$shape = new Shape()->validator(
			'starts_with',
			self::startsWithValidator(),
		);
		$shape->add('escaped', 'text', 'starts_with:http\\://');
		$shape->add('quoted', 'text', 'starts_with:"http://"');
		$shape->add('invalid', 'text', 'starts_with:http\\://');

		$result = $shape->validate([
			'escaped' => 'http://duon.de',
			'quoted' => 'http://duon.org',
			'invalid' => 'https://duon.de',
		]);

		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Must start with http://', $errors['map']['invalid'][0]);
	}

	public function testReviewCallbackAddsErrors(): void
	{
		$shape = new Shape()->title('Review Title');
		$shape->add('email', 'text', 'required')->label('Email');
		$shape->review(static function (Review $context): void {
			self::assertSame(['email' => 'taken@example.com'], $context->values());
			self::assertSame(['email' => 'taken@example.com'], $context->pristineValues());
			self::assertFalse($context->isList());
			self::assertSame('Review Title', $context->title());
			self::assertSame(3, $context->level());

			$context->addError('email', 'Email', 'Already used');
		});

		$result = $shape->validate(['email' => 'taken@example.com'], 3);

		$this->assertFalse($result->isValid());
		$this->assertSame('Already used', $result->map()['email'][0]);
		$this->assertSame('taken@example.com', $result->values()['email']);
		$this->assertSame('taken@example.com', $result->pristineValues()['email']);
		$this->assertSame('Review Title', $result->violations()[0]->title);
		$this->assertSame(3, $result->violations()[0]->level);
	}

	public function testAllReviewCallbacksRunOnceReviewStarts(): void
	{
		$shape = new Shape();
		$shape->review(static function (Review $context): void {
			$context->addError('first', 'First', 'First error');
		});
		$shape->review(static function (Review $context): void {
			$context->addError('second', 'Second', 'Second error');
		});

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('First error', $result->map()['first'][0]);
		$this->assertSame('Second error', $result->map()['second'][0]);
	}

	public function testReviewCallbacksDoNotRunAfterValidationErrors(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('email', 'text', 'required');
		$shape->review(static function (Review $_context) use (&$called): void {
			$called = true;
		});

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertFalse($called);
		$this->assertSame('email is required', $result->map()['email'][0]);
	}

	public function testRulePreparationRunsBeforeScalarValidation(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->prepare(static fn(mixed $_value): string => '42');

		$result = $shape->validate(['age' => 'not a number']);

		$this->assertTrue($result->isValid());
		$this->assertSame(42, $result->values()['age']);
		$this->assertSame('42', $result->pristineValues()['age']);
	}

	public function testRuleDefaultValueFillsMissingField(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->default('draft');
		$shape->add('count', 'int')->default('13');

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertSame('draft', $result->values()['status']);
		$this->assertSame(13, $result->values()['count']);
		$this->assertArrayNotHasKey('status', $result->pristineValues());
		$this->assertArrayNotHasKey('count', $result->pristineValues());
	}

	public function testRuleDefaultValueRunsBeforePreparation(): void
	{
		$shape = new Shape();
		$shape->add('title', 'text');
		$shape
			->add('slug', 'text')
			->default('')
			->prepare(
				static fn(mixed $value, array $data): string => $value !== ''
					? (string) $value
					: strtolower((string) $data['title']),
			);

		$result = $shape->validate(['title' => 'Hello']);

		$this->assertTrue($result->isValid());
		$this->assertSame('hello', $result->values()['slug']);
		$this->assertArrayNotHasKey('slug', $result->pristineValues());
	}

	public function testExplicitValueOverridesRuleDefault(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->default('draft');

		$result = $shape->validate(['status' => 'published']);

		$this->assertTrue($result->isValid());
		$this->assertSame('published', $result->values()['status']);
		$this->assertSame('published', $result->pristineValues()['status']);
	}

	public function testInvalidRuleDefaultAddsValidationError(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->label('Age')->default('old');

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Age must be a whole number', $result->map()['age'][0]);
		$this->assertSame('old', $result->values()['age']);
		$this->assertArrayNotHasKey('age', $result->pristineValues());
	}

	public function testInvalidNestedRuleDefaultAddsValidationError(): void
	{
		$nested = new Shape();
		$nested->add('email', 'text', 'email')->label('Email');

		$shape = new Shape();
		$shape->add('child', $nested)->default(['email' => 'invalid']);

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Email must be a valid email address', $result->map()['child']['email'][0]);
		$this->assertSame(['email' => 'invalid'], $result->values()['child']);
		$this->assertArrayNotHasKey('child', $result->pristineValues());
	}

	public function testNullableRuleAllowsNullValue(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->label('Items')->nullable();

		$result = $shape->validate(['items' => null]);

		$this->assertTrue($result->isValid());
		$this->assertNull($result->values()['items']);
		$this->assertNull($result->pristineValues()['items']);
	}

	public function testNonNullableRuleRejectsNullValue(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->label('Items');

		$result = $shape->validate(['items' => null]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Items must not be null', $result->map()['items'][0]);
	}

	public function testRuleNullMessageOverridesShapeMessage(): void
	{
		$shape = new Shape();
		$shape->message('null', 'Shape null');
		$shape->add('items', 'list')->label('Items')->message('null', '{label} cannot be null');

		$result = $shape->validate(['items' => null]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Items cannot be null', $result->map()['items'][0]);
	}

	public function testNullRuleDefaultImpliesNullable(): void
	{
		$shape = new Shape();
		$shape->add('note', 'text')->default(null);

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertNull($result->values()['note']);
		$this->assertArrayNotHasKey('note', $result->pristineValues());
	}

	public function testRequiredNarrowsNullRuleDefault(): void
	{
		$shape = new Shape();
		$shape->add('note', 'text', 'required')->default(null);

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('note is required', $result->map()['note'][0]);
	}

	public function testRulePreparationReceivesInputData(): void
	{
		$shape = new Shape();
		$shape->add('slug', 'text')->prepare(
			static fn(mixed $value, array $data): string => $value !== ''
				? (string) $value
				: strtolower((string) $data['title']),
		);

		$result = $shape->validate([
			'title' => 'Hello',
			'slug' => '',
		]);

		$this->assertTrue($result->isValid());
		$this->assertSame('hello', $result->values()['slug']);
	}

	public function testRulePreparationRunsBeforeNestedShapeValidation(): void
	{
		$nested = new Shape();
		$nested->add('name', 'text', 'required');

		$shape = new Shape();
		$shape
			->add('child', $nested)
			->prepare(static fn(mixed $value): mixed => $value ?? ['name' => 'Prepared']);

		$result = $shape->validate(['child' => null]);

		$this->assertTrue($result->isValid());
		$this->assertSame('Prepared', $result->values()['child']['name']);
		$this->assertSame(['name' => 'Prepared'], $result->pristineValues()['child']);
	}

	public function testRuleFinalizationRunsAfterValidation(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('count', 'int', 'min:2')
			->finalize(static function (mixed $value, array $values) use (&$called): int {
				$called = true;
				self::assertSame(2, $value);
				self::assertSame(['count' => 2, 'offset' => 3], $values);

				return $value + $values['offset'];
			});
		$shape->add('offset', 'int')->default('3');

		$result = $shape->validate(['count' => '2']);

		$this->assertTrue($result->isValid());
		$this->assertTrue($called);
		$this->assertSame(5, $result->values()['count']);
		$this->assertSame(3, $result->values()['offset']);
		$this->assertSame('2', $result->pristineValues()['count']);
		$this->assertArrayNotHasKey('offset', $result->pristineValues());
	}

	public function testRuleFinalizationRunsForDefaults(): void
	{
		$shape = new Shape();
		$shape->add('title', 'text');
		$shape
			->add('slug', 'text')
			->default('')
			->finalize(static fn(mixed $_value, array $values): string => strtolower(
				(string) $values['title'],
			));

		$result = $shape->validate(['title' => 'Hello']);

		$this->assertTrue($result->isValid());
		$this->assertSame('hello', $result->values()['slug']);
		$this->assertArrayNotHasKey('slug', $result->pristineValues());
	}

	public function testReviewCallbacksReceiveFinalizedValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('name', 'text')->finalize(
			static fn(mixed $value): string => strtoupper((string) $value),
		);
		$shape->review(static function (Review $context) use (&$called): void {
			$called = true;
			self::assertSame(['name' => 'ADA'], $context->values());
			self::assertSame(['name' => 'ada'], $context->pristineValues());
		});

		$result = $shape->validate(['name' => 'ada']);

		$this->assertTrue($result->isValid());
		$this->assertTrue($called);
		$this->assertSame('ADA', $result->values()['name']);
		$this->assertSame('ada', $result->pristineValues()['name']);
	}

	public function testRuleFinalizationDoesNotRunAfterValidationErrors(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('age', 'int')->finalize(static function (mixed $value) use (&$called): mixed {
			$called = true;

			return $value;
		});

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->isValid());
		$this->assertFalse($called);
	}

	public function testRuleFinalizationDoesNotRunForOmittedOptionalValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('subtitle', 'text')
			->optional()
			->finalize(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertFalse($called);
		$this->assertSame([], $result->values());
	}

	public function testRuleFinalizationReceivesListItemValues(): void
	{
		$seen = [];
		$shape = Shape::list();
		$shape->add('first', 'text');
		$shape->add('last', 'text')->finalize(static function (mixed $value, array $values) use (
			&$seen,
		): string {
			$seen[] = $values;

			return $values['first'] . ' ' . $value;
		});

		$result = $shape->validate([
			['first' => 'Ada', 'last' => 'Lovelace'],
			['first' => 'Grace', 'last' => 'Hopper'],
		]);

		$this->assertTrue($result->isValid());
		$this->assertSame('Ada Lovelace', $result->values()[0]['last']);
		$this->assertSame('Grace Hopper', $result->values()[1]['last']);
		$this->assertSame(
			[
				['first' => 'Ada', 'last' => 'Lovelace'],
				['first' => 'Grace', 'last' => 'Hopper'],
			],
			$seen,
		);
	}

	public function testOptionalRuleOmitsMissingValue(): void
	{
		$shape = new Shape();
		$shape->add('subtitle', 'text')->optional();

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertSame([], $result->values());
		$this->assertSame([], $result->pristineValues());
	}

	public function testOptionalRuleValidatesPresentValue(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->optional();

		$result = $shape->validate(['age' => '13']);

		$this->assertTrue($result->isValid());
		$this->assertSame(13, $result->values()['age']);
	}

	public function testMissingRuleAddsValidationError(): void
	{
		$shape = new Shape();
		$shape->add('title', 'text')->label('Title');

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Title is required', $result->map()['title'][0]);
		$this->assertSame([], $result->values());
		$this->assertSame([], $result->pristineValues());
	}

	public function testRuleMissingMessageOverridesShapeMessage(): void
	{
		$shape = new Shape();
		$shape->message('missing', 'Shape missing');
		$shape->add('title', 'text')->label('Title')->message('missing', '{label} is missing');

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Title is missing', $result->map()['title'][0]);
	}

	public function testRulePreparationDoesNotRunForMissingValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('missing', 'text')
			->optional()
			->prepare(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertFalse($called);
		$this->assertArrayNotHasKey('missing', $result->values());
	}

	public function testSubShape(): void
	{
		$testData = [
			'int' => 13,
			'text' => 'Text',
			'shape' => [
				'inner_int' => 23,
				'inner_email' => 'test@example.com',
			],
		];

		$shape = new Shape();
		$shape->add('int', 'int', 'required');
		$shape->add('text', 'text', 'required');
		$shape->add('shape', new SubShape())->label('Shape');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
	}

	public function testInvalidDataInSubShape(): void
	{
		$testData = [
			'int' => 13,
			'shape' => [
				'inner_int' => 23,
				'inner_email' => 'test INVALID example.com',
			],
		];

		$shape = new Shape();
		$shape->add('int', 'int', 'required');
		$shape->add('text', 'text', 'required');
		$shape->add('shape', new SubShape());

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('text is required', $errors['map']['text'][0]);
		$this->assertSame(
			'Email must be a valid email address',
			$errors['map']['shape']['inner_email'][0],
		);
	}

	public function testListShape(): void
	{
		$testData = [
			[
				'int' => 13,
				'text' => 'Text 1',
				'single_shape' => [
					'inner_int' => 23,
					'inner_email' => 'test@example.com',
				],
			],
			[
				'int' => 17,
				'text' => 'Text 2',
				'single_shape' => [
					'inner_int' => '31',
					'inner_email' => 'example@example.com',
				],
				'list_shape' => [
					[
						'inner_int' => '43',
						'inner_email' => 'example@example.com',
					],
					[
						'inner_int' => '47',
						'inner_email' => 'example@example.com',
					],
				],
			],
		];

		$shape = Shape::list();
		$shape->add('int', 'int', 'required');
		$shape->add('text', 'text', 'required');
		$shape->add('single_shape', new SubShape());
		$shape->add('list_shape', new SubShape(true))->optional();

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
		$values = $result->values();
		$this->assertSame(13, $values[0]['int']);
		$this->assertSame(23, $values[0]['single_shape']['inner_int']);
		$this->assertArrayNotHasKey('list_shape', $values[0]);
		$this->assertSame('Text 2', $values[1]['text']);
		$this->assertSame('example@example.com', $values[1]['single_shape']['inner_email']);
		$this->assertSame('example@example.com', $values[1]['list_shape'][0]['inner_email']);
		$this->assertSame(47, $values[1]['list_shape'][1]['inner_int']);

		$pristineValues = $result->pristineValues();
		$this->assertSame(13, $pristineValues[0]['int']);
		$this->assertSame(23, $pristineValues[0]['single_shape']['inner_int']);
		$this->assertArrayNotHasKey('list_shape', $pristineValues[0]);
		$this->assertSame('Text 2', $pristineValues[1]['text']);
		$this->assertSame('example@example.com', $pristineValues[1]['single_shape']['inner_email']);
		$this->assertSame('example@example.com', $pristineValues[1]['list_shape'][0]['inner_email']);
		$this->assertSame('47', $pristineValues[1]['list_shape'][1]['inner_int']);
	}

	public function testInvalidListShape(): void
	{
		$testData = $this->getListData();
		$shape = $this->getListShape();

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(5, $errors);
		$this->assertSame('text is required', $errors['map'][0]['text'][0]);
		$this->assertSame('Int is required', $errors['map'][0]['single_shape']['inner_int'][0]);
		$this->assertSame('Single Shape is required', $errors['map'][1]['single_shape'][0]);
		$this->assertSame(
			'Email must be a valid email address',
			$errors['map'][3]['single_shape']['inner_email'][0],
		);
		$this->assertSame(
			'Int must be a whole number',
			$errors['map'][3]['list_shape'][0]['inner_int'][0],
		);
		$this->assertSame(
			'Email must be a valid email address',
			$errors['map'][3]['list_shape'][2]['inner_email'][0],
		);
	}

	public function testGroupedErrors(): void
	{
		$testData = $this->getListData();
		$shape = $this->getListShape();

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$groups = $result->errors(grouped: true)['errors'];
		$this->assertCount(3, $groups);
		$this->assertSame('List Root', $groups[0]['title']);
		$this->assertSame('email must be a valid email address', $groups[0]['errors'][3]['error']);
		$this->assertSame('List Sub', $groups[1]['title']);
		$this->assertSame('Int must be a whole number', $groups[1]['errors'][0]['error']);
		$this->assertSame('Single Sub', $groups[2]['title']);
		$this->assertSame('Email must be a valid email address', $groups[2]['errors'][1]['error']);
	}

	public function testEmptyFieldName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('must not be empty');

		$shape = new Shape();
		$shape->add('', 'Int', 'int');
	}

	public function testEmptyArraySkipsRegularValidator(): void
	{
		$testData = [
			'items' => [],
		];

		$shape = new Shape();
		$shape->add('items', 'list', 'in:a,b,c');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
	}

	private static function startsWithValidator(): Validator
	{
		return new class implements Validator {
			public string $message {
				get => 'Must start with %4$s';
			}

			#[Override]
			public function validate(Value $value, string ...$args): \Duon\Sire\Contract\Validation
			{
				$prefix = $args[0] ?? '';

				return Validation::from(str_starts_with((string) $value->value, $prefix));
			}
		};
	}

	public function testEmptyRegexPatternFails(): void
	{
		$testData = [
			'text' => 'test',
		];

		$shape = new Shape();
		// Regex validator without a pattern (just 'regex' with no argument)
		$shape->add('text', 'text', 'regex');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('text has an invalid format', $errors['map']['text'][0]);
	}
}
