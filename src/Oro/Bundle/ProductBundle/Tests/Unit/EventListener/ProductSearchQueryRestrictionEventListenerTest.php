<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\EventListener\ProductSearchQueryRestrictionEventListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductSearchQueryRestrictionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $frontendConfigPath = '/front/end/cfg/path';

    /**
     * @var ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductSearchQueryRestrictionEventListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $statuses = ['in_stock'];

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->modifier = $this->getQueryModifier();

        $this->frontendHelper = $this->getFrontendHelper();

        $this->listener = $this->createListener();
    }

    public function testOnSearchQueryInFrontend()
    {
        $this->frontendHelper
            ->expects($this->atLeastOnce())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($this->frontendConfigPath)
            ->willReturn($this->statuses);

        $this->listener->onSearchQuery($this->getEvent());
    }

    public function testOnSearchQueryInBackend()
    {
        $this->frontendHelper
            ->expects($this->atLeastOnce())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->modifier->expects($this->never())
            ->method('modifyByInventoryStatus');

        $this->configManager->expects($this->never())
            ->method('get')
            ->with($this->frontendConfigPath)
            ->willReturn($this->statuses);

        $this->listener->onSearchQuery($this->getEvent());
    }

    /**
     * @return ProductSearchQueryRestrictionEventListener
     */
    protected function createListener()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper,
            $this->frontendConfigPath
        );

        return $listener;
    }

    /**
     * @return FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFrontendHelper()
    {
        /** @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject $frontendHelper */
        $frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()->getMock();

        return $frontendHelper;
    }

    /**
     * @return ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getQueryModifier()
    {
        /** @var ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject $queryModifier */
        $queryModifier = $this->getMockBuilder(ProductVisibilitySearchQueryModifier::class)->getMock();

        return $queryModifier;
    }

    /**
     * @return ProductSearchQueryRestrictionEvent
     */
    protected function getEvent()
    {
        return new ProductSearchQueryRestrictionEvent(new Query());
    }
}