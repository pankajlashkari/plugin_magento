<?php

/**
 * File for PayLane_PayLaneDirectDebit_StandardController class
 *
 * Created on 2011-11-23
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

require_once(dirname(__FILE__) . "/../../common/PayLaneClient.php");

class PayLane_PayLaneDirectDebit_StandardController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Call PayLane Direct System and parse response
	 */
	public function payAction()
	{
		$paylanedirectdebit = Mage::getSingleton("paylanedirectdebit/standard");
		$data = $paylanedirectdebit->getPaymentData();
		
		if (is_null($data))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
		}
		
		echo "Your payment is being processed...";
		
		// connect to PayLane Direct System		
		$paylane_client = new PayLaneClient();
		
		// get login and password from store config
		$direct_login = Mage::getStoreConfig('payment/paylanedirectdebit/direct_login');
		$direct_password = Mage::getStoreConfig('payment/paylanedirectdebit/direct_password');
		
		$status = $paylane_client->connect($direct_login, $direct_password);
		if ($status == false)
		{
			// an error message
	    	$paylanedirectdebit->addComment("Error processing your payment... Please try again later.", true);

	    	$this->_redirect('checkout/onepage/failure');
			return;
		}
		
		$result = $paylane_client->multiSale($data);
		if ($result == false)
		{

			// an error message
	    	$paylanedirectdebit->addComment("Error processing your payment... Please try again later.", true);
	
	    	$this->_redirect('checkout/onepage/failure');
			return;
		}
		
		if (isset($result->ERROR))
		{
			// an error message
	    	$paylanedirectdebit->addComment($result->ERROR->error_description, true);
	
	    	$this->_redirect('checkout/onepage/failure');
	    	return;
		}
		
		if (isset($result->OK))
		{
			$paylanedirectdebit->setPendingStatus($result->OK->id_sale);
	
	    	session_write_close(); 
	    	$this->_redirect('checkout/onepage/success');
	    	return;
		}
	}
}