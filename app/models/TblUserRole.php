<?php

class TblUserRole extends \Phalcon\Mvc\Model
{

    public $id;
    public $user_role;
    public $status;
    public $created_by;
    public $created_at;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('created_by', 'TblUser', 'id', array('alias' => 'User','reusable' => true));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'tbl_user_role';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUserRole[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUserRole
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
