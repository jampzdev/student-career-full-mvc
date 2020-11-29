<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class StudentController extends ControllerBase
{

    public function homeAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

}