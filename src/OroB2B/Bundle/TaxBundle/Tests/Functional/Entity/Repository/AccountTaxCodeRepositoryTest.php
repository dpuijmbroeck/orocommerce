<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes as TaxFixture;

/**
 * @dbIsolation
 */
class AccountTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes']);
    }

    public function testFindOneByAccount()
    {
        /** @var Account $account1 */
        $account1 = $this->getReference('account.orphan');
        $expectedTaxCode = $this->getRepository()->findOneByAccount($account1);

        /** @var AccountTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::TAX_1);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode1->getId());
    }

    /**
     * @return AccountTaxCodeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_tax.entity.account_tax_code.class')
        );
    }
}
