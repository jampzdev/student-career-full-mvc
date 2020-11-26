<?php

class TblModules extends \Phalcon\Mvc\Model
{
    public $id;
    public $head_module_id;
    public $module_code;
    public $module_name;
    public $status;

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
        return 'tbl_modules';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblModules[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblModules
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
