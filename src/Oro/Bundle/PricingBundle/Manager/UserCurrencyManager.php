<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserSettings;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserCurrencyManager
{
    const SESSION_CURRENCIES = 'currency_by_website';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CurrencyConfigInterface
     */
    protected $currencyConfig;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var BaseUserManager
     */
    protected $userManager;

    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param CurrencyConfigInterface $currencyConfig
     * @param WebsiteManager $websiteManager
     * @param BaseUserManager $userManager
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        CurrencyConfigInterface $currencyConfig,
        WebsiteManager $websiteManager,
        BaseUserManager $userManager
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->currencyConfig = $currencyConfig;
        $this->websiteManager = $websiteManager;
        $this->userManager = $userManager;
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getUserCurrency(Website $website = null)
    {
        $currency = null;
        $website = $this->getWebsite($website);

        if ($website) {
            $user = $this->getLoggedUser();
            if ($user instanceof AccountUser) {
                $userSettings = $user->getWebsiteSettings($website);
                if ($userSettings) {
                    $currency = $userSettings->getCurrency();
                }
            } else {
                $sessionStoredCurrencies = $this->getSessionCurrencies();
                if (array_key_exists($website->getId(), $sessionStoredCurrencies)) {
                    $currency = $sessionStoredCurrencies[$website->getId()];
                }
            }
        }

        $allowedCurrencies = $this->getAvailableCurrencies();
        if (!$currency || !in_array($currency, $allowedCurrencies, true)) {
            $currency = $this->getDefaultCurrency();
        }

        return $currency;
    }

    /**
     * @param string $currency
     * @param Website|null $website
     */
    public function saveSelectedCurrency($currency, Website $website = null)
    {
        $website = $this->getWebsite($website);
        if (!$website) {
            return;
        }

        $user = $this->getLoggedUser();
        if ($user instanceof AccountUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new AccountUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setCurrency($currency);
            $this->userManager->getStorageManager()->flush();
        } else {
            $sessionCurrencies = $this->getSessionCurrencies();
            $sessionCurrencies[$website->getId()] = $currency;
            $this->session->set(self::SESSION_CURRENCIES, $sessionCurrencies);
        }
    }

    /**
     * @return array
     */
    public function getAvailableCurrencies()
    {
        return $this->currencyConfig->getCurrencyList();
    }

    /**
     * @return string|null
     */
    public function getDefaultCurrency()
    {
        return $this->currencyConfig->getDefaultCurrency();
    }

    /**
     * @return null|AccountUser
     */
    protected function getLoggedUser()
    {
        $user = null;
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
        }

        return $user;
    }

    /**
     * @param Website|null $website
     * @return Website|null
     */
    protected function getWebsite(Website $website = null)
    {
        if (!$website) {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        return $website;
    }

    /**
     * @return array
     */
    protected function getSessionCurrencies()
    {
        return (array)$this->session->get(self::SESSION_CURRENCIES);
    }
}
