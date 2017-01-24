<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var LocalizedSlugType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LocalizedSlugType($this->configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->formType, 'preSetData']);

        $this->formType->buildForm($builder, []);
    }

    public function testOnPreSetDataForUpdateConfirmationEnabled()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => true]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->once())
            ->method('add')
            ->with(LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = $this->createPersistentCollection();
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    /**
     * @dataProvider disabledConfirmationStrategiesDataProvider
     * @param string $strategy
     */
    public function testOnPreSetDataForUpdateConfirmationDisabled($strategy)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => true]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = $this->createPersistentCollection();
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    /**
     * @return array
     */
    public function disabledConfirmationStrategiesDataProvider()
    {
        return [
            [Configuration::STRATEGY_ALWAYS],
            [Configuration::STRATEGY_NEVER]
        ];
    }

    public function testOnPreSetDataForUpdateConfirmationDisabledByOption()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => false]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = $this->createPersistentCollection();
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    public function testOnPreSetDataForCreate()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->willReturn(['create_redirect_enabled' => true]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(new ArrayCollection()));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals(
                        'ororedirect/js/app/components/localized-slug-component',
                        $options['localized_slug_component']
                    );
                    $this->assertEquals('oro_api_slugify_slug', $options['slugify_route']);
                    $this->assertFalse($options['slug_suggestion_enabled']);
                    $this->assertFalse($options['create_redirect_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setDefined')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildViewForSlugifyComponent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'form-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'source-name',
            'localized_slug_component' => 'some-component-path',
            'slugify_route' => 'some-route',
            'slug_suggestion_enabled' => true,
            'create_redirect_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('localized_slug_component', $view->vars);
        $this->assertEquals('some-component-path', $view->vars['localized_slug_component']);
        $this->assertEquals(
            '[name^="form-name[source-name][values]"]',
            $view->vars['localized_slug_component_options']['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['localized_slug_component_options']['slugify_component_options']['target']
        );
        $this->assertEquals(
            'some-route',
            $view->vars['localized_slug_component_options']['slugify_component_options']['slugify_route']
        );
    }

    public function testBuildViewForConfirmationComponent()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $data = $this->createPersistentCollection();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'localized_slug_component' => 'some-component-path',
            'create_redirect_enabled' => true,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('localized_slug_component', $view->vars);
        $this->assertEquals('some-component-path', $view->vars['localized_slug_component']);
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['localized_slug_component_options']['confirmation_component_options']['slugFields']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][' . LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME . ']"]',
            $view->vars['localized_slug_component_options']['confirmation_component_options']['createRedirectCheckbox']
        );
    }

    public function testBuildViewWithComponentsDisabled()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $data = $this->createPersistentCollection();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'localized_slug_component' => 'some-component-path',
            'create_redirect_enabled' => false,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayNotHasKey('localized_slug_component', $view->vars);
        $this->assertArrayNotHasKey('localized_slug_component_options', $view->vars);
    }

    /**
     * @dataProvider disabledConfirmationStrategiesDataProvider
     * @param string $strategy
     */
    public function testBuildViewWithComponentsDisabledStrategy($strategy)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $data = $this->createPersistentCollection();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'create_redirect_enabled' => true,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayNotHasKey('localized_slug_component', $view->vars);
        $this->assertArrayNotHasKey('confirmation_component', $view->vars);
    }

    /**
     * @return PersistentCollection
     */
    protected function createPersistentCollection()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection(['some-entry']);

        return new PersistentCollection($em, $classMetadata, $collection);
    }
}
