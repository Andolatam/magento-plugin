<?php

/**
 * Class Improntus_Ando_Model_Webservice
 *
 * @author Improntus <http://www.improntus.com>
 */
class Improntus_Ando_Model_Webservice
{
    /**
     * @var string
     */
    protected $_user;

    /**
     * @var string
     */
    protected $_pass;

    /**
     * @var string
     */
    protected $_apiUrl;

    /**
     * @var Improntus_Ando_Helper_Data
     */
    protected $_helper;

    /**
     * @var array
     */
    protected $_token;

    /**
     * Improntus_Ando_Model_Webservice constructor.
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('ando');

        $this->_user = $this->_helper->getWebserviceUser();
        $this->_pass = $this->_helper->getWebservicePass();
        $this->_apiUrl = $this->_helper->getApiUrl();

        $this->_token = $this->getToken();
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        $loginParams = "email={$this->_user}&password={$this->_pass}"; //
        $curl = curl_init($this->_apiUrl.'login/?'.$loginParams);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al generar el token: '. curl_error($curl);
            Mage::log($error ,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return null;
        }

        try{
            $token = \Zend_Json::decode($response);

            Mage::log(print_r($response,true) ,Zend_Log::DEBUG,'debug_ando.log',true);

            return $token;
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al generar el token: '. $e->getMessage();
            Mage::log($error ,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return null;
        }
    }

    /**
     * @param $shippingParams
     * @return bool|mixed
     */
    public function getShippingQuote($shippingParams)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->_apiUrl}shipment/quote",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($shippingParams),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$this->_token['token']}",
                "Content-Type: application/json"
            ),
        ));

        Mage::log(print_r($this->_token['token'],true) ,Zend_Log::DEBUG,'debug_ando.log',true);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al solicitar cotización: '. curl_error($curl);
            Mage::log($error ,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return false;
        }

        try{
            $cotizacion = \Zend_Json::decode($response);

            if(isset($cotizacion['price'][0]['estimatedPrice']))
            {
                $this->_helper->setAndoQuoteId($cotizacion['quoteID']);

                return $cotizacion['price'][0]['estimatedPrice'];
            }
            if(isset($cotizacion['error']))
            {
                $error = 'Se produjo un error al solicitar cotización: '. $cotizacion['error_description'] . ' ShippingParams: ' .print_r($shippingParams,true);
                Mage::log($error ,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

                return false;
            }

            if(isset($cotizacion['status']))
            {
                $errorDesc = Improntus_Ando_Helper_ErrorCode::getError($cotizacion['status']);

                $error = 'Se produjo un error al solicitar cotización: '. $errorDesc . ' ShippingParams: ' .print_r($shippingParams,true);
                Mage::log($error ,null,'error_ando_'.date('m_Y').'.log',true);

                return false;
            }
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al solicitar cotización: '. $e->getMessage() . ' Response: '. print_r($response,true);
            Mage::log($error ,null,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return null;
        }
    }

    /**
     * @param $quoteId
     * @return bool|null
     */
    public function newShipment($quoteId)
    {
        $curl = curl_init();

        $postParams = [
            'quoteID' => $quoteId,
            'promocode' => '',
            'paymentMethod' => 'checking_account'
        ];

        curl_setopt_array($curl,
            [
                CURLOPT_URL => "{$this->_apiUrl}shipment/new",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postParams),
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$this->_token['token']}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al generar el envio: '. curl_error($curl);
            Mage::log($error ,null,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return false;
        }

        try{
            $shipment = \Zend_Json::decode($response);

            Mage::log('Shipment debug: ' . print_r($response,true) ,null,Zend_Log::CRIT,'debug_ando.log',true);

            curl_close($curl);

            return isset($shipment['trackingID']) ? $shipment['trackingID'] : null;
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al generar el envio: '. $e->getMessage();
            Mage::log($error ,null,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            curl_close($curl);

            return false;
        }
    }

    /**
     * @param $shipmentId
     * @return bool|null
     */
    public function trackShipment($shipmentId)
    {
        $curl = curl_init();

        curl_setopt_array($curl,
            [
                CURLOPT_URL => "{$this->_apiUrl}shipment/track?trackingID=$shipmentId",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$this->_token['token']}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al consultar un envío: '. curl_error($curl);
            Mage::log($error ,null,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            return false;
        }

        try{
            $tracking = \Zend_Json::decode($response);

            curl_close($curl);

            return isset($tracking['status']['statusID']) ? Improntus_Ando_Helper_ShipmentSatus::getShipmentMessage($tracking['status']['statusID']) : null;
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al generar el envio: '. $e->getMessage();
            Mage::log($error ,null,Zend_Log::CRIT,'error_ando_'.date('m_Y').'.log',true);

            curl_close($curl);

            return false;
        }
    }
}