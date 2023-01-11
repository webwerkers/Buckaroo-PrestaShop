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

require_once(dirname(__FILE__) . '/../paymentmethod.php');

class BuckarooPayPal extends PaymentMethod
{
    public bool $sellerProtectionEnabled;
    public string $name;
    public string $address1;
    public string $address2;
    public string $zipPostal;
    public string $city;
    public string $stateProvince;
    public string $countryCode;

    public function __construct()
    {
        $this->type = "paypal";
        $this->version = 1;
        $this->mode = Config::getMode($this->type);
    }


    public function pay($customVars = Array())
    {
        if($this->sellerProtectionEnabled){
            $serviceName = 'Paypal';
            $this->data['services'][$serviceName]['action'] = 'ExtraInfo';
            $this->data['services'][$serviceName]['version'] = '1';
            $this->data['customVars'][$serviceName]['Name'] =        $this->name;
            $this->data['customVars'][$serviceName]['Street1'] =     $this->address1;
            $this->data['customVars'][$serviceName]['CityName'] =    $this->city;
            $this->data['customVars'][$serviceName]['PostalCode'] =  $this->zipPostal;
            $this->data['customVars'][$serviceName]['Country'] =     $this->countryCode;
            if (!empty($this->address2)){
                $this->data['customVars'][$serviceName]['Street2'] =    $this->address2;
            }
            if (!empty($this->stateProvince)){
                $this->data['customVars'][$serviceName]['StateOrProvince'] = $this->stateProvince;
            }
            $this->data['customVars'][$serviceName]['AddressOverride'] = 'true';
        }
        return parent::pay($customVars);
    }


}
