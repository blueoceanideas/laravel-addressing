<?php

namespace Galahad\LaravelAddressing\Support\Validation\Rules\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

/**
 * Lets a rule delegate to other rules and act on the boolean outcome.
 *
 * The ValidationRule contract has no return value, so the only way to know
 * whether a nested rule passed is to hand it a $fail() closure of our own
 * and see whether it gets called.
 */
trait RunsNestedRules
{
	/**
	 * @var \Illuminate\Validation\Validator|null
	 */
	protected $validator;

	/**
	 * Set the current validator (\Illuminate\Contracts\Validation\ValidatorAwareRule).
	 *
	 * @param \Illuminate\Validation\Validator $validator
	 * @return $this
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;

		return $this;
	}

	/**
	 * Run a nested rule, discarding its messages.
	 *
	 * @param \Illuminate\Contracts\Validation\ValidationRule $rule
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	protected function nestedRulePasses(ValidationRule $rule, string $attribute, mixed $value): bool
	{
		$failed = false;

		$rule->validate($attribute, $value, function($message = null) use (&$failed) {
			$failed = true;

			// We throw the nested message away, but the nested rule may still
			// chain ->translate() onto whatever $fail() hands back to it.
			return new PotentiallyTranslatedString((string) $message, $this->translator());
		});

		return ! $failed;
	}

	/**
	 * @return \Illuminate\Contracts\Translation\Translator
	 */
	protected function translator(): Translator
	{
		return $this->validator
			? $this->validator->getTranslator()
			: Container::getInstance()->make('translator');
	}
}
