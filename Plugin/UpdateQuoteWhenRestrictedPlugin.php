<?php
/**
 * Copyright © Gigadesign. All rights reserved.
 */
declare(strict_types=1);

namespace Gigadesign\FixInvalidStateChange\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\ChangeQuoteControlInterface as Subject;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Plugin to update the quote if the state change is invalid
 *
 * @author Mark van der Werf <mark@gigadesign.nl>
 */
class UpdateQuoteWhenRestrictedPlugin
{
    /**
     * @var UserContextInterface $userContext
     */
    protected $userContext;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param UserContextInterface $userContext
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        UserContextInterface $userContext,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->userContext = $userContext;
        $this->cartManagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param Subject $subject
     * @param bool $result
     * @param CartInterface $quote
     *
     * @return bool
     */
    public function afterIsAllowed(Subject $subject, bool $result, CartInterface $quote): bool
    {
        if (!$result)
        {
            if (is_null($quote->getCustomerId()) && $this->userContext->getUserId())
            {
                $this->cartManagement->assignCustomer($quote->getId(), $this->userContext->getUserId(), $quote->getStoreId());
            }
            else
            {
                $quote->setCustomerId(null);
                $quote->setCustomerIsGuest(true);
                $quote->setCustomerEmail(null);
                $quote->setCustomerGroupId(0);
                $quote->setCustomerFirstname(null);
                $quote->setCustomerLastname(null);
                $quote->setCustomerDob(null);
                $quote->setCustomerGender(null);
                $quote->setCustomerTaxvat(null);
                $quote->setCheckoutMethod('guest');

                foreach ($quote->getAllAddresses() as $address)
                {
                    $address->setCustomerId(null);
                    $address->setCustomerAddressId(null);
                    $address->save();
                }

                $this->quoteRepository->save($quote);
            }

            return true;
        }

        return $result;
    }
}