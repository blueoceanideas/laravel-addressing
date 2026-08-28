<?php

namespace Galahad\LaravelAddressing\Support\Validation;

use Galahad\LaravelAddressing\Entity\Country;
use Galahad\LaravelAddressing\Entity\Subdivision;
use Galahad\LaravelAddressing\LaravelAddressing;
use Galahad\LaravelAddressing\Support\Validation\Rules\AdministrativeAreaCodeRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\AdministrativeAreaNameRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\CountryCodeRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\CountryNameRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\LooseAdministrativeAreaRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\LooseCountryRule;
use Galahad\LaravelAddressing\Support\Validation\Rules\PostalCodeRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\InvokableValidationRule;
use Illuminate\Validation\Validator as BaseValidator;

class Validator
{
	protected LaravelAddressing $addressing;

	public function __construct(LaravelAddressing $addressing)
	{
		$this->addressing = $addressing;
	}

	/**
	 * Validate that the input is a country code.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function countryCode(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		return $this->check('country_code', new CountryCodeRule($this->addressing), $attribute, $value, $validator);
	}

	/**
	 * Validate that the input is a country name.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function countryName(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		return $this->check('country_name', new CountryNameRule($this->addressing), $attribute, $value, $validator);
	}

	/**
	 * Validate that the input is a country name or code.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function looseCountry(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		return $this->check('country', new LooseCountryRule($this->addressing), $attribute, $value, $validator);
	}

	/**
	 * Validate that the input is an administrative code.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function administrativeArea(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		if (! $country = $this->loadCountryFromValidationData($parameters, $validator)) {
			return false;
		}

		return $this->check('administrative_area_code', new AdministrativeAreaCodeRule($country), $attribute, $value, $validator);
	}

	/**
	 * Validate that the input is an administrative area name.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function administrativeAreaName(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		if (! $country = $this->loadCountryFromValidationData($parameters, $validator)) {
			return false;
		}

		return $this->check('administrative_area_name', new AdministrativeAreaNameRule($country), $attribute, $value, $validator);
	}

	/**
	 * Validate that the input is an administrative area name or code.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function looseAdministrativeArea(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		if (! $country = $this->loadCountryFromValidationData($parameters, $validator)) {
			return false;
		}

		return $this->check('administrative_area', new LooseAdministrativeAreaRule($country), $attribute, $value, $validator);
	}

	/**
	 * Validate a postal code.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	public function postalCode(string $attribute, mixed $value, array $parameters, BaseValidator $validator): bool
	{
		if (! $country = $this->loadCountryFromValidationData($parameters, $validator)) {
			return false;
		}

		$administrative_area = $this->loadAdministrativeAreaFromValidationData($country, $parameters, $validator);

		return $this->check('postal_code', new PostalCodeRule($country, $administrative_area), $attribute, $value, $validator);
	}

	/**
	 * Bridge between Laravel's string-based rule names (registered with
	 * Validator::extend(), which expects a boolean) and our rule classes,
	 * which implement the ValidationRule contract and report failures
	 * through a $fail() closure instead of a return value.
	 *
	 * @param string $rule_name
	 * @param \Illuminate\Contracts\Validation\ValidationRule $rule
	 * @param string $attribute
	 * @param mixed $value
	 * @param \Illuminate\Validation\Validator $validator
	 * @return bool
	 */
	protected function check(string $rule_name, ValidationRule $rule, string $attribute, mixed $value, BaseValidator $validator): bool
	{
		// InvokableValidationRule is the same adapter Laravel uses when a rule
		// object is passed in the rules array. It provides the $fail() closure,
		// collects the resulting messages and gives us back a boolean.
		$invokable = InvokableValidationRule::make($rule)->setValidator($validator);

		if ($invokable->passes($attribute, $value)) {
			return true;
		}

		// extend() callbacks can only return a boolean, so register the rule's
		// own message under the "attribute.rule" key the failure will look up.
		// Fallback messages are the last thing Laravel checks, so anything the
		// developer configured for this attribute still takes precedence.
		foreach ((array) $invokable->message() as $message) {
			$validator->fallbackMessages["{$attribute}.{$rule_name}"] = $message;
		}

		return false;
	}

	/**
	 * This tries to resolve the entity for the requested country based
	 * on the data under validation. Eg. ?country=CA should resolve to the
	 * Canada country entity.
	 *
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return \Galahad\LaravelAddressing\Entity\Country|null
	 */
	protected function loadCountryFromValidationData(array $parameters, BaseValidator $validator): ?Country
	{
		$country_input_name = $parameters[0] ?? 'country';

		if (! $country_value = Arr::get($validator->getData(), $country_input_name)) {
			return null;
		}

		return $this->addressing->findCountry($country_value);
	}

	/**
	 * This tries to resolve the entity for the requested subdivision based
	 * on the data under validation. Eg. ?state=PA should resolve to the
	 * United States -> Pennsylvania subdivision entity.
	 *
	 * @param \Galahad\LaravelAddressing\Entity\Country $country
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return \Galahad\LaravelAddressing\Entity\Subdivision|null
	 */
	protected function loadAdministrativeAreaFromValidationData(Country $country, array $parameters, BaseValidator $validator): ?Subdivision
	{
		if (! $administrative_area_value = $this->loadAdministrativeAreaValueFromValidationData($parameters, $validator)) {
			return null;
		}

		return $country->findAdministrativeArea($administrative_area_value);
	}

	/**
	 * This looks through the data under validation and tries to get the value
	 * for the current state/province using common input names like "state" or "province".
	 *
	 * @param array $parameters
	 * @param \Illuminate\Validation\Validator $validator
	 * @return string|null
	 */
	protected function loadAdministrativeAreaValueFromValidationData(array $parameters, BaseValidator $validator): ?string
	{
		// Either use the explicitly set name, or try all common names
		$possible_input_names = isset($parameters[1])
			? [$parameters[1]]
			: ['administrative_area', 'state', 'province'];

		$data = $validator->getData();
		foreach ($possible_input_names as $input_name) {
			if ($value = Arr::get($data, $input_name)) {
				return $value;
			}
		}

		return null;
	}
}
