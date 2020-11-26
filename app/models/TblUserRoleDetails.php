<?php

class TblUserRoleDetails extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $id_user_role;

    /**
     *
     * @var string
     */
    public $id_module;

    /**
     *
     * @var string
     */
    public $add;

    /**
     *
     * @var string
     */
    public $edit;


    /**
     *
     * @var string
     */
    public $dlte;

    /**
     *
     * @var string
     */
    public $view;

    /**
     *
     * @var string
     */
    public $mask;
    public $import;
    public $export;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('id_module', 'TblModules', 'id', array('alias' => 'Modules'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'tbl_user_role_details';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUserRoleDetails[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUserRoleDetails
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
