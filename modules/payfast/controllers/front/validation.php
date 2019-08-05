<?php
/*
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class PayFastValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {

        require_once( dirname(__FILE__, 5).'/config/config.inc.php' );
        require_once( dirname(__FILE__, 3).'/payfast.php' );

        //$this->setTemplate('payment_return.tpl');
        //$this->setTemplate('module:paymentexample/views/templates/front/payment_return.tpl');
        // $customer = new Customer($cart->id_customer);
        // if (!Validate::isLoadedObject($customer))
        //     Tools::redirect('index.php?controller=order&step=1');
        // $currency = $this->context->currency;
        // $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        // $mailVars = array(
        //     '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
        //     '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
        //     '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
        // );
        // $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
        // Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        // Variable Initialization
        
            // Variable Initialization
            $pfError = false;
            $pfErrMsg = '';
            $pfDone = false;
            $pfData = array();
            $pfHost = (  ( Configuration::get( 'PAYFAST_MODE' ) == 'live' ) ? 'www' : 'sandbox' ) . '.payfast.co.za';
            $pfOrderId = '';
            $pfParamString = '';

            $payfast = new PayFast();

            pflog( 'PayFast ITN call received' );

            //// Notify PayFast that information has been received
            if ( !$pfError && !$pfDone )
            {
                header( 'HTTP/1.0 200 OK' );
                flush();
            }

            //// Get data sent by PayFast
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Get posted data' );

                // Posted variables from ITN
                $pfData = pfGetData();

                pflog( 'PayFast Data: ' . print_r( $pfData, true ) );

                if ( $pfData === false )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_ACCESS;
                }
            }

            //// Verify security signature
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Verify security signature' );

                $passPhrase = Configuration::get( 'PAYFAST_PASSPHRASE' );
                $pfPassPhrase = empty( $passPhrase ) ? null : $passPhrase;

                // If signature different, log for debugging
                if ( !pfValidSignature( $pfData, $pfParamString, $pfPassPhrase ) )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
                }
            }

            //// Verify source IP (If not in debug mode)
            if ( !$pfError && !$pfDone && !PF_DEBUG )
            {
                pflog( 'Verify source IP' );

                if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
                }
            }

            //// Get internal cart
            if ( !$pfError && !$pfDone )
            {
                // Get order data
                $cart = new Cart( (int) $pfData['m_payment_id'] );

                //pflog( "Purchase:\n". print_r( $cart, true )  );
            }

            //// Verify data received
            if ( !$pfError )
            {
                pflog( 'Verify data received' );

                $pfValid = pfValidData( $pfHost, $pfParamString );

                if ( !$pfValid )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_ACCESS;
                }
            }

            //// Check data against internal order
            if ( !$pfError && !$pfDone )
            {
                // pflog( 'Check data against internal order' );
                $fromCurrency = new Currency( Currency::getIdByIsoCode( 'ZAR' ) );
                $toCurrency = new Currency( (int) $cart->id_currency );

                $total = Tools::convertPriceFull( $pfData['amount_gross'], $fromCurrency, $toCurrency );

                // Check order amount
                if ( strcasecmp( $pfData['custom_str2'], $cart->secure_key ) != 0 )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_SESSIONID_MISMATCH;
                }
            }

            $vendor_name = Configuration::get( 'PS_SHOP_NAME' );
            $vendor_url = Tools::getShopDomain( true, true );

            //// Check status and update order
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Check status and update order' );

                $sessionid = $pfData['custom_str2'];
                $transaction_id = $pfData['pf_payment_id'];

                if ( empty( Context::getContext()->link ) )
                {
                    Context::getContext()->link = new Link();
                }

                switch ( $pfData['payment_status'] )
                {
                    case 'COMPLETE':
                        pflog( '- Complete' );

                        // Update the purchase status
                        $payfast->validateOrder( (int) $pfData['custom_int1'], _PS_OS_PAYMENT_, (float) $total,
                            $payfast->displayName, null, array( 'transaction_id' => $transaction_id ), null, false, $pfData['custom_str2'] );

                        break;

                    case 'FAILED':
                        pflog( '- Failed' );

                        // If payment fails, delete the purchase log
                        $payfast->validateOrder( (int) $pfData['custom_int1'], _PS_OS_ERROR_, (float) $total,
                            $payfast->displayName, null, array( 'transaction_id' => $transaction_id ), null, false, $pfData['custom_str2'] );

                        break;

                    case 'PENDING':
                        pflog( '- Pending' );

                        // Need to wait for "Completed" before processing
                        break;

                    default:
                        // If unknown status, do nothing (safest course of action)
                        break;
                }
            }

            // If an error occurred
            if ( $pfError )
            {
                pflog( 'Error occurred: ' . $pfErrMsg );
            }

            // Close log
            pflog( '', true );
            //exit();        
    }
}
