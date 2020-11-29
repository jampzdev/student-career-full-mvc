<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class SettingsController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function studentAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function careerAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }
}