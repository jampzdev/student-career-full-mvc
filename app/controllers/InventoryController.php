<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class InventoryController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function listAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->view->token = $this->_getToken('User');
    }

    public function getUnitListAction(){
        try{
            $this->view->disable();
            $info = [];
            // $post = $this->request->getJsonRawBody();

            $getQry = TblUnit::query()
                ->execute();

            if($getQry){
                foreach($getQry as $data){
                    $info [] = array(
                        "key"         => $data->id,
                        "unit_name"   => $data->unit_name
                    );
                }
            }

            $this->respond(array(
                "statusCode"        => 200,
                "devMessage"        => $info
            ));        
        }
        catch(Exception $e){

        }
    }

    public function getCategoryListAction(){
        try{
            $this->view->disable();
            $info = [];
            // $post = $this->request->getJsonRawBody();

            $getQry = TblCategory::query()
                ->execute();

            if($getQry){
                foreach($getQry as $data){
                    $info [] = array(
                        "key"         => $data->id,
                        "category_name"   => $data->category_name
                    );
                }
            }

            $this->respond(array(
                "statusCode"        => 200,
                "devMessage"        => $info
            ));        
        }
        catch(Exception $e){

        }
    }

    public function getBrandListAction(){
        try{
            $this->view->disable();
            $info = [];
            // $post = $this->request->getJsonRawBody();

            $getQry = TblBrand::query()
                ->execute();

            if($getQry){
                foreach($getQry as $data){
                    $info [] = array(
                        "key"         => $data->id,
                        "brand_name"   => $data->brand_name
                    );
                }
            }

            $this->respond(array(
                "statusCode"        => 200,
                "devMessage"        => $info
            ));        
        }
        catch(Exception $e){

        }
    }

    public function addNewItemAction(){
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();
            if(!empty($post)){
                $ins                = new TblInventory();
                $ins->item_desc     = $post->item_desc;
                $ins->qty           = $post->qty;
                $ins->id_brand      = $post->id_brand;
                $ins->id_unit       = $post->id_unit;
                $ins->id_category   = $post->id_category;
                $ins->barcode       = $post->barcode;
                $ins->item_name     = $post->item_name;
                $ins->created_by    = $post->created_by;
                $ins->price         = $post->price;
                $ins->created_at    = date("Y-m-d H:i:s");

                if($ins->create()){

                    $updQry = TblInventory::findFirst("id=$ins->id");
                    $updQry->item_code     = str_pad($post->id_category,5,"0",STR_PAD_LEFT) . str_pad($post->id_brand,5,"0",STR_PAD_LEFT) . str_pad($ins->id,10,"0",STR_PAD_LEFT);
                    if($updQry->update()){
                        $this->respond(array(
                            "statusCode"        => 200,
                            "devMessage"        => "Record Saved"
                        ));     
                    }
                }
            }

        }
        catch(Exception $e){

        }
    }

    public function getInventoryAction(){
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();

            $info           = [];
            $page           = $post->page;
            $row            = $post->count;
            $search         = $post->search;

            $offset     = ($page - 1) * $row;

            if($search==""){
                $getQryTotal = TblInventory::query()
                    ->execute();

                $getQry = TblInventory::query()
                    ->limit($row,$offset)
                    ->orderBy("id DESC")
                    ->execute();
            }
            else{
                $getQryTotal = TblInventory::query()
                    ->where("item_name LIKE '%".$search."%'")
                    ->execute();

                $getQry = TblInventory::query()
                    ->where("item_name LIKE '%".$search."%'")
                    ->limit($row,$offset)
                    ->orderBy("id DESC")
                    ->execute();
            }


            if($getQry){
                foreach($getQry as $data){
                    $info []  = array(
                        "key"               => $data->id,
                        "item_code"         => $data->item_code,
                        "item_name"         => $data->item_name,
                        "item_desc"         => $data->item_desc,
                        "barcode"           => $data->barcode,

                        "id_category"       => $data->id_category,
                        "id_brand"          => $data->id_brand,
                        "id_unit"           => $data->id_unit,

                        "qty"               => $data->qty,
                        "price"             => $data->price,

                        "category_name"     => $this->_getCategoryById($data->id_category),
                        "brand_name"        => $this->_getBrandById($data->id_brand),
                        "unit_name"         => $this->_getUnitById($data->id_unit),

                        "created_at"        => date("M d, Y",strtotime($data->created_at)),
                        "created_by"        => $this->_getUserInfo($data->created_by)
                    );
                }
            }

            $this->_respond(array(
                'statusCode'        => 200,
                'inventoryList'     => $info,
                'total'             => $getQryTotal->count()
            ));
        }
        catch(Exception $e){
            $this->_respond(array(
                'statusCode'                =>  500,
                'devMessage'                =>  $e->getMessages()
            ));        
        }
    }

    public function deleteSpecificItemAction(){
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();

            $del = TblInventory::findFirst("id=$post->key");

            if($del->delete()){
                $this->respond(array(
                    "statusCode"        => 200,
                    "devMessage"        => "Record Deleted"
                ));                
            }
        }
        catch(Exception $e){

        }
    }

    public function updateItemAction(){
        try{
            $this->view->disable();
            $post = $this->request->getJsonRawBody();

            $upd = TblInventory::findFirst("id=$post->key");
            $upd->item_desc     = $post->item_desc;
            $upd->qty           = $post->qty;
            $upd->id_brand      = $post->id_brand;
            $upd->id_unit       = $post->id_unit;
            $upd->id_category   = $post->id_category;
            $upd->barcode       = $post->barcode;
            $upd->item_name     = $post->item_name;
            $upd->price         = $post->price;

            if($upd->update()){
                $this->respond(array(
                    "statusCode"        => 200,
                    "devMessage"        => "Record Updated"
                ));                
            }
        }
        catch(Exception $e){

        }
    }
}
