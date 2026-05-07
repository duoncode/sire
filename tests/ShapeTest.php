<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\CoercerRegistry;
use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract\Coercer;
use Duon\Sire\Contract\Parser;
use Duon\Sire\Contract\Rule;
use Duon\Sire\Contract\RuleParser;
use Duon\Sire\Contract\Value;
use Duon\Sire\Exception\ValidationError;
use Duon\Sire\Extra;
use Duon\Sire\Failure;
use Duon\Sire\Review;
use Duon\Sire\RuleRegistry;
use Duon\Sire\Shape;
use Duon\Sire\Validation;
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

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be a whole number', $result->first('age'));
		$this->assertSame(13, $result->values()['count']);
	}

	public function testCustomTypeMessage(): void
	{
		$shape = new Shape()->message(
			'type.int',
			'{label} ({field}) must be numeric, got {value}',
		);
		$shape->add('age', 'int')->label('Age');

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->valid());
		$this->assertSame('Age (age) must be numeric, got old', $result->first('age'));
	}

	public function testCustomTypeMessages(): void
	{
		$shape = new Shape()->messages([
			'type.bool' => '%1$s must be yes or no',
		]);
		$shape->add('enabled', 'bool')->label('Enabled');

		$result = $shape->validate(['enabled' => 'maybe']);

		$this->assertFalse($result->valid());
		$this->assertSame('Enabled must be yes or no', $result->first('enabled'));
	}

	public function testCustomRuleMessage(): void
	{
		$shape = new Shape()->message('rule.required', '{label} is mandatory');
		$shape->add('name', 'string')->rules('required')->label('Name');

		$result = $shape->validate(['name' => '']);

		$this->assertFalse($result->valid());
		$this->assertSame('Name is mandatory', $result->first('name'));
	}

	public function testCustomRuleMessageWithArgs(): void
	{
		$shape = new Shape()->message('rule.min', '{label} must be at least {arg1}, got {value}');
		$shape->add('age', 'int')->rules('min:18')->label('Age');

		$result = $shape->validate(['age' => '12']);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be at least 18, got 12', $result->first('age'));
	}

	public function testFieldRulesAppend(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->rules('min:18')->rules('max:65');

		$result = $shape->validate(['age' => '70']);

		$this->assertFalse($result->valid());
		$this->assertSame('age must be at most 65', $result->first('age'));
	}

	public function testFieldTypeMessageOverridesShapeMessage(): void
	{
		$shape = new Shape()->message('type.int', 'Global int error');
		$shape->add('age', 'int')->message('type', 'Age must be a whole number');
		$shape->add('count', 'int');

		$result = $shape->validate([
			'age' => 'old',
			'count' => 'many',
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be a whole number', $result->first('age'));
		$this->assertSame('Global int error', $result->first('count'));
	}

	public function testFieldRuleMessageOverridesShapeMessage(): void
	{
		$shape = new Shape()->message('rule.max', 'Global max {arg1}');
		$shape->add('age', 'int')->rules('max:120')->message(
			'max',
			'Age must be at most {arg1}, got {value}',
		);
		$shape->add('score', 'int')->rules('max:10');

		$result = $shape->validate([
			'age' => '130',
			'score' => '11',
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be at most 120, got 130', $result->first('age'));
		$this->assertSame('Global max 10', $result->first('score'));
	}

	public function testFieldMessagesSupportExplicitKeys(): void
	{
		$shape = new Shape();
		$shape
			->add('age', 'int')
			->rules('max:120')
			->messages([
				'type.int' => 'Age must be numeric',
				'rule.max' => 'Age must be at most {arg1}',
			]);

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be numeric', $result->first('age'));

		$result = $shape->validate(['age' => '121']);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be at most 120', $result->first('age'));
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

		$this->assertFalse($result->valid());
		$this->assertSame('Price must be a number', $result->first('price'));
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

		$this->assertFalse($result->valid());
		$this->assertSame('Amount must be a number', $result->first('amount'));
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
			'published' => true,
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('Enabled must be true or false', $result->first('enabled'));
		$this->assertSame(true, $result->values()['published']);
		$this->assertSame(false, $result->values()['archived']);
	}

	public function testBooleanCoercesFormValues(): void
	{
		$shape = new Shape();
		$shape->add('enabled', 'bool');
		$shape->add('published', 'bool')->default(false);
		$shape->add('visible', 'bool');

		$result = $shape->validate([
			'enabled' => 'on',
			'visible' => ' FALSE ',
		]);

		$this->assertTrue($result->valid());
		$this->assertSame(true, $result->values()['enabled']);
		$this->assertSame(false, $result->values()['published']);
		$this->assertSame(false, $result->values()['visible']);
	}

	public function testFieldStrictModeRejectsCoercibleValues(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->strict()->label('Age');
		$shape->add('count', 'int');

		$result = $shape->validate([
			'age' => '13',
			'count' => '13',
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be a whole number', $result->first('age'));
		$this->assertSame('13', $result->values()['age']);
		$this->assertSame(13, $result->values()['count']);
	}

	public function testShapeStrictModeCanBeOverriddenByFieldCoerce(): void
	{
		$shape = new Shape()->strict();
		$shape->add('age', 'int');
		$shape->add('count', 'int')->coerce();
		$shape->add('price', 'float');
		$shape->add('amount', 'number');
		$shape->add('enabled', 'bool')->coerce();

		$result = $shape->validate([
			'age' => '13',
			'count' => '13',
			'price' => 13,
			'amount' => 13,
			'enabled' => 'on',
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('age must be a whole number', $result->first('age'));
		$this->assertSame('price must be a number', $result->first('price'));
		$this->assertSame('13', $result->values()['age']);
		$this->assertSame(13, $result->values()['count']);
		$this->assertSame(13, $result->values()['price']);
		$this->assertSame(13, $result->values()['amount']);
		$this->assertSame(true, $result->values()['enabled']);
	}

	public function testShapeCoerceResetsShapeStrictMode(): void
	{
		$shape = new Shape()->strict()->coerce();
		$shape->add('age', 'int');

		$result = $shape->validate(['age' => '13']);

		$this->assertTrue($result->valid());
		$this->assertSame(13, $result->values()['age']);
	}

	public function testStrictFieldUsesPreparedValue(): void
	{
		$shape = new Shape();
		$shape
			->add('age', 'int')
			->strict()
			->prepare(static fn(mixed $value): int => (int) $value);

		$result = $shape->validate(['age' => '13']);

		$this->assertTrue($result->valid());
		$this->assertSame(13, $result->values()['age']);
	}

	public function testTypeString(): void
	{
		$shape = new Shape();
		$shape->add('title', 'string')->label('Title');
		$shape->add('code', 'string')->rules('required');
		$shape->add('invalid', 'string')->optional();
		$shape->add('description', 'string')->optional();

		$result = $shape->validate([
			'title' => 13,
			'code' => '0',
			'invalid' => true,
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('invalid must be a string', $result->first('invalid'));
		$this->assertSame('13', $result->values()['title']);
		$this->assertSame('0', $result->values()['code']);
		$this->assertArrayNotHasKey('description', $result->values());
	}

	public function testTypeSkipEmpty(): void
	{
		$testData = [
			'valid_text' => '',
		];

		$shape = new Shape();
		$shape->add('valid_text', 'string')->rules('maxlen');

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
	}

	public function testInvalidTypeSkipsFieldRules(): void
	{
		$shape = new Shape();
		$shape->add('blank_age', 'int')->rules('required');
		$shape->add('old_age', 'int')->rules('min:18');

		$result = $shape->validate([
			'blank_age' => '',
			'old_age' => 'old',
		]);

		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame('blank_age must be a whole number', $result->first('blank_age'));
		$this->assertSame('old_age must be a whole number', $result->first('old_age'));
		$this->assertSame(
			['type.int', 'type.int'],
			array_map(static fn($issue): string => $issue->code, $result->issues()),
		);
	}

	public function testCustomRuleDoesNotRunAfterTypeFailure(): void
	{
		$rule = new class implements Rule {
			public bool $called = false;

			public string $message {
				get => 'Should not run';
			}

			#[Override]
			public function validate(Value $value, string ...$args): \Duon\Sire\Contract\Validation
			{
				$this->called = true;

				return Validation::invalid();
			}
		};

		$shape = new Shape();
		$shape->rule('tracked', $rule);
		$shape->add('age', 'int')->rules('tracked');

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->valid());
		$this->assertFalse($rule->called);
		$this->assertCount(1, $result->issues());
		$this->assertSame('age must be a whole number', $result->first('age'));
	}

	public function testParentRulesDoNotRunAfterNestedValidationFailure(): void
	{
		$rule = new class implements Rule {
			public bool $called = false;

			public string $message {
				get => 'Should not run';
			}

			#[Override]
			public function validate(Value $value, string ...$args): \Duon\Sire\Contract\Validation
			{
				$this->called = true;

				return Validation::invalid();
			}
		};

		$nested = new Shape();
		$nested->add('email', 'string')->rules('email');

		$shape = new Shape();
		$shape->rule('tracked', $rule);
		$shape->add('child', $nested)->rules('tracked');

		$result = $shape->validate(['child' => ['email' => 'invalid']]);

		$this->assertFalse($result->valid());
		$this->assertFalse($rule->called);
		$this->assertCount(1, $result->issues());
		$this->assertSame('email must be a valid email address', $result->first('child.email'));
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

		$this->assertFalse($result->valid());
		$this->assertSame('Items must be a list', $result->first('items'));
		$this->assertSame([1, 2], $result->values()['tags']);
	}

	public function testWrongType(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Wrong shape type');

		$shape = new Shape();
		$shape->add('invalid_field', 'Invalid')->rules('invalid');
		$shape->validate(['invalid_field' => false]);
	}

	public function testUnknownRule(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Unknown rule');

		$shape = new Shape();
		$shape->add('field', 'string')->rules('unknown');
		$shape->validate(['field' => 'value']);
	}

	public function testCustomRuleRegistry(): void
	{
		$registry = RuleRegistry::withDefaults()->with(
			'starts_with',
			self::startsWithRule(),
		);

		$shape = new Shape()->rules($registry);
		$shape->add('field', 'string')->rules('required', 'starts_with:foo');

		$result = $shape->validate(['field' => 'foobar']);
		$this->assertTrue($result->valid());
		$result = $shape->validate(['field' => 'barfoo']);
		$this->assertFalse($result->valid());
		$this->assertSame('Must start with foo', $result->first('field'));
		$result = $shape->validate(['field' => '']);
		$this->assertFalse($result->valid());
		$this->assertSame('field is required', $result->first('field'));
	}

	public function testCustomRuleParser(): void
	{
		$registry = new RuleRegistry([
			'starts_with' => self::startsWithRule(),
		]);

		$parser = new class implements RuleParser {
			#[Override]
			/** @return array{name: string, args: list<string>} */
			public function parse(string $ruleDefinition): array
			{
				$parts = explode('|', $ruleDefinition);

				return [
					'name' => $parts[0],
					'args' => array_slice($parts, 1),
				];
			}
		};

		$shape = new Shape()
			->rules($registry)
			->ruleParser($parser);
		$shape->add('field', 'string')->rules('starts_with|foo');

		$result = $shape->validate(['field' => 'foobar']);
		$this->assertTrue($result->valid());
		$result = $shape->validate(['field' => 'barfoo']);
		$this->assertFalse($result->valid());
		$this->assertSame('Must start with foo', $result->first('field'));
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
				public function coerce(
					mixed $pristine,
					CoercionMode $mode,
				): \Duon\Sire\Contract\Coercion {
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
		$shape->add('slug', 'slug')->rules('required');

		$result = $shape->validate(['slug' => 'test-slug']);
		$this->assertTrue($result->valid());
		$result = $shape->validate(['slug' => 'Not A Slug']);
		$this->assertFalse($result->valid());
		$this->assertSame('Invalid slug', $result->first('slug'));
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
					public function coerce(
						mixed $pristine,
						CoercionMode $mode,
					): \Duon\Sire\Contract\Coercion {
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

		$this->assertFalse($result->valid());
		$this->assertSame('Custom slug error', $result->first('slug'));
	}

	public function testCustomCoercerRegistry(): void
	{
		$registry = new CoercerRegistry([
			'upper' => new class implements Coercer {
				public string $message {
					get => 'Invalid value';
				}

				#[Override]
				public function coerce(
					mixed $pristine,
					CoercionMode $mode,
				): \Duon\Sire\Contract\Coercion {
					$value = is_string($pristine) ? strtoupper($pristine) : $pristine;

					return new Coercion($value, $pristine);
				}
			},
		]);

		$shape = new Shape()->types($registry);
		$shape->add('field', 'upper');

		$result = $shape->validate(['field' => 'value']);
		$this->assertTrue($result->valid());
		$this->assertSame('VALUE', $result->values()['field']);
	}

	public function testResult(): void
	{
		$shape = new Shape();
		$shape->add('email', 'string')->rules('required', 'email');

		$result = $shape->validate(['email' => 'invalid']);
		$this->assertFalse($result->valid());
		$this->assertSame('email must be a valid email address', $result->first('email'));

		$issues = $result->issues();
		$this->assertCount(1, $issues);
		$this->assertSame(['email'], $issues[0]->path);
		$this->assertSame('rule.email', $issues[0]->code);
		$this->assertSame('email must be a valid email address', $issues[0]->message);

		$this->assertSame('invalid', $result->values()['email']);
	}

	public function testParseReturnsValidatedValues(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->label('Age');
		$shape
			->add('status', 'string')
			->default('draft')
			->finalize(
				static fn(mixed $value): string => strtoupper((string) $value),
			);

		$this->assertInstanceOf(Parser::class, $shape);
		$this->assertInstanceOf(\Duon\Sire\Contract\Validator::class, $shape);
		$this->assertSame(
			['age' => 21, 'status' => 'DRAFT'],
			$shape->parse(['age' => '21']),
		);
	}

	public function testParseThrowsValidationErrorForInvalidData(): void
	{
		$shape = new Shape();
		$shape->add('email', 'string')->rules('required', 'email');

		try {
			$shape->parse(['email' => 'invalid']);
			$this->fail('Expected validation error');
		} catch (ValidationError $error) {
			$result = $error->result();

			$this->assertSame('Validation failed', $error->getMessage());
			$this->assertFalse($result->valid());
			$this->assertSame('email must be a valid email address', $result->first('email'));
			$this->assertSame('invalid', $result->values()['email']);
		}
	}

	public function testParseThrowsValidationErrorForReviewErrors(): void
	{
		$shape = new Shape();
		$shape->add('email', 'string')->rules('required', 'email');
		$shape->review(static function (Review $review): void {
			$review->addError('email', 'Already used', 'email.taken');
		});

		try {
			$shape->parse(['email' => 'taken@example.com']);
			$this->fail('Expected validation error');
		} catch (ValidationError $error) {
			$result = $error->result();

			$this->assertFalse($result->valid());
			$this->assertSame('Already used', $result->first('email'));
			$this->assertSame('taken@example.com', $result->values()['email']);
		}
	}

	public function testResultBeforeValidation(): void
	{
		$shape = new Shape();

		$result = $shape->validate([]);
		$this->assertTrue($result->valid());
		$this->assertCount(0, $result->issues());
		$this->assertSame([], $result->messages('email'));
		$this->assertSame([], $result->values());
	}

	public function testShapePreparationRunsBeforeReadingFieldsAndExtras(): void
	{
		$shape = new Shape()
			->extra(Extra::Forbid)
			->prepare(static function (array $data): array {
				self::assertSame(['firstName' => 'Ada', 'age' => '37'], $data);

				return [
					'first_name' => $data['firstName'],
					'age' => $data['age'],
				];
			});
		$shape->add('first_name', 'string');
		$shape->add('age', 'int');

		$result = $shape->validate(['firstName' => 'Ada', 'age' => '37']);

		$this->assertTrue($result->valid());
		$this->assertSame(['first_name' => 'Ada', 'age' => 37], $result->values());
	}

	public function testListShapePreparationReceivesWholeInput(): void
	{
		$shape = Shape::list()->prepare(static function (array $data): array {
			self::assertSame(['Ada', 'Grace'], $data);

			return array_map(
				static fn(mixed $name): array => ['name' => $name],
				$data,
			);
		});
		$shape->add('name', 'string');

		$result = $shape->validate(['Ada', 'Grace']);

		$this->assertTrue($result->valid());
		$this->assertSame(
			[
				['name' => 'Ada'],
				['name' => 'Grace'],
			],
			$result->values(),
		);
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
		$shape->add('unknown_1', 'string');
		$shape->add('unknown_2', 'int');

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
		$this->assertCount(0, $result->issues());

		$values = $result->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertArrayNotHasKey('unknown_3', $values);

		$shape = new Shape()->extra(Extra::Allow);
		$shape->add('unknown_1', 'string');
		$shape->add('unknown_2', 'int');

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
		$this->assertCount(0, $result->issues());

		$values = $result->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertSame('Unknown', $values['unknown_3']);
		$this->assertSame('23', $values['unknown_4']);
	}

	public function testForbidsExtraData(): void
	{
		$shape = new Shape()->extra(Extra::Forbid);
		$shape->add('name', 'string');

		$result = $shape->validate([
			'name' => 'Jane',
			'role' => 'admin',
		]);

		$this->assertFalse($result->valid());
		$this->assertSame('Field "role" is not allowed', $result->first('role'));
		$this->assertSame(['name' => 'Jane'], $result->values());

		$issues = $result->issues();
		$this->assertSame('Field "role" is not allowed', $issues[0]->message);
		$this->assertSame(['role'], $issues[0]->path);
		$this->assertSame('extra', $issues[0]->code);
	}

	public function testForbidExtraDataUsesConfiguredMessage(): void
	{
		$shape = new Shape()
			->extra('forbid')
			->message('extra', 'Unexpected {field}: {value}');
		$shape->add('name', 'string');

		$result = $shape->validate([
			'name' => 'Jane',
			'role' => 'admin',
		]);

		$this->assertSame('Unexpected role: admin', $result->first('role'));
	}

	public function testRejectsInvalidExtraMode(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Invalid extra mode "drop"');

		new Shape()->extra('drop');
	}

	public function testRequiredRule(): void
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
		$shape->add('valid_1', 'string')->rules('required');
		$shape->add('valid_2', 'bool')->rules('required');
		$shape->add('valid_3', 'int')->rules('required');
		$shape->add('valid_4', 'float')->rules('required');
		$shape->add('valid_5', 'list')->rules('required');
		$shape->add('invalid_1', 'string')->rules('required');
		$shape->add('invalid_2', 'float')->rules('required')->label('Required 2');
		$shape->add('invalid_3', 'list')->rules('required');
		$shape->add('invalid_4', 'string')->rules('required');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(4, $result->issues());
		$this->assertSame('invalid_1 is required', $result->first('invalid_1'));
		$this->assertSame('Required 2 is required', $result->first('invalid_2'));
		$this->assertSame('invalid_3 is required', $result->first('invalid_3'));
		$this->assertSame('invalid_4 is required', $result->first('invalid_4'));
	}

	public function testEmailRule(): void
	{
		$testData = [
			'valid_email' => 'valid@email.com',
			'invalid_email' => 'invalid@email',
		];

		$shape = new Shape();
		$shape->add('invalid_email', 'string')->rules('email')->label('Email');
		$shape->add('valid_email', 'string')->rules('email');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('Email must be a valid email address', $result->first('invalid_email'));
	}

	public function testEmailRuleWithDnsCheck(): void
	{
		$testData = [
			'valid_email' => 'valid@gmail.com',
			'invalid_email' => 'invalid@test.tld',
		];

		$shape = new Shape();
		$shape->add('invalid_email', 'string')->rules('email:checkdns');
		$shape->add('valid_email', 'string')->rules('email:checkdns');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame(
			'invalid_email must be a valid email address',
			$result->first('invalid_email'),
		);
	}

	public function testMinValueRule(): void
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
		$shape->add('valid_1', 'int')->rules('min:10');
		$shape->add('valid_2', 'float')->rules('min:10');
		$shape->add('valid_3', 'int')->rules('min:10');
		$shape->add('valid_4', 'float')->rules('min:10');
		$shape->add('invalid_1', 'int')->rules('min:10')->label('Min');
		$shape->add('invalid_2', 'float')->rules('min:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame('Min must be at least 10', $result->first('invalid_1'));
		$this->assertSame('invalid_2 must be at least 10', $result->first('invalid_2'));
	}

	public function testMaxValueRule(): void
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
		$shape->add('valid_1', 'int')->rules('max:13');
		$shape->add('valid_2', 'float')->rules('max:13');
		$shape->add('valid_3', 'int')->rules('max:13');
		$shape->add('valid_4', 'float')->rules('max:13');
		$shape->add('invalid_1', 'int')->rules('max:13');
		$shape->add('invalid_2', 'float')->rules('max:13')->label('Max');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame('invalid_1 must be at most 13', $result->first('invalid_1'));
		$this->assertSame('Max must be at most 13', $result->first('invalid_2'));
	}

	public function testMinLengthRule(): void
	{
		$testData = [
			'valid_1' => 'abcdefghijklm',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghi',
		];

		$shape = new Shape();
		$shape->add('valid_1', 'string')->rules('minlen:10');
		$shape->add('valid_2', 'string')->rules('minlen:10');
		$shape->add('invalid', 'string')->rules('minlen:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame(
			'invalid must be at least 10 characters',
			$result->first('invalid'),
		);
	}

	public function testMaxLengthRule(): void
	{
		$testData = [
			'valid_1' => 'abcdefghi',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghiklm',
		];

		$shape = new Shape();
		$shape->add('valid_1', 'string')->rules('maxlen:10');
		$shape->add('valid_2', 'string')->rules('maxlen:10');
		$shape->add('invalid', 'string')->rules('maxlen:10');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame(
			'invalid must be at most 10 characters',
			$result->first('invalid'),
		);
	}

	public function testRegexRule(): void
	{
		$testData = [
			'valid' => 'abcdefghi',
			'invalid' => 'abcdefghiklm',
			'valid_colon' => 'abcdef:ghi:klm:',
			'invalid_colon' => 'abcdef:ghi:klm',
		];

		$shape = new Shape();
		$shape->add('valid', 'string')->rules('regex:/^abcdefghi$/');
		$shape->add('invalid', 'string')->rules('regex:/^abcdefghi$/');
		$shape->add('valid_colon', 'string')->rules('regex:/^[a-z:]+:$/');
		$shape->add('invalid_colon', 'string')->rules('regex:/^[a-z:]+:$/');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame('invalid has an invalid format', $result->first('invalid'));
	}

	public function testInRule(): void
	{
		$testData = [
			'valid1' => 'valid',
			'valid2' => 'alsovalid',
			'invalid' => 'invalid',
		];

		$shape = new Shape();
		$shape->add('valid1', 'string')->rules('in:valid,alsovalid');
		$shape->add('valid2', 'string')->rules('in:valid,alsovalid');
		$shape->add('invalid', 'string')->rules('in:valid,alsovalid');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('invalid must be an allowed value', $result->first('invalid'));
	}

	public function testInRuleWithQuotedAndEscapedValues(): void
	{
		$testData = [
			'quoted_comma' => 'ACME, Inc',
			'escaped_comma' => 'ACME, Inc',
			'quoted_colon' => 'http://',
			'invalid' => 'Nope',
		];

		$shape = new Shape();
		$shape->add('quoted_comma', 'string')->rules('in:"ACME, Inc",Globex');
		$shape->add('escaped_comma', 'string')->rules('in:ACME\\, Inc,Globex');
		$shape->add('quoted_colon', 'string')->rules('in:"http://","https://"');
		$shape->add('invalid', 'string')->rules('in:"ACME, Inc",Globex');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('invalid must be an allowed value', $result->first('invalid'));
		$this->assertFalse($result->has('quoted_comma'));
		$this->assertFalse($result->has('escaped_comma'));
		$this->assertFalse($result->has('quoted_colon'));
	}

	public function testEscapedAndQuotedColonArgument(): void
	{
		$shape = new Shape()->rule(
			'starts_with',
			self::startsWithRule(),
		);
		$shape->add('escaped', 'string')->rules('starts_with:http\\://');
		$shape->add('quoted', 'string')->rules('starts_with:"http://"');
		$shape->add('invalid', 'string')->rules('starts_with:http\\://');

		$result = $shape->validate([
			'escaped' => 'http://duon.de',
			'quoted' => 'http://duon.org',
			'invalid' => 'https://duon.de',
		]);

		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('Must start with http://', $result->first('invalid'));
	}

	public function testReviewCallbackAddsErrors(): void
	{
		$shape = new Shape();
		$shape->add('email', 'string')->rules('required')->label('Email');
		$shape->review(static function (Review $context): void {
			self::assertSame(['email' => 'taken@example.com'], $context->values());
			self::assertFalse($context->isList());

			$context->addError('email', 'Already used', 'email.taken');
		});

		$result = $shape->validate(['email' => 'taken@example.com']);

		$this->assertFalse($result->valid());
		$this->assertSame('Already used', $result->first('email'));
		$this->assertSame('taken@example.com', $result->values()['email']);
		$this->assertSame('email.taken', $result->issues()[0]->code);
	}

	public function testReviewCallbackAddsErrorsForRootIntegerAndArrayPaths(): void
	{
		$shape = new Shape();
		$shape->review(static function (Review $context): void {
			$context->addError('', 'Form error', 'form');
			$context->addError(0, 'First row error', 'row');
			$context->addError(['items', 0, 'name'], 'Name error', 'item.name');
		});

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertCount(3, $result->issues());
		$this->assertSame('Form error', $result->first(''));
		$this->assertSame('First row error', $result->first(0));
		$this->assertSame('Name error', $result->first(['items', 0, 'name']));
	}

	public function testAllReviewCallbacksRunOnceReviewStarts(): void
	{
		$shape = new Shape();
		$shape->review(static function (Review $context): void {
			$context->addError('first', 'First error');
		});
		$shape->review(static function (Review $context): void {
			$context->addError('second', 'Second error');
		});

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('First error', $result->first('first'));
		$this->assertSame('Second error', $result->first('second'));
	}

	public function testReviewCallbacksDoNotRunAfterValidationErrors(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('email', 'string')->rules('required');
		$shape->review(static function (Review $_context) use (&$called): void {
			$called = true;
		});

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertFalse($called);
		$this->assertSame('email is required', $result->first('email'));
	}

	public function testFieldPreparationRunsBeforeScalarValidation(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->prepare(static fn(mixed $_value): string => '42');

		$result = $shape->validate(['age' => 'not a number']);

		$this->assertTrue($result->valid());
		$this->assertSame(42, $result->values()['age']);
	}

	public function testFieldDefaultValueFillsMissingField(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->default('draft');
		$shape->add('count', 'int')->default('13');

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
		$this->assertSame('draft', $result->values()['status']);
		$this->assertSame(13, $result->values()['count']);
	}

	public function testFieldDefaultValueRunsBeforePreparation(): void
	{
		$shape = new Shape();
		$shape->add('title', 'string');
		$shape
			->add('slug', 'string')
			->default('')
			->prepare(
				static fn(mixed $value, array $data): string => $value !== ''
					? (string) $value
					: strtolower((string) $data['title']),
			);

		$result = $shape->validate(['title' => 'Hello']);

		$this->assertTrue($result->valid());
		$this->assertSame('hello', $result->values()['slug']);
	}

	public function testExplicitValueOverridesFieldDefault(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->default('draft');

		$result = $shape->validate(['status' => 'published']);

		$this->assertTrue($result->valid());
		$this->assertSame('published', $result->values()['status']);
	}

	public function testInvalidFieldDefaultAddsValidationError(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->label('Age')->default('old');

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('Age must be a whole number', $result->first('age'));
		$this->assertSame('old', $result->values()['age']);
	}

	public function testInvalidNestedFieldDefaultAddsValidationError(): void
	{
		$nested = new Shape();
		$nested->add('email', 'string')->rules('email')->label('Email');

		$shape = new Shape();
		$shape->add('child', $nested)->default(['email' => 'invalid']);

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('Email must be a valid email address', $result->first('child.email'));
		$this->assertSame(['email' => 'invalid'], $result->values()['child']);
	}

	public function testNullableFieldAllowsNullValue(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->label('Items')->nullable();

		$result = $shape->validate(['items' => null]);

		$this->assertTrue($result->valid());
		$this->assertNull($result->values()['items']);
	}

	public function testNonNullableFieldRejectsNullValue(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->label('Items');

		$result = $shape->validate(['items' => null]);

		$this->assertFalse($result->valid());
		$this->assertSame('Items must not be null', $result->first('items'));
	}

	public function testFieldNullMessageOverridesShapeMessage(): void
	{
		$shape = new Shape();
		$shape->message('null', 'Shape null');
		$shape->add('items', 'list')->label('Items')->message('null', '{label} cannot be null');

		$result = $shape->validate(['items' => null]);

		$this->assertFalse($result->valid());
		$this->assertSame('Items cannot be null', $result->first('items'));
	}

	public function testNullFieldDefaultImpliesNullable(): void
	{
		$shape = new Shape();
		$shape->add('note', 'string')->default(null);

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
		$this->assertNull($result->values()['note']);
	}

	public function testRequiredNarrowsNullFieldDefault(): void
	{
		$shape = new Shape();
		$shape->add('note', 'string')->rules('required')->default(null);

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('note is required', $result->first('note'));
	}

	public function testFieldPreparationReceivesInputData(): void
	{
		$shape = new Shape();
		$shape->add('slug', 'string')->prepare(
			static fn(mixed $value, array $data): string => $value !== ''
				? (string) $value
				: strtolower((string) $data['title']),
		);

		$result = $shape->validate([
			'title' => 'Hello',
			'slug' => '',
		]);

		$this->assertTrue($result->valid());
		$this->assertSame('hello', $result->values()['slug']);
	}

	public function testFieldPreparationRunsBeforeNestedShapeValidation(): void
	{
		$nested = new Shape();
		$nested->add('name', 'string')->rules('required');

		$shape = new Shape();
		$shape
			->add('child', $nested)
			->prepare(static fn(mixed $value): mixed => $value ?? ['name' => 'Prepared']);

		$result = $shape->validate(['child' => null]);

		$this->assertTrue($result->valid());
		$this->assertSame('Prepared', $result->values()['child']['name']);
	}

	public function testFieldFinalizationRunsAfterValidation(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('count', 'int')
			->rules('min:2')
			->finalize(static function (mixed $value, array $values) use (&$called): int {
				$called = true;
				self::assertSame(2, $value);
				self::assertSame(['count' => 2, 'offset' => 3], $values);

				return $value + $values['offset'];
			});
		$shape->add('offset', 'int')->default('3');

		$result = $shape->validate(['count' => '2']);

		$this->assertTrue($result->valid());
		$this->assertTrue($called);
		$this->assertSame(5, $result->values()['count']);
		$this->assertSame(3, $result->values()['offset']);
	}

	public function testFieldFinalizationRunsForDefaults(): void
	{
		$shape = new Shape();
		$shape->add('title', 'string');
		$shape
			->add('slug', 'string')
			->default('')
			->finalize(static fn(mixed $_value, array $values): string => strtolower(
				(string) $values['title'],
			));

		$result = $shape->validate(['title' => 'Hello']);

		$this->assertTrue($result->valid());
		$this->assertSame('hello', $result->values()['slug']);
	}

	public function testReviewCallbacksReceiveFinalizedValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('name', 'string')->finalize(
			static fn(mixed $value): string => strtoupper((string) $value),
		);
		$shape->review(static function (Review $context) use (&$called): void {
			$called = true;
			self::assertSame(['name' => 'ADA'], $context->values());
		});

		$result = $shape->validate(['name' => 'ada']);

		$this->assertTrue($result->valid());
		$this->assertTrue($called);
		$this->assertSame('ADA', $result->values()['name']);
	}

	public function testFieldFinalizationDoesNotRunAfterValidationErrors(): void
	{
		$called = false;
		$shape = new Shape();
		$shape->add('age', 'int')->finalize(static function (mixed $value) use (&$called): mixed {
			$called = true;

			return $value;
		});

		$result = $shape->validate(['age' => 'old']);

		$this->assertFalse($result->valid());
		$this->assertFalse($called);
	}

	public function testFieldFinalizationDoesNotRunForOmittedOptionalValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('subtitle', 'string')
			->optional()
			->finalize(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
		$this->assertFalse($called);
		$this->assertSame([], $result->values());
	}

	public function testFieldFinalizationReceivesListItemValues(): void
	{
		$seen = [];
		$shape = Shape::list();
		$shape->add('first', 'string');
		$shape->add('last', 'string')->finalize(static function (mixed $value, array $values) use (
			&$seen,
		): string {
			$seen[] = $values;

			return $values['first'] . ' ' . $value;
		});

		$result = $shape->validate([
			['first' => 'Ada', 'last' => 'Lovelace'],
			['first' => 'Grace', 'last' => 'Hopper'],
		]);

		$this->assertTrue($result->valid());
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

	public function testOptionalFieldOmitsMissingValue(): void
	{
		$shape = new Shape();
		$shape->add('subtitle', 'string')->optional();

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
		$this->assertSame([], $result->values());
	}

	public function testOptionalFieldValidatesPresentValue(): void
	{
		$shape = new Shape();
		$shape->add('age', 'int')->optional();

		$result = $shape->validate(['age' => '13']);

		$this->assertTrue($result->valid());
		$this->assertSame(13, $result->values()['age']);
	}

	public function testMissingFieldAddsValidationError(): void
	{
		$shape = new Shape();
		$shape->add('title', 'string')->label('Title');

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('Title is required', $result->first('title'));
		$this->assertSame([], $result->values());
	}

	public function testFieldMissingMessageOverridesShapeMessage(): void
	{
		$shape = new Shape();
		$shape->message('missing', 'Shape missing');
		$shape->add('title', 'string')->label('Title')->message('missing', '{label} is missing');

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('Title is missing', $result->first('title'));
	}

	public function testFieldPreparationDoesNotRunForMissingValues(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('missing', 'string')
			->optional()
			->prepare(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
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
		$shape->add('int', 'int')->rules('required');
		$shape->add('text', 'string')->rules('required');
		$shape->add('shape', new SubShape())->label('Shape');

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
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
		$shape->add('int', 'int')->rules('required');
		$shape->add('text', 'string')->rules('required');
		$shape->add('shape', new SubShape());

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame('text is required', $result->first('text'));
		$this->assertSame(
			'Email must be a valid email address',
			$result->first('shape.inner_email'),
		);
	}

	public function testSubShapeRejectsNonArrayValue(): void
	{
		$shape = new Shape();
		$shape->add('profile', new SubShape())->label('Profile');

		$result = $shape->validate(['profile' => 'invalid']);

		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('Profile must be an array', $result->first('profile'));
		$this->assertSame(['profile'], $result->issues()[0]->path);
		$this->assertSame('type.shape', $result->issues()[0]->code);
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
		$shape->add('int', 'int')->rules('required');
		$shape->add('text', 'string')->rules('required');
		$shape->add('single_shape', new SubShape());
		$shape->add('list_shape', new SubShape(true))->optional();

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
		$values = $result->values();
		$this->assertSame(13, $values[0]['int']);
		$this->assertSame(23, $values[0]['single_shape']['inner_int']);
		$this->assertArrayNotHasKey('list_shape', $values[0]);
		$this->assertSame('Text 2', $values[1]['text']);
		$this->assertSame('example@example.com', $values[1]['single_shape']['inner_email']);
		$this->assertSame('example@example.com', $values[1]['list_shape'][0]['inner_email']);
		$this->assertSame(47, $values[1]['list_shape'][1]['inner_int']);
	}

	public function testListShapeRejectsNonArrayItems(): void
	{
		$shape = Shape::list();
		$shape->add('name', 'string')->optional();

		$result = $shape->validate([
			'invalid',
			['name' => 'Ada'],
		]);

		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('Item must be an array', $result->first(0));
		$this->assertSame([0], $result->issues()[0]->path);
		$this->assertSame('type.shape', $result->issues()[0]->code);
		$this->assertSame([[], ['name' => 'Ada']], $result->values());
	}

	public function testInvalidListShape(): void
	{
		$testData = $this->getListData();
		$shape = $this->getListShape();

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(9, $result->issues());
		$this->assertSame('text is required', $result->first([0, 'text']));
		$this->assertSame('Int is required', $result->first([0, 'single_shape', 'inner_int']));
		$this->assertSame('Single Shape is required', $result->first([1, 'single_shape']));
		$this->assertSame('email must be a valid email address', $result->first([1, 'email']));
		$this->assertSame(
			'Email must be a valid email address',
			$result->first([3, 'single_shape', 'inner_email']),
		);
		$this->assertSame(
			'Int must be a whole number',
			$result->first([3, 'list_shape', 0, 'inner_int']),
		);
		$this->assertSame(
			'Email must be a valid email address',
			$result->first([3, 'list_shape', 2, 'inner_email']),
		);
	}

	public function testIssuePathsContainNestedListLocations(): void
	{
		$testData = $this->getListData();
		$shape = $this->getListShape();

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());

		$issues = $result->issues();
		$this->assertSame([0, 'single_shape', 'inner_int'], $issues[0]->path);
		$this->assertSame('missing', $issues[0]->code);
		$this->assertSame([3, 'list_shape', 0, 'inner_int'], $issues[5]->path);
		$this->assertSame('type.int', $issues[5]->code);
		$this->assertSame(['arg1' => '10'], $issues[8]->params);
	}

	public function testEmptyFieldName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('must not be empty');

		$shape = new Shape();
		$shape->add('', 'Int')->rules('int');
	}

	public function testEmptyArraySkipsRegularRule(): void
	{
		$testData = [
			'items' => [],
		];

		$shape = new Shape();
		$shape->add('items', 'list')->rules('in:a,b,c');

		$result = $shape->validate($testData);
		$this->assertTrue($result->valid());
	}

	private static function startsWithRule(): Rule
	{
		return new class implements Rule {
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
		// Regex rule without a pattern (just 'regex' with no argument)
		$shape->add('text', 'string')->rules('regex');

		$result = $shape->validate($testData);
		$this->assertFalse($result->valid());
		$this->assertCount(1, $result->issues());
		$this->assertSame('text has an invalid format', $result->first('text'));
	}
}
