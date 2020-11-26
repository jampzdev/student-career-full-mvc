<?php

class TblHeadModules extends \Phalcon\Mvc\Model
{


    public $id;
    public $module_code;
    public $module_name;
    public $status;

    public function initialize()
    {
        $this->hasMany('id', 'TblModules', 'head_module_id', array('alias' => 'Modules'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'tbl_head_modules';
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
