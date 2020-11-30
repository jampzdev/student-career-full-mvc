<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class SubjectController extends ControllerBase
{

  public function initialize(){
      if (!$this->_doesUserHaveToken('User')) {
          $this->_generateToken('User');
      }
  }
    public function subjectAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }
    public function listAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }


    public function insertFromCsvAction(){
      $this->view->disable();
      $request    =   $this->request;
      $path = 'uploads/' .$_FILES['file']['name'];
      $getFileUploaded = $_SERVER["DOCUMENT_ROOT"] ."/".$path;
        if(!empty($_FILES))  {
          if ( 0 < $_FILES['file']['error'] ) {
               echo 'Error: ' . $_FILES['file']['error'] . '<br>';
           }
           else {
               move_uploaded_file($_FILES['file']['tmp_name'], $path);
               $row = 1;
                if (($handle = fopen($getFileUploaded, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                      if($row >= 3){
                        $num = count($data);
                        echo "<p> $num fields in line $row: <br /></p>\n";
                        // for ($c=0; $c <= 13; $c++) {
                          $insQuery = new SubjectTbl();
                          $insQuery->subject_code = $data[0];
                          $insQuery->subject_name = $data[1];
                          $insQuery->description = $data[2];
                          // $insQuery->subject_type = $data[2];
                          $insQuery->subject_units = 3;
                          if(!$insQuery->create()){
                               foreach($insQuery->getMessages() as $err){
                                   $errors [] = $err->getMessage();
                               }
                               $this->respond(array(
                                   'statusCode'    => 500,
                                   'devMessage'    => $errors
                               ));
                           }
                      }
                      $row++;

                    }
                    fclose($handle);
                }
           }
        }
    }

    public function getSubjectListAction() {
      $this->view->disable();
      $post = $this->request->getJsonRawBody();
       if(!empty($post)){
           // For pagination
           $page       = $post->page;
           $row        = $post->count;
           $offset     = ($page - 1) * $row;
           // (click)="gotoTop()"
       }
      $getQryCnt = SubjectTbl::query()
        ->execute();
      $getQry = SubjectTbl::query()
          ->limit($row,$offset)
          ->execute();

      $this->respond(array(
           'statusCode'    => 200,
           'devMessage'    => $getQry,
           'totalItems'    => $getQryCnt->count()
       ));
       exit;
    }


}
