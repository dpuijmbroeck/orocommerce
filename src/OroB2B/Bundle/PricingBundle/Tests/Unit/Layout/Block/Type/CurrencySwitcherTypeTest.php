<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PricingBundle\Layout\Block\Type\CurrencySwitcherType;

class CurrencySwitcherTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencySwitcherType
     */
    protected $currencySwitcherType;

    protected function setUp()
    {
        $this->currencySwitcherType = new CurrencySwitcherType();
    }

    public function testGetName()
    {
        $this->assertEquals('currency_switcher', $this->currencySwitcherType->getName());
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver **/
        $resolver = $this->getMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['currencies', 'selected_currency']);
        $this->currencySwitcherType->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $view = new BlockView();

        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block **/
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $options = ['currencies' => ['USD', 'EUR'], 'selected_currency' => 'USD'];
        $this->currencySwitcherType->finishView($view, $block, $options);

        $this->assertArrayHasKey('currencies', $view->vars);
        $this->assertEquals($options['currencies'], $view->vars['currencies']);
        $this->assertArrayHasKey('selected_currency', $view->vars);
        $this->assertEquals($options['selected_currency'], $view->vars['selected_currency']);
    }
}