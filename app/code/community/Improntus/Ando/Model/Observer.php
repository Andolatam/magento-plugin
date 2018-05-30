<?php

/**
 * Class Improntus_Ando_Model_Observer
 */
class Improntus_Ando_Model_Observer
{
    /**
     * @param $event
     */
    public function adminhtmlWidgetContainerHtmlBefore(Varien_Event_Observer $event)
    {
        $block = $event->getBlock();
        $order = Mage::registry('current_order');
        
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View)
        {
            if(isset($order))
            {
                if($order->getShippingMethod() == 'andomoto_andomoto' || $order->getShippingMethod() == 'andobicicleta_andobicicleta')
                {
                    if(!$order->hasShipments())
                    {
                        $urlSolicitar = Mage::helper("adminhtml")->getUrl('adminhtml/ando/solicitar',array('order_id'=>$order->getId()));

                        $id = $order->getId();
                        $block->addButton(
                            'solicitar_ando_button', array(
                                'label'     => 'Solicitar retiro ANDO',
                                'onclick'   => "location.href='$urlSolicitar'",
                                'class'     => 'go',
                                'id'        => 'solicitar_ando_button'
                            )
                        );
                    }
                    else
                    {
                        $block->addButton(
                            'treggo_button', array(
                                'label'     => 'El retiro ANDO ya fue solicitado',
                                'class'     => 'success',
                                'id'        => 'solicitar_ando_button'
                            )
                        );
                    }
                }
            }
        }
    }
}