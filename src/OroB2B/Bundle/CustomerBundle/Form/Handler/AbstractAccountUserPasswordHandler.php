<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager;

abstract class AbstractAccountUserPasswordHandler
{
    /**
     * @var AccountUserManager
     */
    protected $userManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @param AccountUserManager $userManager
     * @param TranslatorInterface $translator
     * @param int $ttl
     */
    public function __construct(AccountUserManager $userManager, TranslatorInterface $translator, $ttl)
    {
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->ttl = $ttl;
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return AccountUser|bool
     */
    abstract public function process(FormInterface $form, Request $request);

    /**
     * @param FormInterface $form
     * @param string $message
     * @param array $messageParameters
     */
    protected function addFormError(FormInterface $form, $message, array $messageParameters = [])
    {
        $message = $this->translator->trans($message, $messageParameters);
        $form->addError(new FormError($message));
    }
}
