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

include_once _PS_MODULE_DIR_ . 'buckaroo3/api/paymentmethods/paymentrequestfactory.php';

abstract class Checkout
{

    const CHECKOUT_TYPE_PAYPAL          = 'buckaroopaypal';
    const CHECKOUT_TYPE_EMPAYMENT       = 'empayment';
    const CHECKOUT_TYPE_IDEAL           = 'ideal';
    const CHECKOUT_TYPE_DIRECTDEBIT     = 'directdebit';
    const CHECKOUT_TYPE_SEPADIRECTDEBIT = 'sepadirectdebit';
    const CHECKOUT_TYPE_GIROPAY         = 'giropay';
    const CHECKOUT_TYPE_PAYSAFECARD     = 'paysafecard';
    const CHECKOUT_TYPE_MISTERCASH      = 'bancontactmrcash';
    const CHECKOUT_TYPE_GIFTCARD        = 'giftcard';
    const CHECKOUT_TYPE_CREDITCARD      = 'creditcard';
    const CHECKOUT_TYPE_EMAESTRO        = 'maestro';
    const CHECKOUT_TYPE_SOFORTBANKING   = 'sofortueberweisung';
    const CHECKOUT_TYPE_TRANSFER        = 'transfer';
    const CHECKOUT_TYPE_AFTERPAY        = 'afterpay';

    // Request types (Payment Methods).
    public static $payment_method_type = array(
        Checkout::CHECKOUT_TYPE_PAYPAL          => 'BuckarooPayPal',
        Checkout::CHECKOUT_TYPE_EMPAYMENT       => 'Empayment',
        Checkout::CHECKOUT_TYPE_IDEAL           => 'IDeal',
        Checkout::CHECKOUT_TYPE_DIRECTDEBIT     => 'DirectDebit',
        Checkout::CHECKOUT_TYPE_SEPADIRECTDEBIT => 'SepaDirectdebit',
        Checkout::CHECKOUT_TYPE_GIROPAY         => 'Giropay',
        Checkout::CHECKOUT_TYPE_PAYSAFECARD     => 'PaySafeCard',
        Checkout::CHECKOUT_TYPE_MISTERCASH      => 'MisterCash',
        Checkout::CHECKOUT_TYPE_GIFTCARD        => 'GiftCard',
        Checkout::CHECKOUT_TYPE_CREDITCARD      => 'CreditCard',
        Checkout::CHECKOUT_TYPE_EMAESTRO        => 'EMaestro',
        Checkout::CHECKOUT_TYPE_SOFORTBANKING   => 'Sofortbanking',
        Checkout::CHECKOUT_TYPE_TRANSFER        => 'Transfer',
        Checkout::CHECKOUT_TYPE_AFTERPAY        => 'AfterPay',
    );

    //protected $current_order;
    protected $payment_request;
    protected $payment_response;
    /* @var $cart CartCore */
    protected $cart;
    protected $customer;
    protected $invoice_address;
    protected $shipping_address;
    protected $products;
    protected $reference;

    /**
     * @param mixed $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    public $returnUrl;

    public function __construct($cart)
    {
        $this->initialize();

        $this->cart             = $cart;
        $this->customer         = new Customer($cart->id_customer);
        $this->invoice_address  = new Address((int) ($cart->id_address_invoice));
        $this->shipping_address = null;
        if ($cart->id_address_invoice != $cart->id_address_delivery) {
            $this->shipping_address = new Address((int) ($cart->id_address_delivery));
        }
        $this->products = $this->cart->getProducts();
    }

    abstract protected function initialize();

    protected function setCheckout()
    {

        $this->payment_request->amountDedit = (string) ((float) $this->cart->getOrderTotal(true, Cart::BOTH));
        $currency                           = new Currency((int) $this->cart->id_currency);
        $this->payment_request->currency    = $currency->iso_code;
        $this->payment_request->description = Configuration::get('BUCKAROO_TRANSACTION_LABEL');
        $reference                          = $this->reference . '_' . $this->cart->id;
        $this->payment_request->invoiceId   = $reference;
        $this->payment_request->orderId     = $reference;
        $this->payment_request->returnUrl   = $this->returnUrl;
    }

    public function startPayment()
    {
        $this->payment_response = $this->payment_request->pay();
    }

    public function isRequestSucceeded()
    {
        if (!empty($this->payment_response) && $this->payment_response->hasSucceeded()) {
            return true;
        }
        return false;
    }

    abstract public function isRedirectRequired();

    public function doRedirect($redirect_url = null)
    {
        if (is_null($redirect_url)) {
            $redirect_url = $this->payment_response->getRedirectUrl();
        }
        Tools::redirect($redirect_url);
        exit(0);
    }

    public function getStatusCode()
    {
        if (isset($this->payment_response) && isset($this->payment_response->statuscode)) {
            return $this->payment_response->statuscode;
        }
        return 0;
    }

    public function getResponse()
    {
        if (isset($this->payment_response)) {
            return $this->payment_response;
        }
        return null;
    }

    /**
     * Given an checkout_type_id, return an instance of that subclass.
     * @param int checkout_type_id
     * @param array $data
     * @return Address subclass
     */
    final public static function getInstance($payment_method, $cart)
    {

        $class_name = self::$payment_method_type[$payment_method] . "Checkout";
        checkoutautoload($class_name); //Try to find class in api directory
        if (!class_exists($class_name)) {
            throw new Exception('Payment method not found', '1'); //TODO: ExceptionPayment
        }
        return new $class_name($cart);
    }

    /**
     * Given an checkout_type_id, return an instance of that subclass.
     * @param int checkout_type_id
     * @param array $data
     * @return Address subclass
     */
    final public static function getInstanceRefund($payment_method)
    {
        $payment_method = Tools::strtolower($payment_method);
        $class_name     = self::$payment_method_type[$payment_method] . "Checkout";
        checkoutautoload($class_name); //Try to find class in api directory
        if (!class_exists($class_name)) {
            throw new Exception('Payment method not found', '1'); //TODO: ExceptionPayment
        }
        return new $class_name(null);
    }

    /**
     * Split address to parts
     *
     * @param string $address
     * @return array
     */
    protected function getAddressComponents($address)
    {
        $result                    = array();
        $result['house_number']    = '';
        $result['number_addition'] = '';

        $address = str_replace(array('?', '*', '[', ']', ',', '!'), ' ', $address);
        $address = preg_replace('/\s\s+/', ' ', $address);

        preg_match('/^([0-9]*)(.*?)([0-9]+)(.*)/', $address, $matches);

        if (!empty($matches[2])) {
            $result['street']          = trim($matches[1] . $matches[2]);
            $result['house_number']    = trim($matches[3]);
            $result['number_addition'] = trim($matches[4]);
        } else {
            $result['street'] = $address;
        }

        return $result;
    }
}

function checkoutautoload($payment_method)
{
    $class_name = Tools::strtolower($payment_method);
    $path       = dirname(__FILE__) . "/{$class_name}.php";
    if (file_exists($path)) {
        require_once $path;
    } else {
        die('Class not found!');
    }
}
