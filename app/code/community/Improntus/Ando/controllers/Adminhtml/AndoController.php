<?php

/**
 * Class Improntus_Ando_Adminhtml_AndoController
 */
class Improntus_Ando_Adminhtml_AndoController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @throws Exception
     */
    public function solicitarAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$order->getId())
        {
            Mage::throwException("Order does not exist, for the Shipment process to complete");
        }

        if ($order->canShip())
        {
            try
            {
                $shipment = Mage::getModel('sales/service_order', $order)
                    ->prepareShipment($this->_getItemQtys($order));

                /** @var Improntus_Ando_Model_Webservice $andoWs */
                $andoWs = Mage::getModel('ando/webservice');
                $shipmentId = $andoWs->newShipment($order->getAndoQuoteId());

                $arrTracking = array(
                    'carrier_code' => isset($carrier_code) ? $carrier_code : $order->getShippingCarrier()->getCarrierCode(),
                    'title' => isset($shipmentCarrierTitle) ? $shipmentCarrierTitle : $order->getShippingCarrier()->getConfigData('title'),
                    'number' => $shipmentId,
                );

                $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
                $shipment->addTrack($track);

                $shipment->register();

                $this->_saveShipment($shipment, $order, 'La solicitud de envío ANDO fue realizada exitosamente.');

                $order->save();

                Mage::getSingleton("core/session")->addSuccess('La solicitud de envío ANDO fue realizada exitosamente.');
            }
            catch (Exception $e)
            {
                Mage::getSingleton("core/session")->addError('Se produjo un error al intentar generar el envío ANDO. Error: '.$e->getMessage());

                throw $e;
            }
        }
        else
        {
            Mage::getSingleton("core/session")->addNotice('El pedido no puede ser generado en ANDO');
        }

        $this->_redirectReferer();
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param Mage_Sales_Model_Order $order
     * @param string $customerEmailComments
     * @return $this
     */
    public function _saveShipment(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order $order, $customerEmailComments = '')
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($order)
            ->save();

        $emailSentStatus = $shipment->getData('email_sent');
        $ship_data = $shipment->getOrder()->getData();
        $customerEmail = $ship_data['customer_email'];

        if (!is_null($customerEmail) && !$emailSentStatus)
        {
            $shipment->sendEmail(true, $customerEmailComments);
            $shipment->setEmailSent(true);
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function _getItemQtys(Mage_Sales_Model_Order $order)
    {
        $qty = array();

        foreach ($order->getAllItems() as $_eachItem)
        {
            if ($_eachItem->getParentItemId())
            {
                $qty[$_eachItem->getParentItemId()] = $_eachItem->getQtyOrdered();
            }
            else
            {
                $qty[$_eachItem->getId()] = $_eachItem->getQtyOrdered();
            }
        }

        return $qty;
    }

}