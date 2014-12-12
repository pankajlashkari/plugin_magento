<?php

/**
 * File for PayLane_PayLanePayPal_StandardController class
 *
 * Created on 2011-11-30
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

require_once(dirname(__FILE__) . "/../../common/PayLaneClient.php");

class PayLane_PayLanePayPal_StandardController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Return HTTP variable depending on module configuration - via POST or GET
	 * 
	 * @param string $name variable name
	 * @return string variable value
	 */
	public function getHttpVariable($name)
	{
		$response_method = Mage::getStoreConfig('payment/paylanepaypal/response_method');
		
		if ($response_method == "get")
		{
			return $this->getRequest()->getParam($name);
		}
		else
		{
			return $this->getRequest()->getPost($name);
		}
	}
	
	/**
	 * Catch and parse response from PayLane Service
	 */
	public function returnAction()
	{
		$paylanepaypal = Mage::getSingleton("paylanepaypal/standard");

		$status = $this->getHttpVariable('status');
		$id_sale = $this->getHttpVariable('id_sale');
		$amount = $this->getHttpVariable('amount');
		$description = $this->getHttpVariable('description');
		$currency = $this->getHttpVariable('currency');

		if (empty($status) || empty($id_sale) || empty($amount) || empty($description) || empty($currency))
		{
			return Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));	
		}
		
		$hash_salt = Mage::getStoreConfig('payment/paylanepaypal/hash_salt');
		$hash = sha1($hash_salt . '|' . $status . '|' . $description . '|' . $amount . '|' . $currency . '|' . $id_sale);
		
		if ($hash !== $this->getHttpVariable('hash'))
		{
			return Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		$paylanepaypal->setCurrentOrderPaid($id_sale);
				
		session_write_close(); 
		return $this->_redirect('checkout/onepage/success');
	}
	
	/**
	 * Catch cancel action
	 */
	public function cancelAction()
	{
		Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		return;
	}
	
	/**
	 * Redirect user to PayLane and PayPal secure page
	 */
	public function redirectAction()
	{
		$paylanepaypal = Mage::getSingleton("paylanepaypal/standard");
		$data = $paylanepaypal->getPaymentData();

		$data['return_url'] = Mage::getUrl('paylanepaypal/standard/return');
		$data['cancel_url'] = Mage::getUrl('paylanepaypal/standard/cancel');
		
		// connect to PayLane Direct System		
		$paylane_client = new PayLaneClient();
		
		// get login and password from store config
		$direct_login = Mage::getStoreConfig('payment/paylanepaypal/direct_login');
		$direct_password = Mage::getStoreConfig('payment/paylanepaypal/direct_password');
		
		$status = $paylane_client->connect($direct_login, $direct_password);
		if ($status == false)
		{
			// an error message
	    	$paylanepaypal->addComment("Error processing your payment... Please try again later.", true);
	
	    	session_write_close(); 
	    	$this->_redirect('checkout/onepage/failure');
			return;
		}
		
		$result = $paylane_client->paypalSale($data);
		if ($result == false)
		{
			// an error message
		   	$paylanepaypal->addComment("Error processing your payment... Please try again later.", true);
					
		   	session_write_close(); 
		   	$this->_redirect('checkout/onepage/failure');
			return;
		}
						
		if (isset($result->ERROR))
		{
			// an error message
		   	$paylanepaypal->addComment($result->ERROR->error_description, true);
					
		   	session_write_close(); 
		   	$this->_redirect('checkout/onepage/failure');
		   	return;
		}
						
		if (isset($result->OK))
		{
			$paylanepaypal->setIdPayPalCheckout($result->OK->id_paypal_checkout);
			$paylanepaypal->addComment('id_paypal_checkout=' . $result->OK->id_paypal_checkout);
		   	
		   	Mage::app()->getFrontController()->getResponse()->setRedirect($result->OK->redirect_url);
		   	return;
		}
		else
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}
	}
}