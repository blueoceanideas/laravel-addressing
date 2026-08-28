<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\Entity\Country;
use Galahad\LaravelAddressing\Support\Validation\Rules\Concerns\RunsNestedRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class LooseAdministrativeAreaRule implements ValidationRule, ValidatorAwareRule
{
	use RunsNestedRules;

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
		$passes = $this->nestedRulePasses(new AdministrativeAreaCodeRule($this->country), $attribute, $value)
			|| $this->nestedRulePasses(new AdministrativeAreaNameRule($this->country), $attribute, $value);

		if ($passes) {
			return;
		}

		$fail('laravel-addressing::validation.administrative_area')->translate([
			'type' => $this->country->addressFormat()->getAdministrativeAreaType(),
		]);
	}
}
