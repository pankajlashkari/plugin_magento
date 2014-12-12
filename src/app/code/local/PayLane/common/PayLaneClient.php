<?php

/**
 * File for PayLaneClient class
 *
 * Created on 2011-09-30
 *
 * @package		paylane-php5-client
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

 
/**
 * PayLaneClient class
 *
 * PHP5 PayLane Client for Direct Web Service
 *
 * @package		paylane-php5-client
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */
 
class PayLaneClient
{
	/**
	 * Holds SOAP client object
	 * 
	 * @var SoapClient
	 */
	private $client = null;
	
	/**
	 * Last error or false if not occured
	 * 
	 * @var boolean|string Error description or false if no error occured
	 */
	private $error = false;
	
	/**
	 * Connect to PayLane Direct Web Service
	 * 
	 * @param string $direct_login Login for PayLane Direct System
	 * @param string $direct_password Password for PayLane Direct System
	 * @return boolean true if connection was successfull, false otherwise
	 */
	public function connect($direct_login, $direct_password)
	{
		try
		{
			$params = array(
				"login" => $direct_login,
				"password" => $direct_password,
			);
			$this->client = new SoapClient("https://direct.paylane.com/wsdl/production/Direct.wsdl", $params);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check the current status of a sale
	 * 
	 * @param array $id_sale_list id_sale_list
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function checkSales($id_sale_list)
	{
		try
		{
			$result = $this->client->checkSales($id_sale_list);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Check if a card is enrolled in the 3-D Secure program
	 * 
	 * @param array $params params same as for the multiSale method (for more see PayLane Direct Service Documentation)
	 * @param string $back_url Back URL
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function checkCard3DSecureEnrollment($params, $back_url)
	{
		try
		{
			$result = $this->client->checkCard3DSecureEnrollment($params, $back_url);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Get status of a performed sale
	 * 
	 * @param float $amount Amount of performed sale
	 * @param string $description Description of performed sale
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function getSaleResult($amount, $description)
	{
		try
		{
			$result = $this->client->getSaleResult($amount, $description);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Perform funds capture previously authorized using multiSale method
	 *
	 * @param unsigned long $id_sale_authorization Identification number of sale
	 * @param $amount Amount
	 * @param $description Short description of sale
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function captureSale($id_sale_authorization, $amount, $description = "")
	{
		try
		{
			$result = $this->client->captureSale($id_sale_authorization, $amount, $description);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Close previously made sale authorization
	 * 
	 * @param usigned long $id_sale_authorization Sale Authorization ID returned by multiSale
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function closeSaleAuthorization($id_sale_authorization)
	{
		try
		{
			$result = $this->client->closeSaleAuthorization($id_sale_authorization);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;		
	}
	
	/**
	 * Perform sale after successful secure3d authorization
	 * 
	 * @param unsigned long $id_secure3d_auth id_secure3d_auth
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function saleBy3DSecureAuthorization($id_secure3d_auth)
	{
		try
		{
			$result = $this->client->saleBy3DSecureAuthorization($id_secure3d_auth);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;	
	}
	
	/**
	 * Perform or initiate a sale using various payment methods
	 * 
	 * @param array $params multi_sale_params structure (for more see PayLane Direct Service Documentation)
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function multiSale($params)
	{
		try
		{
			$result = $this->client->multiSale($params);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Perform a sale that will use cardholder's data from the sale done before (recurring)
	 * 
	 * @param usigned long $id_sale Identification number of sale that identifies the cardholder
	 * @param decimal(12,2) $amount Amount to be charged
	 * @param string $currency Currency for this sale
	 * @param string $description Description of the sale
	 * @param string $card_code Card security code
	 * @param string $processing_date Date when the resale is processed
	 * @param boolean $resale_by_authorization
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function resale($id_sale, $amount, $currency, $description = "", $card_code = "", $processing_date = "", $resale_by_authorization = false)
	{
		try
		{
			$result = $this->client->resale($id_sale, $amount, $currency, $description, $card_code, $processing_date, $resale_by_authorization);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;		
	}
	
	/**
	 * Refund a sale
	 * 
	 * @param usigned long $id_sale Sale ID
	 * @param decimal(12,2) $amount Amount to be refunded (always positive value)
	 * @param string $reason Reason for the refund
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function refund($id_sale, $amount, $reason)
	{
		try
		{
			$result = $this->client->refund($id_sale, $amount, $reason);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;		
	}
	
	/**
	 * Get last error if occured
	 * 
	 * @return boolean|string Error description or false, if not occured
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Initiate or perform PayPal sale
	 * 
	 * @param array params see Direct Web Service documentation for details)
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function paypalSale($params)
	{
		try
		{
			$result = $this->client->paypalSale($params);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Perform PayPal sale authorization
	 * 
	 * @param array params see Direct Web Service documentation for details)
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function paypalAuthorization($params)
	{
		try
		{
			$result = $this->client->paypalAuthorization($params);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Stop recurring profile
	 * 
	 * @param unsigned long $id_paypal_recurring PayPal Recurring ID
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function paypalStopRecurring($id_paypal_recurring)
	{
		try
		{
			$result = $this->client->paypalStopRecurring($id_paypal_recurring);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Get PayLane sale ID by id_paypal_checkout
	 * 
	 * @param unsigned long $id_paypal_checkout PayPal Checkout ID
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function paypalGetSaleId($id_paypal_checkout)
	{
		try
		{
			$result = $this->client->paypalGetSaleId($id_paypal_checkout);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Get PayLane sale authorization ID by id_paypal_checkout
	 * 
	 * @param unsigned long $id_paypal_checkout PayPal Checkout ID
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function paypalGetSaleAuthorizationId($id_paypal_checkout)
	{
		try
		{
			$result = $this->client->paypalGetSaleAuthorizationId($id_paypal_checkout);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Check details of the last sale performed as PayPal recurring transaction
	 * 
	 * @param unsigned long $id_paypal_recurring PayPal Recurring ID
	 * @return StdClass|boolean status object with details or false if error occured
	 */
	public function checkLastPayPalRecurringSale($id_paypal_recurring)
	{
		try
		{
			$result = $this->client->checkLastPayPalRecurringSale($id_paypal_recurring);
		}
		catch (SoapFault $exception)
		{
			$this->error = $exception;
			return false;
		}
		
		return $result;
	}
}
 
?>