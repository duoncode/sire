<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\CoercerRegistry;
use Duon\Sire\Contract\Coercer;
use Duon\Sire\Contract\Validator;
use Duon\Sire\Contract\ValidatorParser;
use Duon\Sire\Contract\Value;
use Duon\Sire\Result;
use Duon\Sire\Review;
use Duon\Sire\Shape;
use Duon\Sire\ValidatorRegistry;
use Duon\Sire\Violation;
use Override;
use ValueError;

class ShapeTest extends TestCase
{
	public function testTypeInt(): void
	{
		$testData = [
			'valid_int_1' => '13',
			'valid_int_2' => 13,
			'invalid_int_1' => '23invalid',
			'invalid_int_2' => '23.23',
		];

		$shape = new Shape();
		$shape->add('invalid_int_1', 'int')->label('Int 1');
		$shape->add('invalid_int_2', 'int');
		$shape->add('valid_int_1', 'int')->label('Int');
		$shape->add('valid_int_2', 'int')->label('Int');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertSame('Invalid number', $errors['errors'][0]['error']);
		$this->assertSame('invalid_int_1', $errors['errors'][0]['field']);
		$this->assertSame('Int 1', $errors['errors'][0]['label']);
		$this->assertSame('Invalid number', $errors['errors'][1]['error']);
		$this->assertSame('invalid_int_2', $errors['errors'][1]['field']);
		$this->assertSame('invalid_int_2', $errors['errors'][1]['label']);
		$this->assertSame('Invalid number', $errors['map']['invalid_int_1'][0]);
		$this->assertSame('Invalid number', $errors['map']['invalid_int_2'][0]);
		$this->assertArrayNotHasKey('valid_int_1', $errors['map']);
		$this->assertArrayNotHasKey('valid_int_2', $errors['map']);

		$values = $result->values();
		$this->assertSame(13, $values['valid_int_1']);
		$this->assertSame(13, $values['valid_int_2']);
		$this->assertSame('23invalid', $values['invalid_int_1']);

		$pristine = $result->pristineValues();
		$this->assertSame('13', $pristine['valid_int_1']);
		$this->assertSame(13, $pristine['valid_int_2']);
	}

	public function testTypeFloat(): void
	{
		$testData = [
			'valid_float_1' => '13',
			'valid_float_2' => '13.13',
			'valid_float_3' => 13,
			'valid_float_4' => 13.13,
			'invalid_float' => '23.23invalid',
		];

		$shape = new Shape();
		$shape->add('invalid_float', 'float')->label('Float');
		$shape->add('valid_float_1', 'float');
		$shape->add('valid_float_2', 'float');
		$shape->add('valid_float_3', 'float');
		$shape->add('valid_float_4', 'float');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertSame('Invalid number', $errors['errors'][0]['error']);
		$this->assertSame('Invalid number', $errors['map']['invalid_float'][0]);
		$this->assertArrayNotHasKey('valid_float_1', $errors['map']);
		$this->assertArrayNotHasKey('valid_float_2', $errors['map']);
		$this->assertArrayNotHasKey('valid_float_3', $errors['map']);
		$this->assertArrayNotHasKey('valid_float_4', $errors['map']);
	}

	public function testTypeBoolean(): void
	{
		$testData = [
			'valid_bool_1' => true,
			'valid_bool_2' => false,
			'valid_bool_3' => 'yes',
			'valid_bool_4' => 'off',
			'valid_bool_5' => 'true',
			'valid_bool_6' => 'null',
			'valid_bool_8' => null,
			'invalid_bool_1' => 'invalid',
			'invalid_bool_2' => 13,
		];

		$shape = new Shape();
		$shape->add('valid_bool_1', 'bool');
		$shape->add('valid_bool_2', 'bool');
		$shape->add('valid_bool_3', 'bool');
		$shape->add('valid_bool_4', 'bool');
		$shape->add('valid_bool_5', 'bool');
		$shape->add('valid_bool_6', 'bool');
		$shape->add('valid_bool_7', 'bool');
		$shape->add('valid_bool_8', 'bool');
		$shape->add('invalid_bool_1', 'bool')->label('Bool 1');
		$shape->add('invalid_bool_2', 'bool');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertSame('Invalid boolean', $errors['errors'][0]['error']);
		$this->assertSame('Invalid boolean', $errors['errors'][1]['error']);
		$this->assertSame('Invalid boolean', $errors['map']['invalid_bool_1'][0]);
		$this->assertSame('Invalid boolean', $errors['map']['invalid_bool_2'][0]);
		$this->assertArrayNotHasKey('valid_bool_1', $errors['map']);
		$this->assertArrayNotHasKey('valid_bool_2', $errors['map']);

		$values = $result->values();
		$this->assertSame(true, $values['valid_bool_1']);
		$this->assertSame(false, $values['valid_bool_2']);
		$this->assertSame(true, $values['valid_bool_3']);
		$this->assertSame(false, $values['valid_bool_4']);
		$this->assertSame(true, $values['valid_bool_5']);
		$this->assertSame(false, $values['valid_bool_6']);
		$this->assertSame(false, $values['valid_bool_7']);
		$this->assertSame(false, $values['valid_bool_8']);

		$pristine = $result->pristineValues();
		$this->assertSame('yes', $pristine['valid_bool_3']);
		$this->assertSame('invalid', $pristine['invalid_bool_1']);
		$this->assertSame(13, $pristine['invalid_bool_2']);
	}

	public function testTypeText(): void
	{
		$testData = [
			'valid_text_1' => 'Lorem ipsum',
			'valid_text_2' => false,
			'valid_text_3' => true,
			'valid_text_4' => '<a href="/test">Test</a>',
		];

		$shape = new Shape();
		$shape->add('valid_text_1', 'text')->label('Text');
		$shape->add('valid_text_2', 'text')->label('Text');
		$shape->add('valid_text_3', 'text')->label('Text');
		$shape->add('valid_text_4', 'text');
		$shape->add('valid_text_5', 'text');

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->errors()['errors']);

		$values = $result->values();

		$this->assertSame('Lorem ipsum', $values['valid_text_1']);
		$this->assertNull($values['valid_text_2']);
		$this->assertSame('1', $values['valid_text_3']);
		$this->assertSame('<a href="/test">Test</a>', $values['valid_text_4']);
		$this->assertNull($values['valid_text_5']);

		$pristine = $result->pristineValues();
		$this->assertSame(false, $pristine['valid_text_2']);
		$this->assertNull($pristine['valid_text_5']);
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
		$testData = [
			'valid_list_1' => [1, 2],
			'valid_list_2' => [['key' => 'data']],
			'invalid_list_1' => 'invalid',
			'invalid_list_2' => 13,
		];

		$shape = new Shape();
		$shape->add('valid_list_1', 'list');
		$shape->add('valid_list_2', 'list');
		$shape->add('invalid_list_1', 'list')->label('List 1');
		$shape->add('invalid_list_2', 'list');

		$result = $shape->validate($testData);
		$this->assertFalse($result->isValid());
		$errors = $result->errors();
		$this->assertSame('Invalid list', $errors['errors'][0]['error']);
		$this->assertSame('Invalid list', $errors['errors'][1]['error']);
		$this->assertSame('Invalid list', $errors['map']['invalid_list_1'][0]);
		$this->assertSame('Invalid list', $errors['map']['invalid_list_2'][0]);
		$this->assertArrayNotHasKey('valid_list_1', $errors['map']);
		$this->assertArrayNotHasKey('valid_list_2', $errors['map']);

		$values = $result->values();
		$this->assertSame([1, 2], $values['valid_list_1']);
		$this->assertSame([['key' => 'data']], $values['valid_list_2']);

		$pristine = $result->pristineValues();
		$this->assertSame([1, 2], $pristine['valid_list_1']);
		$this->assertSame('invalid', $pristine['invalid_list_1']);
		$this->assertSame(13, $pristine['invalid_list_2']);
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
		$this->assertSame('Required', $result->errors()['map']['field'][0]);
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
				#[Override]
				public function coerce(mixed $pristine, string $label): Value
				{
					if (!is_string($pristine) || !preg_match('/^[a-z0-9-]+$/', $pristine)) {
						return new \Duon\Sire\Value($pristine, $pristine, 'Invalid slug');
					}

					return new \Duon\Sire\Value($pristine, $pristine);
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

	public function testResult(): void
	{
		$shape = new Shape();
		$shape->add('email', 'text', 'required', 'email');

		$result = $shape->validate(['email' => 'invalid']);
		$this->assertInstanceOf(Result::class, $result);
		$this->assertFalse($result->isValid());
		$this->assertSame('Invalid email address', $result->map()['email'][0]);

		$violations = $result->violations();
		$this->assertCount(1, $violations);
		$this->assertInstanceOf(Violation::class, $violations[0]);
		$this->assertSame('email', $violations[0]->field);
		$this->assertSame('Invalid email address', $violations[0]->error);

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

	public function testWrongErrorType(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Wrong error type');

		$registry = new CoercerRegistry([
			'text' => new class implements Coercer {
				#[Override]
				public function coerce(mixed $pristine, string $label): Value
				{
					return new \Duon\Sire\Value(
						$pristine,
						$pristine,
						['not', 'a', 'string'],
					);
				}
			},
		]);

		$shape = new Shape()->types($registry);
		$shape->add('field', 'text');
		$shape->validate(['field' => 'value']);
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

		$shape = new Shape()->keepUnknown();
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
		$this->assertSame('Required', $errors['map']['invalid_1'][0]);
		$this->assertSame('Required', $errors['map']['invalid_2'][0]);
		$this->assertSame('Required', $errors['map']['invalid_3'][0]);
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
		$this->assertSame('Invalid email address', $errors['map']['invalid_email'][0]);
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
		$this->assertSame('Invalid email address', $errors['map']['invalid_email'][0]);
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
		$this->assertSame('Lower than the required minimum of 10', $errors['map']['invalid_1'][0]);
		$this->assertSame('Lower than the required minimum of 10', $errors['map']['invalid_2'][0]);
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
		$this->assertSame('Higher than the allowed maximum of 13', $errors['map']['invalid_1'][0]);
		$this->assertSame('Higher than the allowed maximum of 13', $errors['map']['invalid_2'][0]);
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
			'Shorter than the minimum length of 10 characters',
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
			'Exeeds the maximum length of 10 characters',
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
		$this->assertSame('Does not match the required pattern', $errors['map']['invalid'][0]);
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
		$this->assertSame('Invalid value', $errors['map']['invalid'][0]);
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
		$this->assertSame('Invalid value', $errors['map']['invalid'][0]);
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
		$this->assertSame('Required', $result->map()['email'][0]);
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

	public function testRulePreparationDoesNotRunForMissingValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('missing', 'text')->prepare(static function (mixed $value) use (&$called): mixed {
			$called = true;

			return $value;
		});

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertFalse($called);
		$this->assertNull($result->values()['missing']);
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
		$this->assertSame('Required', $errors['map']['text'][0]);
		$this->assertSame('Invalid email address', $errors['map']['shape']['inner_email'][0]);
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
		$shape->add('list_shape', new SubShape(true));

		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
		$values = $result->values();
		$this->assertSame(13, $values[0]['int']);
		$this->assertSame(23, $values[0]['single_shape']['inner_int']);
		$this->assertNull($values[0]['list_shape']);
		$this->assertSame('Text 2', $values[1]['text']);
		$this->assertSame('example@example.com', $values[1]['single_shape']['inner_email']);
		$this->assertSame('example@example.com', $values[1]['list_shape'][0]['inner_email']);
		$this->assertSame(47, $values[1]['list_shape'][1]['inner_int']);

		$pristineValues = $result->pristineValues();
		$this->assertSame(13, $pristineValues[0]['int']);
		$this->assertSame(23, $pristineValues[0]['single_shape']['inner_int']);
		$this->assertNull($pristineValues[0]['list_shape']);
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
		$this->assertSame('Required', $errors['map'][0]['text'][0]);
		$this->assertSame('Required', $errors['map'][0]['single_shape']['inner_int'][0]);
		$this->assertSame('Required', $errors['map'][1]['single_shape'][0]);
		$this->assertSame('Invalid email address', $errors['map'][3]['single_shape']['inner_email'][0]);
		$this->assertSame('Invalid number', $errors['map'][3]['list_shape'][0]['inner_int'][0]);
		$this->assertSame('Invalid email address', $errors['map'][3]['list_shape'][2]['inner_email'][0]);
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
		$this->assertSame('Invalid email address', $groups[0]['errors'][2]['error']);
		$this->assertSame('List Sub', $groups[1]['title']);
		$this->assertSame('Invalid number', $groups[1]['errors'][0]['error']);
		$this->assertSame('Single Sub', $groups[2]['title']);
		$this->assertSame('Invalid email address', $groups[2]['errors'][1]['error']);
	}

	public function testEmptyFieldName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('must not be empty');

		$shape = new Shape();
		$shape->add('', 'Int', 'int');
	}

	public function testEmptyArraySkipsValidatorWithSkipEmpty(): void
	{
		$testData = [
			'items' => [],
		];

		$shape = new Shape();
		// Using 'in' validator which has skipEmpty=true
		$shape->add('items', 'list', 'in:a,b,c');

		// Empty array should skip the 'in' validator (which has skipEmpty=true)
		// and not produce an error
		$result = $shape->validate($testData);
		$this->assertTrue($result->isValid());
	}

	private static function startsWithValidator(): Validator
	{
		return new class implements Validator {
			public string $message = 'Must start with %4$s';

			public bool $skipEmpty = true;

			#[Override]
			public function validate(Value $value, string ...$args): bool
			{
				$prefix = $args[0] ?? '';

				return str_starts_with((string) $value->value, $prefix);
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
		$this->assertSame('Does not match the required pattern', $errors['map']['text'][0]);
	}
}
