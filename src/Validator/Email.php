<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Override;

/** @api */
final class Email implements Contract\Validator
{
	public string $message = 'Invalid email address';

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		$email = filter_var(
			trim((string) $value->value),
			\FILTER_VALIDATE_EMAIL,
		);

		if ($email !== false && ($args[0] ?? null) === 'checkdns') {
			[, $mailDomain] = explode('@', $email);

			return checkdnsrr($mailDomain, 'MX');
		}

		return $email !== false;
	}
}
