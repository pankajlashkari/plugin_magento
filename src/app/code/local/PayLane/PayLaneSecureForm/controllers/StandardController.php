<?php

/**
 * File for PayLane_PayLaneSecureForm_StandardController class
 *
 * Created on 2011-10-30
 *
 * @package		paylane-utils-magento
 * @copyright	2011 PayLane Sp. z o.o.
 * @author		Michal Nowakowski <michal.nowakowski@paylane.com>
 * @version		SVN: $Id$
 */

class PayLane_PayLaneSecureForm_StandardController extends Mage_Core_Controller_Front_Action
{
	const STATUS_PERFORMED = "PERFORMED";
	const STATUS_PENDING = "PENDING";
	const STATUS_CLEARED = "CLEARED";
	const STATUS_ERROR = "ERROR";

	private static $transaction_statuses = array(
			self::STATUS_ERROR,
			self::STATUS_CLEARED,
			self::STATUS_PENDING,
			self::STATUS_PERFORMED,
	);

	/**
	 * Redirect user to PayLane Secure Form
	 */
	public function redirectAction()
	{
		$this->getResponse()->setBody($this->getLayout()->createBlock('paylanesecureform/redirect')->toHtml());
	}

	/**
	 * Return HTTP variable depending on module configuration - via POST or GET
	 *
	 * @param string $name variable name
	 * @return string variable value
	 */
	public function getHttpVariable($name)
	{
		$response_method = Mage::getStoreConfig('payment/paylanesecureform/response_method');

		if ($response_method == "get")
		{

			if (isset($_GET[$name]))
			{
				return $_GET[$name];
			}

			return null;
		}
		else
		{

			if (isset($_POST[$name]))
			{
				return $_POST[$name];
			}

			return $_POST[$name];
		}
	}

	/**
	 * Check PayLane response and change order status
	 */
	public function backAction()
	{
		$paylanesecureform = Mage::getSingleton("paylanesecureform/standard");

		//values returned from PayLane Secure Form
		$paylane_data = array();

		if (!is_null($this->getHttpVariable('status')))
		{
			$paylane_data['status'] = $this->getHttpVariable('status');
			if (!in_array($paylane_data['status'], self::$transaction_statuses))
			{
				Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
				return;
			}
		}
		else
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		if (!is_null($this->getHttpVariable('description')))
		{
			$paylane_data['description'] = $this->getHttpVariable('description');
		}
		else
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		// get order
		$order = Mage::getModel('sales/order');
		$order = Mage::getModel('sales/order')->loadByIncrementId($paylane_data['description']);

		if (is_null($order))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
			return;
		}

		if ($paylane_data['status'] == self::STATUS_ERROR)
		{
			$msg = '';

			if (isset($paylane_data['id_error']))
			{
				$msg = "id error : " . $paylane_data['id_error'];
			}

			$order->addStatusHistoryComment('Status = ' . $paylane_data['status'] . " " . $msg);
			$order->save();

			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		if (!is_null($this->getHttpVariable('id_sale')))
		{
			$paylane_data['id_sale'] = $this->getHttpVariable('id_sale');
		}

		if (!is_null($this->getHttpVariable('id_authorization')))
		{
			$paylane_data['id_authorization'] = $this->getHttpVariable('id_authorization');
		}

		if (!is_null($this->getHttpVariable('amount')) && !is_null($this->getHttpVariable('currency')))
		{
			$paylane_data['amount'] = $this->getHttpVariable('amount');
			$paylane_data['currency'] = $this->getHttpVariable('currency');
		}
		else
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		if (!is_null($this->getHttpVariable('hash')))
		{
			$paylane_data['hash'] = $this->getHttpVariable('hash');
		}

		// get original order details
		$original_data = $paylanesecureform->getOriginalPaymentData($paylane_data['description']);

		// check merchant_transaction_id, amount, currency code
		if ( ($original_data['description'] != $paylane_data['description']) ||
		     ($original_data['amount'] != $paylane_data['amount']) ||
		     ($original_data['currency'] != $paylane_data['currency']))
		{
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		if (isset($paylane_data['hash']))
		{
			$redirect_hash = $paylanesecureform->calculateRedirectHash($paylane_data);

			// compare hash
			if ($paylane_data['hash'] != $redirect_hash)
			{
				Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
				return;
			}
		}
		else
		{
			// hash was not set!
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/failure"));
			return;
		}

		$payment = $order->getPayment();
		$payment->setTransactionId($paylane_data['id_sale']);

		// set processing status if payment is pending
		if ($paylane_data['status'] == self::STATUS_PENDING)
		{
			$payment->setIsClosed(0);
			$payment->setIsTransactionClosed(0);
			$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
			$order->addStatusHistoryComment("This sale is now being processed by PayLane. To monitor current sale status please login to PayLane Merchant Panel. id_sale = " . $paylane_data['id_sale']);
		}
		else
		{
			// ok, now everything is correct, we can change order status
			$order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);

			try
			{
				if (!$order->canInvoice())
				{
					Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
				}

				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

				if (!$invoice->getTotalQty())
				{
					Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
				}

				$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
				$invoice->register();
				$transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
				$transactionSave->save();
			}
			catch (Mage_Core_Exception $e)
			{
			}

			$order->sendNewOrderEmail();
			$order->addStatusHistoryComment("In PayLane Merchant Panel check if payment was processed correctly id_sale = " . $paylane_data['id_sale']);
		}

		$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);

		$payment->save();
		$order->save();

		Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("checkout/onepage/success"));
		return;
	}
}