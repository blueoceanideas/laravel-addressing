<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\Entity\Country;
use Galahad\LaravelAddressing\Entity\Subdivision;
use Galahad\LaravelAddressing\Support\Validation\Rules\Concerns\CastsValueToString;
use Illuminate\Contracts\Validation\ValidationRule;

class PostalCodeRule implements ValidationRule
{
	use CastsValueToString;

	/**
	 * @var \Galahad\LaravelAddressing\Entity\Country
	 */
	protected $country;

	/**
	 * @var \Galahad\LaravelAddressing\Entity\Subdivision|null
	 */
	protected $administrative_area;

	/**
	 * Constructor.
	 *
	 * @param \Galahad\LaravelAddressing\Entity\Country $country
	 * @param \Galahad\LaravelAddressing\Entity\Subdivision|null $administrative_area
	 */
	public function __construct(Country $country, ?Subdivision $administrative_area = null)
	{
		$this->country = $country;
		$this->administrative_area = $administrative_area;
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

		// If we don't have a pattern for this country/area, pass.
		if (! $pattern = $this->pattern()) {
			return;
		}

		if (! preg_match($pattern, $value)) {
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
		$fail('laravel-addressing::validation.postal_code')->translate([
			'type' => $this->country->addressFormat()->getPostalCodeType() ?? 'postal code',
		]);
	}

	protected function isRequired(): bool
	{
		return in_array('postalCode', $this->country->addressFormat()->getRequiredFields());
	}

	/**
	 * Build the postal code regex pattern.
	 *
	 * In commerceguys/addressing v1, subdivisions had a "pattern type" (full or start)
	 * that determined whether the pattern should match the entire postal code or just
	 * the beginning. In v2, this concept was removed and all subdivision patterns are
	 * full patterns. This method handles both versions via the wrapper's
	 * getPostalCodePatternType() which gracefully degrades.
	 */
	protected function pattern(): ?string
	{
		$pattern = $this->administrative_area
			? $this->administrative_area->getPostalCodePattern()
			: $this->country->addressFormat()->getPostalCodePattern();

		if (null === $pattern) {
			return null;
		}

		$pattern_type = $this->administrative_area
			? $this->administrative_area->getPostalCodePatternType()
			: 'full';

		if ('start' === $pattern_type) {
			return '/^'.$pattern.'/i';
		}

		return '/'.$pattern.'/i';
	}
}
