<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query\Builder as Builder;
class UsersController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function usersAction() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->token = $this->_getToken('User');
    }

    public function rolesAction() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->token = $this->_getToken('User');
    }

    public function getUsersAction() {
        try {
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            if(!empty($post)){
                // For pagination
                $page       = $post->page;
                $row        = $post->count;
                $offset     = ($page - 1) * $row;
                $orderByRaw = $post->orderBy;
                $sorting    = $orderByRaw[0] == "-" ? "DESC" : "ASC";
                $orderBy    = substr($orderByRaw, 1);

                // For Search bar
                $a = "TblUser";
                $b = "TblUserRole";

                if (empty($post->search)) {
                    // print_r("test query");
                    $user = TblUser::query()
                            ->columns("$a.id, $a.email_address, $a.complete_name, $b.user_role")
                            ->join($b, "$a.id_user_role = $b.id")
                            ->limit($row,$offset)
                            // ->orderBy($orderBy." ".$sorting)
                            ->orderBy("$a.id DESC")
                            ->execute();

                    $count = TblUser::count();
                } else {
                    $user = TblUser::query()
                            ->columns("$a.id, $a.email_address, $a.complete_name, $b.user_role")
                            ->join($b, "$a.id_user_role = $b.id")
                            ->where("$a.complete_name LIKE ?1 OR $a.email_address LIKE ?1 OR $b.user_role LIKE ?1")
                            ->bind(array(1=>"%".$post->search."%"))
                            ->limit($row,$offset)
                            // ->orderBy($orderBy." ".$sorting)
                            ->orderBy("$a.id DESC")
                            ->execute();

                    // Count for pagination
                    // $count = TblUser::count(array(
                    //         "conditions" => "complete_name LIKE ?1",
                    //         "bind"      => array(
                    //             1       => "%".$post->search."%",
                    //         ),
                    // ));

                }


                if($user){
                    foreach ($user as $value) {
                        // echo $value->first_name." ,".$value->last_name." ,".$value->email."<br>";
                        $user_data[] = array(
                                'id'                => $value->id,
                                'complete_name'     => $value->complete_name,
                                'email_address'     => $value->email_address,
                                // 'user_type'         => $value->user_type,
                                // 'user_role'         => $value->id_user_role,
                                // 'user_role'         => $value->getUserRole()->user_role,
                                'user_role'         => $value->user_role,
                        );
                    }
                    $this->respond(array(
                            "userList"  => $user_data,
                            "total"     => count($user)
                    ));
                }
                else {
                    // echo "Getting Data Failed.";
                }
            }
        } catch (Exception $e) {
            // $this->_respondError($e);
        }
    }

    public function updateUserDataAction() {
        $this->view->disable();
        $post = $this->request->getJsonRawBody();
        if(!$post){
          $this->_respondInvalid(2);
        }
        $updateUser = TblUser::findFirst("id = $post->id");
        $updateUser->complete_name  = $post->user_name;
        $updateUser->email_address  = $post->user_email;
        $updateUser->password       = hash('sha256',$post->user_password);
        $updateUser->id_user_role   = $post->user_role;
        if (!$updateUser->update()) {
            $errorMessage 		= array();
            foreach ($updateUser->getMessages($errorMessage) as $msg) {
                $errorMessage[] = $msg->getMessage();
            }
            $this->respond(array(
                'statusCode'    =>  500,
                'devMessage'    =>  "Failed to update user record.",
                'message'       =>  $errorMessage,
            ));
        }
        $this->respond(array(
            'statusCode'    =>  200,
            'devMessage'    =>  "User Added.",
        ));
    }

    public function deleteScpecificUserAction() {
        try {
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            if(!$post){
            $this->_respondInvalid(2);
            }
            $deleteUser = TblUser::findFirst("id = $post->id");
            if ($deleteUser->delete()) {
                $this->respond(array(
                    'statusCode'    =>  200,
                    'devMessage'    =>  "User Deleted.",
                ));
            }
        }
        catch(Exception $e){
            $this->_respondError(500,array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }

    public function addNewUserAction() {
        $this->view->disable();
        $post = $this->request->getJsonRawBody();
        // var_dump($post);

        $checkDuplicate = TblUser::findFirst(array(
            "conditions"    => "email_address = ?1",
            "bind"          => array(
                1   => $post->user_email,
            )
        ));

        if($checkDuplicate) {
            $this->respond(array(
                'statusCode'    =>  204,
                'devMessage'    =>  "Username already existed.",
            ));
        }
        else {
            $saveDetails = new TblUser();
            $saveDetails->complete_name     = $post->user_name;
            $saveDetails->email_address     = $post->user_email;
            $saveDetails->password          = hash('sha256',$post->user_password);
            // $saveDetails->password          = $post->user_password;
            $saveDetails->user_type         = "a";
            $saveDetails->id_user_role      = $post->user_role;
            if(!$saveDetails->create()){
                $errorMessage	=	array();
                foreach ($saveDetails->getMessages($errorMessage) as $msg) {
                    $errorMessage[]	=	$msg->getMessage();
                }
                $this->_respondError(array(
                    'statusCode'    =>  400,
                    'devMessage'    =>  "User creation failed.",
                    'message'       =>  $errorMessage,
                ));
            }
            $this->respond(array(
                'statusCode'    =>  200,
                'devMessage'    =>  "User Added.",
            ));
        }
    }

    public function getSystemModulesAction(){
        try{
            $this->view->disable();
            $getQry = TblHeadModules::query()->execute();
            foreach($getQry as $qry) {
                $module[] = array(
                    "id"    => $qry->module_no,
                    "data"  => $qry->getModules(),
                );
            }

            $this->respond(array(
                'statusCode'    => 200,
                'devMessage'    => $module
            ));

        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }


    }

    public function getUsersListAction(){
        try{
            $this->view->disable();

            $a = "TblUser";

            $getQry = TblUser::query()
                ->columns("$a.id,$a.email_address,$a.complete_name")
                ->execute();
            if($getQry){
                foreach($getQry as $data){
                    $info [] = array(
                        'key'           => $data->id,
                        'module_code'   => $data->module_code,
                        'module_name'   => $data->module_name,
                        'status'        => $data->status
                    );
                }

                $this->respond(array(
                    'statusCode'    => 200,
                    'devMessage'    => $info
                ));
            }
        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }

    public function setNewRoleAction(){

        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            $this->db->begin();

            if($post->id_key == null || $post->id_key == "") {

                    $role_name              = $post->role_name;
                    $current_user_id        = $post->current_user_id;
                    $filings                = $post->role_access_filing;
                    $bilings                = $post->role_access_biling;
                    $atms                   = $post->role_access_atm;
                    $settings               = $post->role_access_settings;
                    $memberships            = $post->role_access_memberships;
                    $user_managements       = $post->role_access_user_managements;
                    $roles                  = array();


                    $isExist = TblUserRole::findFirst("user_role = '$role_name'");

                    if($isExist) {
                        $this->respond(array(
                            'statusCode'    => 400,
                            'devMessage'    => "Role name already exists."
                        ));
                        return;
                    }

                    $newUserRole                = new TblUserRole();
                    $newUserRole->user_role     = $role_name;
                    $newUserRole->created_by    = $current_user_id;
                    $newUserRole->created_at    = date("Y-m-d H:i:s");

                    if(!$newUserRole->create()) {
                        $this->db->rollback();
                        $error = [];
                        $errorMessage 		= array();
                        foreach ($newClientLoan->getMessages($errorMessage) as $msg) {
                            $errorMessage[] = $msg->getMessage();
                        }
                    }

                    //Filings User Role
                    if($filings != null) {
                        $filings_info = array();
                        foreach($filings as $filing) {
                            $info = array(
                                "module_id"         => $filing->module_id,
                                "delete"            => $filing->delete,
                                "view"              => $filing->view,
                                "import"            => $filing->import,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    //Billings User Role
                    if($bilings != null) {
                        $bilings_info = array();
                        foreach($bilings as $biling) {
                            $info = array(
                                "module_id"         => $biling->module_id,
                                "view"              => $biling->view,
                                "export"            => $biling->export,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    // var_dump($roles);
                    // exit();


                    //ATMs User Role
                    if($atms != null) {
                        $atms_info = array();
                        foreach($atms as $atm) {
                            $info = array(
                                "module_id"         => $atm->module_id,
                                "view"              => $atm->view,
                                "pin"               => $atm->pin,
                                "export"            => $atm->export,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    //Settings User Role
                    if($settings != null) {
                        $settings_info = array();
                        foreach($settings as $setting) {
                            $info = array(
                                "module_id"         => $setting->module_id,
                                "add"               => $setting->add,
                                "edit"              => $setting->edit,
                                "delete"            => $setting->delete,
                                "view"              => $setting->view,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    //Membership User Role
                    if($memberships != null) {
                        $memberships_info = array();
                        foreach($memberships as $membership) {
                            $info = array(
                                "module_id"         => $membership->module_id,
                                "view"              => $membership->view,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    //User Management Role
                    if($user_managements != null) {
                        $user_managements_info = array();
                        foreach($user_managements as $user_management) {
                            $info = array(
                                "module_id"         => $user_management->module_id,
                                "add"               => $user_management->add,
                                "edit"              => $user_management->edit,
                                "delete"            => $user_management->delete,
                                "view"              => $user_management->view,
                                "head_module_id"    => (int)$filings->head_module_id,
                            );
                            array_push($roles, $info);
                        }
                    }

                    foreach($roles as $role) {

                        if((int)$role["module_id"] != 0) {
                            $newUserRoleDetails                     = new TblUserRoleDetails();
                            $newUserRoleDetails->id_module          = (int)$role["module_id"];
                            $newUserRoleDetails->id_user_role       = $newUserRole->id;
                            $newUserRoleDetails->add                = $role["add"] ? 1 : 0;
                            $newUserRoleDetails->edit               = $role["edit"] ? 1 : 0;
                            $newUserRoleDetails->dlte               = $role["delete"] ? 1 : 0;
                            $newUserRoleDetails->view               = $role["view"] ? 1 : 0;
                            $newUserRoleDetails->mask               = $role["pin"] ? 1 : 0;
                            $newUserRoleDetails->import             = $role["import"] ? 1 : 0;
                            $newUserRoleDetails->export             = $role["export"] ? 1 : 0;

                            if(!$newUserRoleDetails->create()) {
                                $this->db->rollback();
                                $error = [];
                                $errorMessage 		= array();
                                foreach ($newClientLoan->getMessages($errorMessage) as $msg) {
                                    $errorMessage[] = $msg->getMessage();
                                }
                            }
                        }
                    }

                    $message = "Successfully added user role.";
                }
                else {

                    $id_key                 = $post->id_key;
                    $role_name              = $post->role_name;
                    $filings                = $post->role_access_filing;
                    $bilings                = $post->role_access_biling;
                    $atms                   = $post->role_access_atm;
                    $settings               = $post->role_access_settings;
                    $memberships            = $post->role_access_memberships;
                    $user_managements       = $post->role_access_user_managements;
                    $roles                  = array();

                    //Filing
                    foreach($filings as $filing) {
                        $info = array(
                            "module_id"     => $filing->module_id,
                            "delete"        => $filing->delete,
                            "view"          => $filing->view,
                            "import"        => $filing->import,
                        );
                        array_push($roles, $info);
                    }

                    //Billing
                    foreach($bilings as $biling) {
                        $info = array(
                            "module_id"     => $biling->module_id,
                            "view"          => $biling->view,
                            "export"        => $biling->export,
                        );
                        array_push($roles, $info);
                    }

                    //ATMS
                    foreach($atms as $atm) {
                        $info = array(
                            "module_id"     => $atm->module_id,
                            "view"          => $atm->view,
                            "pin"           => $atm->pin,
                            "export"        => $atm->export,
                        );
                        array_push($roles, $info);
                    }

                    //Settings
                    foreach($settings as $setting) {
                        $info = array(
                            "module_id"    => $setting->module_id,
                            "add"          => $setting->add,
                            "edit"         => $setting->edit,
                            "delete"       => $setting->delete,
                            "view"         => $setting->view,
                        );
                        array_push($roles, $info);
                    }

                    //Membership
                    foreach($memberships as $membership) {
                        $info = array(
                            "module_id"    => $membership->module_id,
                            "view"         => $membership->view,
                        );
                        array_push($roles, $info);
                    }

                    //User Management
                    foreach($user_managements as $user_management) {
                        $info = array(
                            "module_id"    => $user_management->module_id,
                            "add"          => $user_management->add,
                            "edit"         => $user_management->edit,
                            "delete"       => $user_management->delete,
                            "view"         => $user_management->view,
                        );
                        array_push($roles, $info);
                    }

                    $updateUserRole             = TblUserRole::findFirst(array(
                        "conditions"    => "id  = ?1",
                        "bind"          => array(
                            1   => $id_key,
                        )
                    ));

                    if($updateUserRole) {
                        $updateUserRole->user_role     = $role_name;
                        if(!$updateUserRole->update()) {
                            $this->db->rollback();
                        $error = [];
                        $errorMessage 		= array();
                        foreach ($updateUserRole->getMessages($errorMessage) as $msg) {
                            $errorMessage[] = $msg->getMessage();
                        }
                    }
                    }

                    $deleteUserRoleDatetails = TblUserRoleDetails::find(array(
                        "conditions"    => "id_user_role  = ?1",
                        "bind"          => array(
                            1   => $id_key,
                        )
                    ));

                    if ($deleteUserRoleDatetails->delete()) {

                        foreach($roles as $role) {
                            if((int)$role["module_id"] != 0) {
                                $newUserRoleDetails                     = new TblUserRoleDetails();
                                $newUserRoleDetails->id_module          = (int)$role["module_id"];
                                $newUserRoleDetails->id_user_role       = $id_key;
                                $newUserRoleDetails->add                = $role["add"] ? 1 : 0;
                                $newUserRoleDetails->edit               = $role["edit"] ? 1 : 0;
                                $newUserRoleDetails->dlte               = $role["delete"] ? 1 : 0;
                                $newUserRoleDetails->view               = $role["view"] ? 1 : 0;
                                $newUserRoleDetails->mask               = $role["pin"] ? 1 : 0;
                                $newUserRoleDetails->import             = $role["import"] ? 1 : 0;
                                $newUserRoleDetails->export             = $role["export"] ? 1 : 0;

                                if(!$newUserRoleDetails->create()) {
                                    $this->db->rollback();
                                    $error = [];
                                    $errorMessage 		= array();
                                    foreach ($newClientLoan->getMessages($errorMessage) as $msg) {
                                        $errorMessage[] = $msg->getMessage();
                                    }
                                }
                            }
                        }
                    }
                    $message = "Record is successfully updated.";
                }


                $this->db->commit();
                $respond_message = array(
                    "statusCode"        => 200,
                    "devMessage"        => $message
                );
                $this->respond($respond_message);

        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }

    }

    public function getUserRolesAction(){
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            if(!$post){
                $this->_respondInvalid(2);
            }

            $page                   = $post->page;
            $count                  = $post->count;
            $offset 	            = ($page-1) * $count;
            $search                 = $post->search;

            if($search==""){
                $getQryCnt = TblUserRole::query()
                    ->columns("COUNT(*) as cnt")
                    ->execute();

                $getQry = TblUserRole::query()
                    ->limit($count,$offset)
                    ->execute();
            }
            else{
                $getQryCnt = TblUserRole::query()
                    ->columns("COUNT(*) as cnt")
                    ->where("user_role LIKE '%".$search."%'")
                    ->execute();

                $getQry = TblUserRole::query()
                    ->where("user_role LIKE '%".$search."%'")
                    ->limit($count,$offset)
                    ->execute();
            }

            foreach($getQryCnt as $cnt){}


            if($getQry){
                $info = [];
                foreach($getQry as $data){
                    $sub = [];
                    $a = "TblUserRoleDetails";
                    $b = "TblModules";

                    $getQry2 = TblUserRoleDetails::query()
                        ->columns(" $a.id,
                                    $a.add,
                                    $a.edit,
                                    $a.dlte,
                                    $a.view,
                                    $a.mask,
                                    $a.export,
                                    $a.import,
                                    $b.module_name")
                        ->join("TblModules","$a.id_module = $b.id","")
                        ->where("$a.id_user_role = ?1")
                        ->bind(array(1=>$data->id))
                        ->execute();

                    foreach($getQry2 as $data2){
                        $sub [] = array(
                            'key'           => $data2->id,
                            'module_name'   => $data2->module_name,
                            'add'           => $data2->add,
                            'edit'          => $data2->edit,
                            'delete'        => $data2->dlte,
                            'view'          => $data2->view,
                            'mask'          => $data2->mask,
                            'import'        => $data2->import,
                            'export'        => $data2->export,
                        );
                    }

                    $info [] = array(
                        'key'           => $data->id,
                        'user_role'     => $data->user_role,
                        'created_at'    => $data->created_at,
                        'created_by'    => $data->getUser()->complete_name,
                        'sub'           => $sub
                    );
                }

                $this->respond(array(
                    'statusCode'    => 200,
                    'devMessage'    => $info,
                    'totalItems'    => $cnt->cnt
                ));
            }
            else{
                foreach($getQry->getMessages() as $err){
                    $this->respond(array(
                        'statusCode'    => 500,
                        'devMessage'    => $err->getMessage()
                    ));
                }
            }
        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }

    public function getUserRolesDropdownAction() {
        try {
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            if(!empty($post)){

                $userRole = TblUserRole::query()
                        ->execute();

                if($userRole){
                    foreach ($userRole as $value) {
                        $user_data[] = array(
                                'user_role_id'      => $value->id,
                                'user_role'         => $value->user_role,
                        );
                    }
                    $this->respond(array(
                        "userRoleList"  => $user_data,
                    ));
                }
                else {
                    // echo "Getting Data Failed.";
                }
            }
        } catch (Exception $e) {
            // $this->_respondError($e);
        }
    }

    public function deleteUserRoleAction() {
        $this->view->disable();
        $post = $this->request->getJsonRawBody();
        if(!$post){
          $this->_respondInvalid(2);
        }

        $checking = TblUser::findFirst(array(
            "conditions"    => "id_user_role = ?1",
            "bind"          => array(
                1   => $post->id,
            )
        ));
        if($checking){
            $this->respond(array(
                'statusCode' => 500,
                'devMessage' => "This roles is invalid to delete",
            ));
        } else {
            $deleteUserRole = TblUserRole::findFirst("id = $post->id");
            if ($deleteUserRole->delete()) {
                $this->respond(array(
                    'statusCode'    =>  200,
                    'devMessage'    =>  "User role Deleted.",
                ));
            }
        }

    }

    public function getSpecificUserRoleAction() {
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();

            $getQry     = TblUserRoleDetails::query()
                            ->where("id_user_role = ?1")
                            ->bind(array(
                                1   =>  $post->id
                            ))
                            ->execute();
            foreach($getQry as $qry) {
                $info[] = array(
                    "id_module"            => $qry->id_module,
                    "add"                  => $qry->add,
                    "edit"                 => $qry->edit,
                    "delete"               => $qry->dlte,
                    "view"                 => $qry->view,
                    "export"               => $qry->export,
                    "import"               => $qry->import,
                    "mask"                 => $qry->mask,
                );
            }

            $respond_message = array(
                "statusCode"    => 200,
                "devMessage"    => $info,
            );
            $this->respond($respond_message);
        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }

    //New Action for getting the user role
    public function getAction($type) {
        $post = $this->request->getJsonRawBody();

        if(!empty($post)) {
            if($this->request->isPost()) {
                if($type === "roles") {

                    $role_id = $post->id;
                    $user_roles = TblUserRoleDetails::find(array(
                        "conditions"    => "id_user_role  = ?1",
                        "bind"          => array(
                            1   => $role_id,
                        )
                    ));

                    
                    foreach($user_roles as $user_role) {
                        foreach($user_role->getModules() as $modules){}
                        $roles[] = array(
                            "head_module_id"    => (int)$modules->head_module_id,
                            "module_id"         => (int)$modules->id,
                            "module_code"       => $modules->module_code,
                            "role"      => array(
                                "role_id"   => (int)$user_role->id,
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

                    $respond_message = array(
                        "statusCode"    => 200,
                        "devMessage"    => array(
                            "message"   => "Fetching is successfull.",
                            "roles"      => $roles,
                        ),
                    );
                    $this->respond($respond_message);
                    exit;
                }
            }
        }
    }
}
