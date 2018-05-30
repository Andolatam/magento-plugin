<?php

/**
 * Class Ids_Andreani_Model_Config_Pesomax
 *
 * @author Improntus <http://www.improntus.com>
 */
class Improntus_Ando_Model_Source_PesoMaximo
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            '1'  => '1 kg',
            '3'  => '3 kg',
            '8'  => '8 kg'
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            '1'  => '1 kg',
            '3'  => '3 kg',
            '8'  => '8 kg'
        );
    }
}
