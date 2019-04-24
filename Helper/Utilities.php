<?php

namespace CheckoutCom\Magento2\Helper;


class Utilities {

	/**
     * Initialize the API client wrapper.
     */
    public function __construct(

    )
    {

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
}