<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Galahad\LaravelAddressing\Entity\Country;
use Galahad\LaravelAddressing\Entity\Subdivision;
use Illuminate\Contracts\Validation\Rule;
use Throwable;

class PostalCodeRule implements Rule
{
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
	public function __construct(Country $country, Subdivision $administrative_area = null)
	{
		$this->country = $country;
		$this->administrative_area = $administrative_area;
	}

	/**
	 * {@inheritdoc}
	 */
	public function passes($attribute, $value): bool
	{
		try {
			$value = (string) $value;
		} catch (Throwable $exception) {
			return false;
		}

		// If it's not required and empty, pass
		if ('' === $value && false === $this->isRequired()) {
			return true;
		}

		// If we don't have a pattern for this country/area, automatically pass
		if (! $pattern = $this->pattern()) {
			return true;
		}

		return preg_match($pattern, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function message(): string
	{
		$type = $this->country->addressFormat()->getPostalCodeType() ?? 'postal code';

		return trans('laravel-addressing::validation.postal_code', compact('type'));
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
