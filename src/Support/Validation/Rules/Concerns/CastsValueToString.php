<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules\Concerns;

use Stringable;

/**
 * The value under validation is whatever was in the input, so it isn't
 * necessarily a string: request data can hand us arrays or objects.
 */
trait CastsValueToString
{
	/**
	 * Cast the value under validation to a string, or return null when it
	 * isn't string-able at all — an array or an arbitrary object can never
	 * be a valid address component.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	protected function castToString(mixed $value): ?string
	{
		if (null === $value || is_scalar($value) || $value instanceof Stringable) {
			return (string) $value;
		}

		return null;
	}
}
