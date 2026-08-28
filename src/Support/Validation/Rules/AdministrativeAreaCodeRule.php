<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\Entity\Country;
use Galahad\LaravelAddressing\Support\Validation\Rules\Concerns\CastsValueToString;
use Illuminate\Contracts\Validation\ValidationRule;

class AdministrativeAreaCodeRule implements ValidationRule
{
	use CastsValueToString;

	/**
	 * @var \Galahad\LaravelAddressing\Entity\Country
	 */
	protected $country;

	/**
	 * Constructor.
	 *
	 * @param \Galahad\LaravelAddressing\Entity\Country $country
	 */
	public function __construct(Country $country)
	{
		$this->country = $country;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (null === ($value = $this->castToString($value))) {
			$this->fail($fail);

			return;
		}

		// If it's empty, only the address format decides whether that's a failure.
		if ('' === $value) {
			if ($this->isRequired()) {
				$fail('validation.required')->translate();
			}

			return;
		}

		// If we don't have a known list of administrative areas, pass.
		if (0 === $this->country->administrativeAreas()->count()) {
			return;
		}

		if (null === $this->country->administrativeArea($value)) {
			$this->fail($fail);
		}
	}

	/**
	 * Report a failure using our own translated message.
	 *
	 * @param \Closure $fail
	 */
	protected function fail(Closure $fail): void
	{
		$fail('laravel-addressing::validation.administrative_area_code')->translate([
			'type' => $this->country->addressFormat()->getAdministrativeAreaType() ?? 'administrative area',
		]);
	}

	protected function isRequired(): bool
	{
		return in_array('administrativeArea', $this->country->addressFormat()->getRequiredFields());
	}
}
