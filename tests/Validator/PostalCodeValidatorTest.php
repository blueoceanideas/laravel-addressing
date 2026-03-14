<?php

namespace Galahad\LaravelAddressing\Tests\Validator;

use CommerceGuys\Addressing\Subdivision\Subdivision;

/**
 * Class PostalCodeValidatorTest.
 *
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class PostalCodeValidatorTest extends BaseValidatorTestCase
{
	/**
	 * Determine whether the installed commerceguys/addressing has subdivision-level
	 * postal code patterns (v1 does, v2 removed them for most countries).
	 */
	protected function hasSubdivisionPostalCodePatterns(): bool
	{
		return method_exists(Subdivision::class, 'getPostalCodePatternType');
	}

	public function test_colorado_postal_code()
	{
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'US', 'state' => 'CO', 'code' => '80301'],
			'rules' => ['code' => 'postal_code:country,state'],
		]));
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'US', 'state' => 'CO', 'code' => '81000'],
			'rules' => ['code' => 'postal_code:country,state'],
		]));

		// In v1, subdivision-level patterns reject postal codes outside the state range.
		// In v2, subdivision postal code patterns were removed, so only country-level
		// format validation applies (82000 is a valid US zip format).
		if ($this->hasSubdivisionPostalCodePatterns()) {
			$this->assertFalse($this->performValidation([
				'data' => ['country' => 'US', 'state' => 'CO', 'code' => '82000'],
				'rules' => ['code' => 'postal_code:country,state'],
			]));
		} else {
			$this->assertTrue($this->performValidation([
				'data' => ['country' => 'US', 'state' => 'CO', 'code' => '82000'],
				'rules' => ['code' => 'postal_code:country,state'],
			]));
		}
	}

	public function test_invalid_format_postal_code_always_fails()
	{
		// This should fail in both v1 and v2 because it's not a valid US zip format
		$this->assertFalse($this->performValidation([
			'data' => ['country' => 'US', 'state' => 'CO', 'code' => 'INVALID'],
			'rules' => ['code' => 'postal_code:country,state'],
		]));
	}

	public function test_array_postal_code_invalid()
	{
		$this->assertFalse($this->performValidation([
			'data' => ['country' => 'US', 'state' => 'CO', 'code' => ['80301']],
			'rules' => ['code' => 'postal_code:country,state'],
		]));
	}

	public function test_brazilian_postal_codes()
	{
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'BR', 'state' => 'MG', 'code' => '31170-070'],
			'rules' => ['code' => 'postal_code:country,state'],
		]));
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'BR', 'state' => 'MG', 'code' => '31310-190'],
			'rules' => ['code' => 'postal_code:country,state'],
		]));

		// In v1, subdivision patterns reject postal codes from other states.
		// In v2, only country-level format validation applies.
		if ($this->hasSubdivisionPostalCodePatterns()) {
			$this->assertFalse($this->performValidation([
				'data' => ['country' => 'BR', 'state' => 'MG', 'code' => '21000-070'],
				'rules' => ['code' => 'postal_code:country,state'],
			]));
		} else {
			$this->assertTrue($this->performValidation([
				'data' => ['country' => 'BR', 'state' => 'MG', 'code' => '21000-070'],
				'rules' => ['code' => 'postal_code:country,state'],
			]));
		}
	}

	public function test_uses_default_field_names()
	{
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'US', 'administrative_area' => 'CO', 'code' => '80301'],
			'rules' => ['code' => 'postal_code'],
		]));
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'US', 'state' => 'CO', 'code' => '80301'],
			'rules' => ['code' => 'postal_code'],
		]));
	}

	public function test_uses_country_reg_ex_if_no_admin_area()
	{
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'GB', 'administrative_area' => '', 'code' => 'NW4 2HX'],
			'rules' => ['code' => 'postal_code'],
		]));
		$this->assertFalse($this->performValidation([
			'data' => ['country' => 'GB', 'administrative_area' => '', 'code' => '1234567890'],
			'rules' => ['code' => 'postal_code'],
		]));
	}

	public function test_allows_empty_postal_codes_in_countries_where_it_is_optional()
	{
		$this->assertTrue($this->performValidation([
			'data' => ['country' => 'IE', 'administrative_area' => '', 'code' => ''],
			'rules' => ['code' => 'postal_code'],
		]));

		$this->assertFalse($this->performValidation([
			'data' => ['country' => 'IE', 'administrative_area' => '', 'code' => '948723$&(#*'],
			'rules' => ['code' => 'postal_code'],
		]));
	}
}
