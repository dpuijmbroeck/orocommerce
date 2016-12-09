<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Event\AfterContentNodeProcessEvent;
use Oro\Bundle\WebCatalogBundle\Event\BeforeContentNodeProcessEvent;
use Oro\Bundle\WebCatalogBundle\Event\Events;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\Handler\ContentNodeHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var SlugGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $slugGenerator;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ContentNodeHandler
     */
    protected $contentNodeHandler;

    protected function setUp()
    {
        $this->form = $this->getMock(FormInterface::class);
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->slugGenerator = $this->getMockBuilder(SlugGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMock(ObjectManager::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->contentNodeHandler = new ContentNodeHandler(
            $this->form,
            $this->request,
            $this->slugGenerator,
            $this->manager,
            $this->eventDispatcher
        );
    }

    public function testProcessNotPost()
    {
        $contentNode = new ContentNode();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($contentNode);

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(false);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->contentNodeHandler->process($contentNode));
    }

    public function testProcessNotValid()
    {
        $contentNode = new ContentNode();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($contentNode);

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->assertFalse($this->contentNodeHandler->process($contentNode));
    }

    public function testProcess()
    {
        $contentNode = new ContentNode();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($contentNode);

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->slugGenerator->expects($this->once())
            ->method('generate')
            ->with($contentNode);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($contentNode);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertBeforeProcessEventsTriggered($this->form, $contentNode);
        $this->assertAfterProcessEventsTriggered($this->form, $contentNode);

        $this->assertTrue($this->contentNodeHandler->process($contentNode));
    }

    public function testProcessWithDefaultVariant()
    {
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);

        $contentNode = new ContentNode();
        $contentNode->addScope($scope1)
            ->addScope($scope2);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($contentNode);

        $contentVariant = new ContentVariant();
        $contentVariant->addScope($scope1);

        $defaultVariant = new ContentVariant();
        $defaultVariant->setDefault(true);

        $contentNode->addContentVariant($defaultVariant)
            ->addContentVariant($contentVariant);

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->slugGenerator->expects($this->once())
            ->method('generate')
            ->with($contentNode);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($contentNode);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertBeforeProcessEventsTriggered($this->form, $contentNode);
        $this->assertAfterProcessEventsTriggered($this->form, $contentNode);
        $this->assertTrue($this->contentNodeHandler->process($contentNode));
        $actualDefaultVariantScopes = $contentNode->getDefaultVariant()->getScopes();
        $this->assertCount(1, $actualDefaultVariantScopes);
        $this->assertContains($scope2, $actualDefaultVariantScopes);
    }

    public function testEventInterruptsBeforeDataSet()
    {
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);

        $contentNode = new ContentNode();
        $contentNode->addScope($scope1)
            ->addScope($scope2);

        $this->request->expects($this->never())
            ->method('isMethod')
            ->will($this->returnValue('POST'));

        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->request);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(
                function ($name, BeforeContentNodeProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $result = $this->contentNodeHandler->process($contentNode);
        $this->assertFalse($result);
    }

    public function testEventInterruptsBeforeSubmit()
    {
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);

        $contentNode = new ContentNode();
        $contentNode->addScope($scope1)
            ->addScope($scope2);

        $this->form->expects($this->once())
            ->method('setData');

        $this->request->expects($this->once())
            ->method('isMethod')
            ->will($this->returnValue('POST'));

        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->request);

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT)
            ->willReturnCallback(
                function ($name, BeforeContentNodeProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $result = $this->contentNodeHandler->process($contentNode);
        $this->assertFalse($result);
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertBeforeProcessEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET, new BeforeContentNodeProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT, new BeforeContentNodeProcessEvent($form, $entity));
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertAfterProcessEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(Events::BEFORE_FLUSH, new AfterContentNodeProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(Events::AFTER_FLUSH, new AfterContentNodeProcessEvent($form, $entity));
    }
}
