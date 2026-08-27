<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\Entity\Country;
use Illuminate\Contracts\Validation\ValidationRule;

class AdministrativeAreaCodeRule implements ValidationRule
{
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

	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
        $value = (string) $value;

        // If required and empty, fail.
        if ($this->isRequired() && empty($value)) {
            $fail('The :attribute is required.');
        }

        // If we have known lst of admin areas and given value is null, fail.
        if ($this->country->administrativeAreas()->count() > 0 && null === $this->country->administrativeArea($value)) {
            $fail('Invalid administrative area code.');
        }
	}

	/**
	 * {@inheritdoc}
	 */
	public function message(): string
	{
		$type = $this->country->addressFormat()->getAdministrativeAreaType();

		return trans('laravel-addressing::validation.administrative_area_code', compact('type'));
	}

	protected function isRequired(): bool
	{
		return in_array('administrativeArea', $this->country->addressFormat()->getRequiredFields());
	}
}
