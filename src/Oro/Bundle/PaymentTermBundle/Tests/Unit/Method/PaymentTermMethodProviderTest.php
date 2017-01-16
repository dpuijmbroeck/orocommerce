<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTermMethodProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerInterface;

class PaymentTermMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var PaymentTermMethodProvider
     */
    protected $provider;

    /**
     * @var PaymentTerm
     */
    protected $method;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new PaymentTermMethodProvider(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper
        );
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger **/
        $logger = $this->createMock(LoggerInterface::class);
        $this->provider->setLogger($logger);
        $this->method = new PaymentTerm(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper
        );
        $this->method->setLogger($logger);
    }

    public function testGetPaymentMethods()
    {
        static::assertEquals([PaymentTerm::TYPE => $this->method], $this->provider->getPaymentMethods());
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals($this->method, $this->provider->getPaymentMethod(PaymentTerm::TYPE));
    }

    public function testHasPaymentMethod()
    {
        static::assertTrue($this->provider->hasPaymentMethod(PaymentTerm::TYPE));
        static::assertFalse($this->provider->hasPaymentMethod('not_existing'));
    }

    public function testGetType()
    {
        static::assertEquals(PaymentTerm::TYPE, $this->provider->getType());
    }
}
