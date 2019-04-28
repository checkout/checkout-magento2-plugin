<?php

namespace CheckoutCom\Magento2\Helper;


class Utilities {

	/**
     * Utilities constructor.
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->urlInterface      = $urlInterface;
        $this->customerSession   = $customerSession;
	}
	
	/**
     * Convert a date string to ISO8601 format.
     */
    public function formatDate($dateString) {
        try {
            $datetime = new \DateTime($dateString);
            return $datetime->format(\DateTime::ATOM);
        } 
        catch(\Exception $e) {
            return null;
        }
    }

    /**
     * Force authentication if the user is not logged in.
     */    
    public function isLoggedIn() {
        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setAfterAuthUrl($this->urlInterface->getCurrentUrl());
            $this->customerSession->authenticate();
        }    
    }
}