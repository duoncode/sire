<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Email implements Contract\Validator
{
	public string $message {
		get => 'Invalid email address';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		$email = filter_var(
			trim((string) $value->value),
			\FILTER_VALIDATE_EMAIL,
		);

		if ($email !== false && ($args[0] ?? null) === 'checkdns') {
			[, $mailDomain] = explode('@', $email);

			return Validation::from(checkdnsrr($mailDomain, 'MX'));
		}

		return Validation::from($email !== false);
	}
}
