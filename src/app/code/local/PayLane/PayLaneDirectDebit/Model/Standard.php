<?php

/**
 * File for PayLane_PayLaneDirectDebit_Model_Standard class
 *
 * Created on 2011-11-24
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

require_once(dirname(__FILE__) . "/../../common/PayLaneClient.php");

class PayLane_PayLaneDirectDebit_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
	/**
	 * Payment method identification name
	 * 
	 * @var string
	 */
	protected $_code = 'paylanedirectdebit';
	
	/**
	 * Where to find input form to show on checkout page
	 * 
	 * @var string
	 */
	protected $_formBlockType = 'paylanedirectdebit/form_standard';

	/**
     * Can use this payment method in administration panel?
     * 
     * @var boolean
     */
    protected $_canUseInternal = true;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     * 
     * @var boolean
     */
    protected $_canUseCheckout = true;
    
    /**
     * Is this payment method a gateway?
     * 
     * @var boolean
     */
    protected $_isGateway = true;
	
	/**
	 * Validate user data from input form on the checkout page
	 * 
	 * @see Mage_Payment_Model_Method_Abstract::validate()
	 * @return Mage_Payment_Model_Method_Abstract
	 */
	public function validate()
	{
		parent::validate();

		$info = $this->getInfoInstance();
		
		$account_holder = $info->getAdditionalInformation('account_holder');
		$bic = $info->getAdditionalInformation('bic');
		$iban = $info->getAdditionalInformation('iban');
		$account_country = $info->getAdditionalInformation('account_country');
		
		if (strlen($account_country) !== 2)
		{
			$error = $this->_getHelper()->__('Please choose a country');
			
			Mage::throwException($error);
		}

		if (strlen($bic) < 8 || strlen($bic) > 11)
		{
			$error = $this->_getHelper()->__('Please enter a valid BIC/SWIFT code');
			
			Mage::throwException($error);
		}
		
		if (strlen($iban) < 15 || strlen($iban) > 31)
		{
			$error = $this->_getHelper()->__('Please enter a valid account number');
			
			Mage::throwException($error);
		}
		
		if (strlen($account_holder) < 2 || strlen($account_holder) > 30)
		{
			$error = $this->_getHelper()->__('Please enter a valid account holder');
			
			Mage::throwException($error);
		}

		return $this;
	}
	
	/**
	 * Saves data from Direct Debit form
	 * 
	 * @see Mage_Payment_Model_Method_Abstract::assignData()
	 * @return PayLane_PayLaneDirectDebit_Model_Standard
	 */
	public function assignData($data)
	{
		if (!($data instanceof Varien_Object)) 
		{
			$data = new Varien_Object($data);
		}
		
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation('account_holder', $data->getAccountHolder());
		$info->setAdditionalInformation('bic', $data->getBic());
		$info->setAdditionalInformation('iban', $data->getIban());
		$info->setAdditionalInformation('account_country', $data->getAccountCountry());
		
		return $this;
	}
	
	/**
	 * Save id_sale returned from PayLane Direct System
	 * 
	 * @param $id_sale Sale ID
	 */
	public function setPaylaneIdSale($id_sale)
	{
		// get order id
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		if (is_null($order_id))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
		
		// get order
		$order = Mage::getModel('sales/order');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		if (is_null($order))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
		
		$payment = $order->getPayment();
		$payment->setIdSale($id_sale);
		$payment->save();
	}
	
	/**
	 * Add comment to order history
	 * 
	 * @param string $comment Comment (i.e. failure description)
	 * @param boolean $send_notification Send notification to user?
	 */
	public function addComment($comment, $send_notification = false)
	{	
		// get order id
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		if (is_null($order_id))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		// get order
		$order = Mage::getModel('sales/order');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		if (is_null($order))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		$order->addStatusHistoryComment($comment, $send_notification);
		$order->sendNewOrderEmail();
		$order->save();
	}
	
	/**
	 * Set current order status to PENDING
	 */
	public function setPendingStatus($id_sale)
	{
		// get order id
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		if (is_null($order_id))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
		
		// get order
		$order = Mage::getModel('sales/order');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		if (is_null($order))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
		
		$order->setStatus('pending_payment');

		$this->addTransaction($order, $id_sale);

		$order->addStatusHistoryComment("In PayLane Merchant Panel check if payment was processed!");
		$order->addStatusHistoryComment('id_sale=' . $id_sale);
		$order->sendNewOrderEmail();
		$order->save();
	}

	/**
	 * Adds a transaction to the order.
	 * 
	 * @param	mixed	$trx_id		Payment gateway ID
	 * @param	bool	$is_closed	Is the transaction closed?
	 */
	public function addTransaction($order, $trx_id)
	{
		$payment = $order->getPayment();
		
		$payment->setTransactionId($trx_id);		
		$payment->setIsClosed(0);
		$payment->setIsTransactionClosed(0);

		$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
		$payment->save();
	}
	
	/**
 	 * Return redirect url
 	 * 
 	 * @return string
 	 */
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('paylanedirectdebit/standard/pay', array('_secure' => true));
	}
	
	/**
	 * Return payment details required for paylane direct debit sale
	 * 
	 * @return array Payment details
	 */
	public function getPaymentData()
	{	
		// get order id
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		if (is_null($order_id))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		// get order
		$order = Mage::getModel('sales/order');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		if (is_null($order))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		// get order details
		$order_data = $order->_data;
		if (is_null($order_data))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		// get billing address
		$billing_address_data = $order->getBillingAddress()->_data;
		
		// create array with payment details and fill it with data
		$data = array();
		$data['customer'] = array();
		
		if (!is_null($order->getCustomerName()))
		{
			$data['customer']['name'] = $order->getCustomerName();
		}
		
		if (!is_null($billing_address_data['email']))
		{
			$data['customer']['email'] = $billing_address_data['email'];
		}
		
		$data['customer']['address'] = array();
		
		if (!is_null($billing_address_data['street']))
		{
			$data['customer']['address']['street_house'] = str_replace("\n", ", ", $billing_address_data['street']);
		}
		
		if (!is_null($billing_address_data['postcode']))
		{
			$data['customer']['address']['zip'] = $billing_address_data['postcode'];
		}
		
		if (!is_null($billing_address_data['city']))
		{
			$data['customer']['address']['city'] = $billing_address_data['city'];
		}
		
		if (!is_null($billing_address_data['country_id']))
		{
			$data['customer']['address']['country_code'] = $billing_address_data['country_id'];
		}
		
		if (!is_null($billing_address_data['region']))
		{
			$data['customer']['address']['state'] = $billing_address_data['region'];
		}
		
		if (!is_null($order_data['grand_total']))
		{
			// we need to remove last two 0s, magento stores prices in XX.XX00 format
			$data['amount'] = substr($order_data['grand_total'], 0, -2);
		}
		
		if (!is_null($order_data['remote_ip']))
		{
			$data['customer']['ip'] = $order_data['remote_ip'];
		}
		
		if (!is_null($order_data['order_currency_code']))
		{
			$data['currency_code'] = $order_data['order_currency_code'];
		}
		
		$data['payment_method'] = array();
		$data['payment_method']['account_data'] = array();	
		
		$payment = $order->getPayment();
		if (is_null($payment) || !($payment instanceof Varien_Object))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
		
		$data['payment_method']['account_data']['account_holder'] = $payment->getAdditionalInformation('account_holder');
		$data['payment_method']['account_data']['bic'] = $payment->getAdditionalInformation('bic');
		$data['payment_method']['account_data']['iban'] = $payment->getAdditionalInformation('iban');
		$data['payment_method']['account_data']['account_country'] = $payment->getAdditionalInformation('account_country');
		
		$data['fraud_check'] = Mage::getStoreConfig('payment/paylanedirectdebit/fraud_check');		
		
		$description = '';

		// generate transaction description - item x quantity
		foreach ($order->getAllItems() as $item)
		{
			$item_data = $item->_data;
			$description .= $item_data['name'] . ' x ' . substr($item_data['qty_ordered'], 0, -5) . '<br>';
			$description .= '; order_id=' . $order_id;
		}
		$data['product'] = array();
		$data['product']['description'] = substr($description, 0, 200);
		
		return $data;
	}
}
