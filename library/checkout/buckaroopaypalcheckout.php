<?php
/**
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * It is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this file
 *
 *  @author    Buckaroo.nl <plugins@buckaroo.nl>
 *  @copyright Copyright (c) Buckaroo B.V.
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include_once _PS_MODULE_DIR_ . 'buckaroo3/library/checkout/checkout.php';

class BuckarooPayPalCheckout extends Checkout
{

    protected $customVars = array();
    final public function setCheckout()
    {
        parent::setCheckout();
        $this->payment_request->sellerProtectionEnabled = boolval(Configuration::get('BUCKAROO_PAYPAL_SELLERPROTECTION'));
        if ($this->payment_request->sellerProtectionEnabled){
            $address = $this->shipping_address;
            if (empty($address)){
                $address = $this->invoice_address;
            }
            if (empty($address)){
                $this->payment_request->sellerProtectionEnabled = false;
                return;
            }
            $this->payment_request->name  = $address->firstname.' '.$address->lastname;
            $this->payment_request->address1 = $address->address1;
            $this->payment_request->address2 = $address->address2;
            $this->payment_request->zipPostal = $address->postcode;
            $this->payment_request->city = $address->city;
            if (!empty($address->id_state)){
                $state = new State($address->id_state);
                $this->payment_request->stateProvince = $state->iso_code;
            }
            else {
                $this->payment_request->stateProvince = '';
            }
            $this->payment_request->countryCode = \Country::getIsoById($address->id_country);
        }
    }

    public function startPayment()
    {
        $this->payment_response = $this->payment_request->pay($this->customVars);
    }

    public function isRedirectRequired()
    {
        return true;
    }

    public function isVerifyRequired()
    {
        return false;
    }

    protected function initialize()
    {
        $this->payment_request = PaymentRequestFactory::create(PaymentRequestFactory::REQUEST_TYPE_PAYPAL);
    }
}
