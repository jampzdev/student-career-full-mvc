<?php

class TblSales extends \Phalcon\Mvc\Model
{
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
 
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'tbl_sales';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblSales[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblSales
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
