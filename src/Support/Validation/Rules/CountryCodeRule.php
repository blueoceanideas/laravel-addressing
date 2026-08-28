<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\LaravelAddressing;
use Galahad\LaravelAddressing\Support\Validation\Rules\Concerns\CastsValueToString;
use Illuminate\Contracts\Validation\ValidationRule;

class CountryCodeRule implements ValidationRule
{
	use CastsValueToString;

	/**
	 * @var \Galahad\LaravelAddressing\LaravelAddressing
	 */
	protected $addressing;

	/**
	 * Constructor.
	 *
	 * @param \Galahad\LaravelAddressing\LaravelAddressing $addressing
	 */
	public function __construct(LaravelAddressing $addressing)
	{
		$this->addressing = $addressing;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		$value = $this->castToString($value);

		if (null === $value || null === $this->addressing->country($value)) {
			$fail('laravel-addressing::validation.country_code')->translate();
		}
	}
}
