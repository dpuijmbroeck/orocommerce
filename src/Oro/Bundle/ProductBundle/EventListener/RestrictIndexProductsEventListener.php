<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictIndexProductsEventListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ProductVisibilityQueryBuilderModifier */
    protected $modifier;

    /** @var string */
    protected $configPath;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     * @param string $configPath
     */
    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        $configPath
    ) {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->configPath = $configPath;
    }

    /**
     * @param RestrictIndexEntitiesEvent $event
     */
    public function onRestrictIndexEntitiesEvent(RestrictIndexEntitiesEvent $event)
    {
        if ($event->getEntityClass() !== Product::class) {
            return;
        }

        $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
        $inventoryStatuses = $this->configManager->get($this->configPath);
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }
}