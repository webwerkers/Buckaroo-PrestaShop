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

require_once(dirname(__FILE__) . '/../../library/logger.php');
require_once(dirname(__FILE__) . '/../abstract.php');
require_once(dirname(__FILE__) . '/../soap.php');
require_once(dirname(__FILE__) . '/responsefactory.php');

abstract class PaymentMethod extends BuckarooAbstract
{
    //put your code here
    protected $type;

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public $currency;
    public $amountDedit;
    public $amountCredit = 0;
    public $orderId;
    public $invoiceId;
    public $description;
    public $OriginalTransactionKey;
    public $returnUrl;
    public $mode;
    public $version;
    public $usecreditmanagment = 0;
    public $usenotification = 0;
    protected $data = array();

    public function pay($customVars = Array())
    {
        $this->data['services'][$this->type]['action'] = 'Pay';
        $this->data['services'][$this->type]['version'] = $this->version;

        if ($this->usenotification && !empty($customVars['Customeremail'])){
            $this->data['services']['notification']['action'] = 'ExtraInfo';
            $this->data['services']['notification']['version'] = '1';
            $this->data['customVars']['notification']['NotificationType'] = $customVars['Notificationtype'];
            $this->data['customVars']['notification']['CommunicationMethod'] = 'email';
            $this->data['customVars']['notification']['RecipientEmail'] = $customVars['Customeremail'];
            $this->data['customVars']['notification']['RecipientFirstName'] = $customVars['CustomerFirstName'];
            $this->data['customVars']['notification']['RecipientLastName'] = $customVars['CustomerLastName'];
            $this->data['customVars']['notification']['RecipientGender'] = $customVars['Customergender'];
            if (!empty($customVars['Notificationdelay'])) {
                $this->data['customVars']['notification']['SendDatetime'] = $customVars['Notificationdelay'];
            }
        }

        return $this->payGlobal();
    }

    public function refund()
    {
        $this->data['services'][$this->type]['action'] = 'Refund';
        $this->data['services'][$this->type]['version'] = $this->version;

        return $this->refundGlobal();
    }

    public function payGlobal()
    {
        $this->data['currency'] = $this->currency;
        $this->data['amountDebit'] = $this->amountDedit;
        $this->data['amountCredit'] = $this->amountCredit;
        $this->data['invoice'] = $this->invoiceId;
        $this->data['order'] = $this->orderId;
        $this->data['description'] = $this->description;
        $this->data['returnUrl'] = $this->returnUrl;
        $this->data['mode'] = $this->mode;
        $soap = new Soap($this->data);
        return ResponseFactory::getResponse($soap->transactionRequest());
    }

    public function refundGlobal()
    {
        $this->data['currency'] = $this->currency;
        $this->data['amountDebit'] = $this->amountDedit;
        $this->data['amountCredit'] = $this->amountCredit;
        $this->data['invoice'] = $this->invoiceId;
        $this->data['order'] = $this->orderId;
        $this->data['description'] = $this->description;
        $this->data['OriginalTransactionKey'] = $this->OriginalTransactionKey;
        $this->data['returnUrl'] = $this->returnUrl;
        $this->data['mode'] = $this->mode;
        $soap = new Soap($this->data);
        return ResponseFactory::getResponse($soap->transactionRequest());
    }

    public static function isIBAN($iban)
    {

        // Normalize input (remove spaces and make upcase)
        $iban = Tools::strtoupper(str_replace(' ', '', $iban));

        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            $country = Tools::substr($iban, 0, 2);
            $check = (int)(Tools::substr($iban, 2, 2));
            $account = Tools::substr($iban, 4);

            // To numeric representation
            $search = range('A', 'Z');
            $replace = array();
            foreach (range(10, 35) as $tmp) {
                $replace[] = (string)($tmp);
            }
            $numstr = str_replace($search, $replace, $account . $country . '00');

            // Calculate checksum
            $checksum = (int)(Tools::substr($numstr, 0, 1));
            for ($pos = 1; $pos < Tools::strlen($numstr); $pos++) {
                $checksum *= 10;
                $checksum += (int)(Tools::substr($numstr, $pos, 1));
                $checksum %= 97;
            }

            return ((98 - $checksum) == $check);
        } else {
            return false;
        }
    }
}
