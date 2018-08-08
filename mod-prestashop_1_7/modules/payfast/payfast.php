<?php
/**
 * payfast.php
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 * 
 * @author     Ron Darby<ron.darby@payfast.co.za>
 * @version    1.0.5
 * @date       12/12/2013
 *
 * @link       http://www.payfast.co.za/help/prestashop
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

include 'payfast_common.inc.php';

if( !defined( '_PS_VERSION_' ) )
    exit;

class PayFast extends PaymentModule
{
    const LEFT_COLUMN = 0;
    const RIGHT_COLUMN = 1;
    const FOOTER = 2;
    const DISABLE = -1;
    const SANDBOX_MERCHANT_KEY = '46f0cd694581a';
    const SANDBOX_MERCHANT_ID = '10000100';
    
    public function __construct()
    {
        $this->name = 'payfast';
        $this->tab = 'payments_gateways';
        $this->version = constant('PF_MODULE_VER');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);  
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        
        parent::__construct();
       
        $this->author  = 'PayFast';
        $this->page = basename(__FILE__, '.php');
        
        $this->description = $this->l('Accept payments by credit card, EFT and cash from both local and international buyers, quickly and securely with PayFast.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

    }

    public function install()
    {
        if( !parent::install()
            OR !$this->registerHook('paymentOptions') 
            OR !$this->registerHook('paymentReturn') 
            OR !Configuration::updateValue('PAYFAST_MERCHANT_ID', '') 
            OR !Configuration::updateValue('PAYFAST_MERCHANT_KEY', '') 
            OR !Configuration::updateValue('PAYFAST_LOGS', '1') 
            OR !Configuration::updateValue('PAYFAST_MODE', 'test')
            OR !Configuration::updateValue('PAYFAST_PAYNOW_TEXT', 'Pay Now With')
            OR !Configuration::updateValue('PAYFAST_PAYNOW_LOGO', 'on')  
            OR !Configuration::updateValue('PAYFAST_PAYNOW_ALIGN', 'right')
            OR !Configuration::updateValue('PAYFAST_PASSPHRASE', '')  )
        {            
            return false;
        }
            

        return true;
    }

    public function uninstall()
    {
        return ( parent::uninstall() 
            AND Configuration::deleteByName('PAYFAST_MERCHANT_ID') 
            AND Configuration::deleteByName('PAYFAST_MERCHANT_KEY') 
            AND Configuration::deleteByName('PAYFAST_MODE') 
            AND Configuration::deleteByName('PAYFAST_LOGS')
            AND Configuration::deleteByName('PAYFAST_PAYNOW_TEXT') 
            AND Configuration::deleteByName('PAYFAST_PAYNOW_LOGO')            
            AND Configuration::deleteByName('PAYFAST_PAYNOW_ALIGN')
            AND Configuration::deleteByName('PAYFAST_PASSPHRASE')
            );

    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html = '
        <style>
            #content{                    
                padding-top: 10px; 
                width:550px;  
            }                   
            @media only screen and ( min-width: 550px ) {
              #box {
                  width: 110px;
              }                   
            }
            @media only screen and ( max-width: 550px ) {
                #content {
                    width:300px;
                }
                .nobootstrap .margin-form {
                    padding-left: 180px;
                }   
                .nobootstrap label {
                    width: 170px;
                }
                #box {
                    width: 91px;
                }
                body.mobile #content.bootstrap {
                    padding-left: 0px;                        
                }  
            }
            .nobootstrap legend{
                color: #585A69;
                direction: ltr;
                width: auto;
            } 
            .nobootstrap legend img{
                padding: 0px 4px 4px 0;
            }
            .nobootstrap{
                width: auto;
            }   
        </style>
        <div id="content" class="bootstrap">  
        <p style="text-align:center;">
            <a href="https://www.payfast.co.za" target="_blank">
                <img src="'.__PS_BASE_URI__.'modules/payfast/secure_logo.png" alt="PayFast" boreder="0" />
            </a>
        </p><br />';

             

        /* Update configuration variables */
        if( Tools::isSubmit( 'submitPayfast' ) )
        {
            if( $paynow_text =  Tools::getValue( 'payfast_paynow_text' ) )
            {
                 Configuration::updateValue( 'PAYFAST_PAYNOW_TEXT', $paynow_text );
            }

            if( $paynow_logo =  Tools::getValue( 'payfast_paynow_logo' ) )
            {
                 Configuration::updateValue( 'PAYFAST_PAYNOW_LOGO', $paynow_logo );
            }
            if( $paynow_align =  Tools::getValue( 'payfast_paynow_align' ) )
            {
                 Configuration::updateValue( 'PAYFAST_PAYNOW_ALIGN', $paynow_align );
            }
            if( $passPhrase =  Tools::getValue( 'payfast_passphrase' ) )
            {
                 Configuration::updateValue( 'PAYFAST_PASSPHRASE', $passPhrase );
            }
            
            $mode = ( Tools::getValue( 'payfast_mode' ) == 'live' ? 'live' : 'test' ) ;
            Configuration::updateValue('PAYFAST_MODE', $mode );
            if( $mode != 'test' )
            {
                if( ( $merchant_id = Tools::getValue( 'payfast_merchant_id' ) ) AND preg_match('/[0-9]/', $merchant_id ) )
                {
                    Configuration::updateValue( 'PAYFAST_MERCHANT_ID', $merchant_id );
                }           
                else
                {
                    $errors[] = '<div class="warning warn"><h3 style="margin: -20px 1px 15px;">'.$this->l( 'Merchant ID seems to be wrong' ).'</h3></div>';
                }

                if( ( $merchant_key = Tools::getValue( 'payfast_merchant_key' ) ) AND preg_match('/[a-zA-Z0-9]/', $merchant_key ) )
                {
                    Configuration::updateValue( 'PAYFAST_MERCHANT_KEY', $merchant_key );
                }
                else
                {
                    $errors[] = '<div class="warning warn"><h3 style="margin: -20px 1px 15px;">'.$this->l( 'Merchant key seems to be wrong' ).'</h3></div>';
                }                  

                if( !sizeof( $errors ) )
                {
                    //Tools::redirectAdmin( $currentIndex.'&configure=payfast&token='.Tools::getValue( 'token' ) .'&conf=4' );
                }
                
            }
            if( Tools::getValue( 'payfast_logs' ) )
            {
                Configuration::updateValue( 'PAYFAST_LOGS', 1 );
            }
            else
            {
                Configuration::updateValue( 'PAYFAST_LOGS', 0 );
            } 
            foreach( array('displayLeftColumn', 'displayRightColumn', 'displayFooter') as $hookName )
                if ( $this->isRegisteredInHook($hookName) )
                    $this->unregisterHook($hookName);
            if ( Tools::getValue('logo_position') == self::LEFT_COLUMN )
                $this->registerHook('displayLeftColumn');
            else if ( Tools::getValue('logo_position') == self::RIGHT_COLUMN )
                $this->registerHook('displayRightColumn'); 
             else if ( Tools::getValue('logo_position') == self::FOOTER )
                $this->registerHook('displayFooter'); 
            if( method_exists ('Tools','clearSmartyCache') )
            {
                Tools::clearSmartyCache();
            } 
            
        }      
        
       

        /* Display errors */
        if( sizeof($errors) )
        {
            $html .= '<ul style="color: red; font-weight: bold; width: 100%; background: #FFDFDF; ">';
            foreach ( $errors AS $error )
                $html .= '<li> '.$error.'</li>';
            $html .= '</ul>';
        }



        $blockPositionList = array(
            self::DISABLE => $this->l('Disable'),
            self::LEFT_COLUMN => $this->l('Left Column'),
            self::RIGHT_COLUMN => $this->l('Right Column'),
            self::FOOTER => $this->l('Footer'));

        if( $this->isRegisteredInHook('displayLeftColumn') )
        {
            $currentLogoBlockPosition = self::LEFT_COLUMN ;
        }
        elseif( $this->isRegisteredInHook('displayRightColumn') )
        {
            $currentLogoBlockPosition = self::RIGHT_COLUMN; 
        }
        elseif( $this->isRegisteredInHook('displayFooter'))
        {
            $currentLogoBlockPosition = self::FOOTER;
        }
        else
        {
            $currentLogoBlockPosition = -1;
        }
        

    /* Display settings form */
        $html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
          <fieldset>
          <legend><img src="'.__PS_BASE_URI__.'modules/payfast/logo.gif" />'.$this->l('Settings').'</legend>
            <p>'.$this->l('Use "Test" mode to test sandbox payments, and "Live" mode when you are ready to go live.').'</p>
            <label>
              '.$this->l('Mode').'
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="payfast_mode" style="width:60px;">
                <option value="live"'.(Configuration::get('PAYFAST_MODE') == 'live' ? ' selected="selected"' : '').'>'.$this->l('Live').'&nbsp;&nbsp;</option>
                <option value="test"'.(Configuration::get('PAYFAST_MODE') == 'test' ? ' selected="selected"' : '').'>'.$this->l('Test').'&nbsp;&nbsp;</option>
              </select>
            </div></br>
            <p>'.$this->l('You can find your Merchant ID and Merchant Key on your ').'<a href="https://www.payfast.co.za/">'.
            $this->l('PayFast.co.za').'</a>'.$this->l(' account under DASHBOARD.').'</p>
            <label>
              '.$this->l('Merchant ID').'
            </label>
            <div class="margin-form">
            <input type="text" name="payfast_merchant_id" placeholder="e.g. 1000010.." value="'.Tools::getValue('payfast_merchant_id', Configuration::get('PAYFAST_MERCHANT_ID')).'" />
            </div>
            <label>
              '.$this->l('Merchant Key').'
            </label>
            <div class="margin-form">
            <input type="text" name="payfast_merchant_key" placeholder="e.g. 46f0cd69458.." value="'.trim(Tools::getValue('payfast_merchant_key', Configuration::get('PAYFAST_MERCHANT_KEY'))).'" />
            </div></br> 
            <p>'.$this->l('The passphrase is an optional/ extra security feature that must be set on your ').'<a href="https://www.payfast.co.za/">'.
            $this->l('PayFast.co.za').'</a>'.$this->l(' account in order to be used. You can find your passphrase under SETTINGS > Integration SECURITY PASSPHRASE.').'</p>'.
            '<label>
              '.$this->l('Secure Passphrase').'
            </label>
            <div class="margin-form">
            <input type="text" name="payfast_passphrase" placeholder="Must be set on your PayFast account.." value="'.trim(Tools::getValue('payfast_passphrase', Configuration::get('PAYFAST_PASSPHRASE'))).'" />
            </div></br>
            <p>'.$this->l('Enable Debug to log the server-to-server communication. The log file for debugging can be found at ').' '.__PS_BASE_URI__.'modules/payfast/payfast.log. '.$this->l('If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.').'</p>       
            <label>
              '.$this->l('Debug').'
            </label>
            <div class="margin-form" style="margin-top:5px">
              <input type="checkbox" name="payfast_logs"'.(Tools::getValue('payfast_logs', Configuration::get('PAYFAST_LOGS')) ? ' checked="checked"' : '').' />
            </div></br>
            <p>'.$this->l('The following payment option text is displayed during checkout.').'</p>';

        //Pay now text field
        $html .= '<label>
                    '.$this->l('Payment option text').'
                  </label>
                  <div class="margin-form" style="margin-top:5px">
                  <input type="text" name="payfast_paynow_text" value="'. Configuration::get('PAYFAST_PAYNOW_TEXT').'">
                  </div>';

        //Pay Now text preview.
        $html .= '<label>Preview</label>
                  <div>
                    '.Configuration::get('PAYFAST_PAYNOW_TEXT') .
                    '&nbsp&nbsp<img alt="Pay Now With PayFast" title="Pay Now With PayFast" src="'.__PS_BASE_URI__.'modules/payfast/logo.png">
                  </div></br>';

        //image position field
        $html .= '<p>'.$this->l('Select the position where the "Secure Payments by PayFast" image will appear on your website. This will be dependant on your theme.').'</p>
            <label> 
            '.$this->l('Image position').'
            </label>
            <div class="margin-form" style="margin-bottom:18px;width:110px;">
                <select id="box" name="logo_position" >';
        foreach($blockPositionList as $position => $translation)
        {
            $selected = ($currentLogoBlockPosition == $position) ? 'selected="selected"' : '';
            $html .= '<option value="'.$position.'" '.$selected.'>'.$translation.'</option>';
        }
        $html .='
        </select>
    </div>
    <div style="float:right;"><input type="submit" name="submitPayfast" class="button" value="'.$this->l('   Save   ').'" />
    </div>
    <div class="clear">
    </div>
    </fieldset>
    </form>
    <br /><br />
    <fieldset>
      <legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
      <p>- '.$this->l('In order to use your PayFast module, you must insert your PayFast Merchant ID and Merchant Key above.').'</p>
      <p>- '.$this->l('Any orders in currencies other than ZAR will be converted by PrestaShop prior to be sent to the PayFast payment gateway.').'<p>
      <p>- '.$this->l('It is possible to setup an automatic currency rate update using crontab. You will simply have to create a cron job with currency update link available at the bottom of "Currencies" section.').'<p>
    </fieldset>
    </div>';

        return $html;
    }

    private function _displayLogoBlock( $position )
    {    
        $html = '
            <div style="text-align:center;">
                <a href="https://www.payfast.co.za" target="_blank" title="Secure Payments With PayFast">
                    <img src="'.__PS_BASE_URI__.'modules/payfast/secure_logo.png" width="150" />
                </a>
            </div>';
        
        return $html;
    }

    public function hookDisplayRightColumn( $params )
    {
        return $this->_displayLogoBlock(self::RIGHT_COLUMN);
    }

    public function hookDisplayLeftColumn( $params )
    {
        return $this->_displayLogoBlock(self::LEFT_COLUMN);
    }  

    public function hookDisplayFooter( $params )
    {
        $html = '
        <section id="payfast_footer_link" class="footer-block col-xs-12 col-sm-2">        
            <div style="text-align:center;">
                <a href="https://www.payfast.co.za" rel="nofollow" title="Secure Payments With PayFast">
                    <img src="'.__PS_BASE_URI__.'modules/payfast/secure_logo.png"  />
                </a>
            </div>  
        </section>';
        return $html;
    }    

    //new method
    public function hookPaymentOptions( $params )
    {
        if( !$this->active )
        {
            return;
        }
        $payment_options = [
            $this->getCardPaymentOption()
        ];

        return $payment_options;
        
    }

    public function getCardPaymentOption()
    {   
        global $cookie, $cart; 
      
        // Buyer details
        $customer = new Customer((int)($cart->id_customer));
        
        $toCurrency = new Currency(Currency::getIdByIsoCode('ZAR'));
        $fromCurrency = new Currency((int)$cookie->id_currency);
        
        $total = $cart->getOrderTotal();

        $pfAmount = Tools::convertPriceFull( $total, $fromCurrency, $toCurrency );
       
        $data = array();

        $currency = $this->getCurrency((int)$cart->id_currency);
        if( $cart->id_currency != $currency->id )
        {
            // If PayFast currency differs from local currency
            $cart->id_currency = (int)$currency->id;
            $cookie->id_currency = (int)$cart->id_currency;
            $cart->update();
        }
        
        // Use appropriate merchant identifiers
        // Live
        if( Configuration::get('PAYFAST_MODE') == 'live' )
        {
            $data['info']['merchant_id'] = Configuration::get('PAYFAST_MERCHANT_ID');
            $data['info']['merchant_key'] = Configuration::get('PAYFAST_MERCHANT_KEY');
            $data['payfast_url'] = 'https://www.payfast.co.za/eng/process';
        }
        // Sandbox
        else
        {
            $data['info']['merchant_id'] = self::SANDBOX_MERCHANT_ID;
            $data['info']['merchant_key'] = self::SANDBOX_MERCHANT_KEY; 
            $data['payfast_url'] = 'https://sandbox.payfast.co.za/eng/process';
        }
        $data['payfast_paynow_text'] = Configuration::get('PAYFAST_PAYNOW_TEXT');        
        $data['payfast_paynow_logo'] = Configuration::get('PAYFAST_PAYNOW_LOGO');      
        $data['payfast_paynow_align'] = Configuration::get('PAYFAST_PAYNOW_ALIGN');
        // Create URLs
        $data['info']['return_url'] = $this->context->link->getPageLink( 'order-confirmation', null, null, 'key='.$cart->secure_key.'&id_cart='.(int)($cart->id).'&id_module='.(int)($this->id));
        $data['info']['cancel_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__;
        $data['info']['notify_url'] = Tools::getHttpHost( true ).__PS_BASE_URI__.'modules/payfast/validation.php?itn_request=true';
    
        $data['info']['name_first'] = $customer->firstname;
        $data['info']['name_last'] = $customer->lastname;
        $data['info']['email_address'] = $customer->email;
        $data['info']['m_payment_id'] = $cart->id;
        $data['info']['amount'] = number_format( sprintf( "%01.2f", $pfAmount ), 2, '.', '' );
        $data['info']['item_name'] = Configuration::get('PS_SHOP_NAME') .' purchase, Cart Item ID #'. $cart->id; 
        $data['info']['custom_int1'] = $cart->id;       
        $data['info']['custom_str1'] = 'PF_PRESTASHOP_1.7_'.constant('PF_MODULE_VER');            
        $data['info']['custom_str2'] = $cart->secure_key;   

        $pfOutput = '';
        // Create output string
        foreach( ($data['info']) as $key => $val )
            $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
    
        $passPhrase = Configuration::get( 'PAYFAST_PASSPHRASE' );
        if( empty( $passPhrase ) ||  Configuration::get('PAYFAST_MODE') != 'live' )
        {
            $pfOutput = substr( $pfOutput, 0, -1 );
        }
        else
        {
            $pfOutput = $pfOutput."passphrase=".urlencode( $passPhrase );
        }

        $data['info']['signature'] = md5( $pfOutput );
       
        //create the payment option object
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l(Configuration::get('PAYFAST_PAYNOW_TEXT')))
                       ->setAction($data['payfast_url']) //link to payfast
                       ->setInputs([ //payfast values
                            'merchant_id' => [
                                'name' =>'merchant_id',
                                'type' =>'hidden',
                                'value' =>$data['info']['merchant_id'],
                            ],
                            'merchant_key' => [
                                'name' =>'merchant_key',
                                'type' =>'hidden',
                                'value' =>$data['info']['merchant_key'],
                            ],
                            'return_url' => [
                                'name' =>'return_url',
                                'type' =>'hidden',
                                'value' =>$data['info']['return_url'],
                            ],
                            'cancel_url' => [
                                'name' =>'cancel_url',
                                'type' =>'hidden',
                                'value' =>$data['info']['cancel_url'],
                            ],
                            'notify_url' => [
                                'name' =>'notify_url',
                                'type' =>'hidden',
                                'value' =>$data['info']['notify_url'],
                            ],
                            'name_first' => [
                                'name' =>'name_first',
                                'type' =>'hidden',
                                'value' => $data['info']['name_first'],
                            ],
                            'name_last' => [
                                'name' =>'name_last',
                                'type' =>'hidden',
                                'value' => $data['info']['name_last'],
                            ],
                            'email_address' => [
                                'name' =>'email_address',
                                'type' =>'hidden',
                                'value' => $data['info']['email_address'],
                            ],
                            'm_payment_id' => [
                                'name' =>'m_payment_id',
                                'type' =>'hidden',
                                'value' =>$data['info']['m_payment_id'],
                            ],
                            'amount' => [
                                'name' =>'amount',
                                'type' =>'hidden',
                                'value' =>$data['info']['amount'],
                            ],
                            'item_name' => [
                                'name' =>'item_name',
                                'type' =>'hidden',
                                'value' =>$data['info']['item_name'],
                            ],
                            'custom_int1' => [
                                'name' =>'custom_int1',
                                'type' =>'hidden',
                                'value' =>$data['info']['custom_int1'],
                            ],
                            'custom_str1' => [
                                'name' =>'custom_str1',
                                'type' =>'hidden',
                                'value' =>$data['info']['custom_str1'],
                            ],
                            'custom_str2' => [
                                'name' =>'custom_str2',
                                'type' =>'hidden',
                                'value' =>$data['info']['custom_str2'],
                            ],
                            'signature' => [
                                'name' =>'signature',
                                'type' =>'hidden',
                                'value' =>$data['info']['signature'],
                            ],
                        ])
                       ->setAdditionalInformation($this->context->smarty->fetch('module:payfast/payment_info.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));
                        
        return $externalOption;
    }

    public function hookPaymentReturn( $params )
    {
        if (!$this->active)
        {
            return;
        }
        $test = __FILE__;

        return $this->display($test, 'payfast_success.tpl'); 
    }
   
}