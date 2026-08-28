<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules;

use Closure;
use Galahad\LaravelAddressing\LaravelAddressing;
use Galahad\LaravelAddressing\Support\Validation\Rules\Concerns\RunsNestedRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class LooseCountryRule implements ValidationRule, ValidatorAwareRule
{
	use RunsNestedRules;

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
		$passes = $this->nestedRulePasses(new CountryCodeRule($this->addressing), $attribute, $value)
			|| $this->nestedRulePasses(new CountryNameRule($this->addressing), $attribute, $value);

		if ($passes) {
			return;
		}

		$fail('laravel-addressing::validation.country')->translate();
	}
}
