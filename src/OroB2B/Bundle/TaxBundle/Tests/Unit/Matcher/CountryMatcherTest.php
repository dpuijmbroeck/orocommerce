<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;

class CountryMatcherTest extends AbstractMatcherTest
{
    /**
     * @var CountryMatcher
     */
    protected $matcher;

    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new CountryMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);
    }

    /**
     * @dataProvider matchProvider
     * @param TaxRule[] $expected
     * @param Country $country
     * @param string $productTaxCode
     * @param string $accountTaxCode
     * @param TaxRule[] $taxRules
     */
    public function testMatch($expected, $country, $productTaxCode, $accountTaxCode, $taxRules)
    {
        $address = (new Address())
            ->setCountry($country);

        $this->taxRuleRepository
            ->expects(
                empty($taxRules) || empty($productTaxCode) || empty($accountTaxCode) ? $this->never() : $this->once()
            )
            ->method('findByCountryAndProductTaxCodeAndAccountTaxCode')
            ->with($country, $productTaxCode, $accountTaxCode)
            ->willReturn($taxRules);

        $this->assertEquals($expected, $this->matcher->match($address, $productTaxCode, $accountTaxCode));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        $taxRules = [
            new TaxRule(),
            new TaxRule(),
        ];

        return [
            'address with country and product tax code' => [
                'expected' => $taxRules,
                'country' => new Country('US'),
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => $taxRules
            ],
            'address without country' => [
                'expected' => [],
                'country' => null,
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => []
            ],
            'address without product tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => null,
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => []
            ],
            'address without account tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => null,
                'taxRules' => []
            ]
        ];
    }

    public function testIsEuropeanUnionCountry()
    {
        $reflectionClass = new \ReflectionObject($this->matcher);
        $reflectionProperty = $reflectionClass->getProperty('europeanUnionCountryCodes');
        $reflectionProperty->setAccessible(true);
        $europeanCountryCodes = $reflectionProperty->getValue();

        foreach ($europeanCountryCodes as $europeanCode) {
            $this->assertTrue($this->matcher->isEuropeanUnionCountry($europeanCode));
        }

        $this->assertFalse($this->matcher->isEuropeanUnionCountry('NON_EU_COUNTRY'));
    }
}
