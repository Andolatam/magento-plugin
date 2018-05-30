<?php

/**
 * Class Improntus_Ando_Model_Carrier_AndoBicicleta
 *
 * @author Improntus <http://www.improntus.com>
 */
class Improntus_Ando_Model_Carrier_AndoBicicleta extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    const CARRIER_CODE = 'andobicicleta';

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return false|Mage_Core_Model_Abstract
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        /** @var Improntus_Ando_Helper_Data $helper */
        $helper = Mage::helper('ando');
        $webservice = Mage::getModel('ando/webservice');

        $pesoTotal = 0;
        $freeBoxes = 0;

        $pesoMaximo = (float)$helper->getPesoMaximo(self::CARRIER_CODE);
        $sku = '';

        $dimensiones = [
            'alto'  => 0,
            'ancho' => 0,
            'largo' => 0
        ];

        foreach ($request->getAllItems() as $_item)
        {
            if($sku != $_item->getSku())
            {
                $sku = $_item->getSku();
                $pesoTotal = ($_item->getQty() * $_item->getWeight()) + $pesoTotal;
                $_producto = $_item->getProduct();

                if ($_item->getFreeShippingDiscount() && !$_item->getProduct()->isVirtual())
                {
                    $freeBoxes += $_item->getQty();
                }

                $dimensiones['alto'] += (int) $_producto->getResource()
                        ->getAttributeRawValue($_producto->getId(),'alto',$_producto->getStoreId()) * $_item->getQty();

                $dimensiones['largo'] += (int) $_producto->getResource()
                        ->getAttributeRawValue($_producto->getId(),'largo',$_producto->getStoreId()) * $_item->getQty();

                $dimensiones['ancho'] = (int) $_producto->getResource()
                        ->getAttributeRawValue($_producto->getId(),'ancho',$_producto->getStoreId()) * $_item->getQty();
            }
        }

        if(isset($freeBoxes))
            $this->setFreeBoxes($freeBoxes);

        $result = Mage::getModel('shipping/rate_result');

        if($pesoTotal >= $pesoMaximo)
        {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage('Su pedido supera el peso máximo permitido por Ando. Por favor divida su orden en más pedidos o consulte al administrador de la tienda.');
            return $error;
        }
        else
        {
            $address =  Mage::app()->getRequest()->getParam('billing') ?  Mage::app()->getRequest()->getParam('billing') :
                Mage::app()->getRequest()->getParam('shipping');

            $quote = Mage::getSingleton('checkout/session')->getQuote();

            if(!$address)
            {
                $shippingAddress = $quote->getShippingAddress();

                $address = [];
                $address['firstname'] = $shippingAddress->getFirstname();
                $address['lastname'] = $shippingAddress->getLastname();
                $address['mail'] = $shippingAddress->getEmail();
                $address['telephone'] = $shippingAddress->getTelephone();
                $address['street'] = [];

                foreach ($shippingAddress->getStreet() as $_street)
                {
                    $address['street'][] = $_street;
                }

                $address['altura'] = $shippingAddress->getAltura();
                $address['city'] = $shippingAddress->getCity();
                $address['region_id'] = $shippingAddress->getRegionId();
                $address['region'] = $shippingAddress->getRegion();
            }

            $direccionRetiro = $helper->getDireccionRetiro();

            $costoEnvio = $webservice->getShippingQuote(
            [
                'shipFrom_province'      => $direccionRetiro['provincia'],
                'shipFrom_addressStreet' => $direccionRetiro['calle'],
                'shipFrom_addressNumber' => $direccionRetiro['numero'],
                'shipFrom_city'          => $direccionRetiro['ciudad'],
                'shipFrom_country'       => 'Argentina',
                'startSpecialInstructions'=> $direccionRetiro['observaciones'],
                'shipTo_firstName'       => $address['firstname'],
                'shipTo_lastName'        => $address['lastname'],
                'shipTo_email'           => $quote->getCustomerEmail(),
                'shipTo_phone'           => $address['telephone'],
                'shipTo_addressStreet'   => trim(implode(' ',$address['street'])),
                'shipTo_addressNumber'   => $address['altura'],
                'shipTo_city'            => $address['city'],
                'shipTo_province'        => $address['region_id'] ? $helper->getProvincia($address['region_id']) : $address['region'],
                'shipTo_country'         => 'Argentina',
                'endSpecialInstructions' => $address['observaciones'],
                'packageWidth'           => 2,
                'packageLarge'           => 2,
                'packageHeight'          => 2,
                'packageWeight'          => $pesoTotal,
                'shippingMethod'         => 'BIKE',
                'digitalSignature'       => false,
                'currency'               => 'ARS',
                'promocode'              => null
            ]);

            if($costoEnvio)
            {
                /* @var $rate Mage_Shipping_Model_Rate_Result_Method */
                $rate = Mage::getModel('shipping/rate_result_method');

                $rate->setCarrier($this->_code);
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($this->_code);
                $rate->setMethodDescription($this->getConfigData('description'));

                if($request->getFreeShipping() == true || $request->getPackageQty() == $this->getFreeBoxes())
                {
                    $costoEnvio = '0.00';
                }

                $rate->setPrice($costoEnvio);
                $rate->setCost($costoEnvio);

                $result->append($rate);
            }
            else
            {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage('No existen cotizaciones para el código postal ingresado');
                $error->setMethodDescription($this->getConfigData('description'));

                return $error;
            }
        }

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods() 
    {
        return array($this->_code => $this->getConfigData('name'));
    }
}

