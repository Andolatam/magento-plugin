<?php

/**
 * Class Improntus_Ando_Helper_Data
 *
 * @author Improntus <http://www.improntus.com>
 */
class Improntus_Ando_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getWebserviceUser()
    {
        return Mage::getStoreConfig('shipping/ando_webservice/user',Mage::app()->getStore());
    }

    /**
     * @return string
     */
    public function getWebservicePass()
    {
        return Mage::getStoreConfig('shipping/ando_webservice/password',Mage::app()->getStore());
    }

    /**
     * @param $carrier
     * @return string
     */
    public function getPesoMaximo($carrier)
    {
        return Mage::getStoreConfig("carriers/$carrier/max_package_weight",Mage::app()->getStore());
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return Mage::getStoreConfig('shipping/ando_webservice/url',Mage::app()->getStore());
    }

    /**
     * @return string
     */
    public function getPromocode()
    {
        return Mage::getStoreConfig('shipping/ando_webservice/promocode',Mage::app()->getStore());
    }

    /**
     * @return array
     */
    public function getDireccionRetiro()
    {
        return array(
            'calle' => Mage::getStoreConfig('shipping/direccion/calle',Mage::app()->getStore()),
            'numero' => Mage::getStoreConfig('shipping/direccion/numero',Mage::app()->getStore()),
            'ciudad' => Mage::getStoreConfig('shipping/direccion/ciudad',Mage::app()->getStore()),
            'provincia' => Mage::getStoreConfig('shipping/direccion/provincia',Mage::app()->getStore()),
            'observaciones' => Mage::getStoreConfig('shipping/direccion/observaciones',Mage::app()->getStore())

        );
    }

    /**
     * @param $regionId
     * @return string
     */
    public function getProvincia($regionId)
    {
        if(is_int($regionId))
        {
            $provincia = Mage::getModel('directory/region')->load($regionId);

            $regionId = $provincia->getName() ? $provincia->getName() : $regionId;
        }

        return $regionId;
    }

    /**
     * @param $andoQuoteId
     */
    public function setAndoQuoteId($andoQuoteId)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->setAndoQuoteId($andoQuoteId);
        $quote->save();
    }
}