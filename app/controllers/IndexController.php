<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class IndexController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function indexAction()
    {

    }

    public function homeAction()
    {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->token = $this->_getToken('User');
    }

    public function loginAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function authAction(){
        $this->view->disable();

        $post = $this->request->getJsonRawBody();
        if(!$post){
          $this->_respondInvalid(2);
        }

        // $this->_checkToken('User',$post->token_key,$post->token_value);

        $email                  = $post->username;
        $password               = hash('sha256',$post->password);

        $user                   =  TblUser::findFirst(array(
                "conditions" => "email_address = ?1 and password = ?2",
                "bind"       => array(1 => $email, 2 => $password)
        ));

        if($user){
            $token = bin2hex(mcrypt_create_iv(35, MCRYPT_DEV_URANDOM));
            $user_token                     = new TblToken();
            $user_token->user_id            = $user->id;
            $user_token->token              = $token;
            $user_token->datetime_created   = $this->_getDateTime();
            $user_token->create();
            $this->session->set('auth-psslai', array(
                'user_type' =>  $user->user_type
            ));

            $user_roles = TblUserRoleDetails::find(array(
                "conditions"    => "id_user_role  = ?1",
                "bind"          => array(
                    1   => $user->id_user_role,
                )
            ));

            if($user_roles) {
                foreach($user_roles as $user_role) {
                    foreach($user_role->getModules() as $modules){}
                    $roles[] = array(
                        "head_module_id"    => $modules->head_module_id,
                        "module_id"         => $modules->id,
                        "module_code"       => $modules->module_code,
                        "role"      => array(
                            "role_id"   => (bool)$user_role->id,
                            "add"       => (bool)$user_role->add,
                            "edit"      => (bool)$user_role->edit,
                            "dlte"      => (bool)$user_role->dlte,
                            "view"      => (bool)$user_role->view,
                            "export"    => (bool)$user_role->export,
                            "import"    => (bool)$user_role->import,
                            "mask"      => (bool)$user_role->mask,
                        ),
                    );
                }
            }

            $this->_respond(array(
                'user_id'                   =>  $user->id,
                'token'                     =>  $token,
                'email_address'             =>  $user->email_address,
                'user_complete_name'        =>  $user->complete_name,
                'user_type'                 =>  $user->user_type,
                'user_role'                 =>  $roles
            ));
        }else{
            $this->_respondInvalid(3);
        }
    }

    public function outAction(){
        $this->view->disable();
        if ($this->request->isGet()){
            $this->session->remove("auth-psslai");
            $cache = $this->_cache();
            $keys = $cache->queryKeys();
            foreach ($keys as $key) {
                $cache->delete($key);
            }

            $this->respond(array(
                'statusCode'    =>  200,
                'devMessage'    =>  'ok'
            ));
        }
    }
}
