<?php

class TblUser extends \Phalcon\Mvc\Model
{

    public $id;
    public $email_address;
    public $psssword;
    public $complete_name;
    public $user_type;
    public $id_user_role;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('id', 'TblToken', 'user_id', array('alias' => 'TblToken'));
        // $this->belongsTo('id_user_role', 'TblUserRole', 'id', array('alias' => 'UserRole','reusable' => true));
        // $this->belongsTo('id_user_role', 'TblUserRole', 'id', array('alias' => 'UserRole','reusable' => true));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'tbl_user';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUser[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return TblUser
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
