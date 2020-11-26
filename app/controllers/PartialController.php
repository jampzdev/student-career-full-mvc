<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class PartialController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function headerAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function mainmenuAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function footerAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function commonAction(){
        $this->view->token = $this->_getToken('User');
        $this->view->session = $session;
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }
}
