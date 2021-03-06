<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class StudentController extends ControllerBase
{

  public function initialize(){
      if (!$this->_doesUserHaveToken('User')) {
          $this->_generateToken('User');
      }
  }
    public function studentAction(){
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
                          $insQuery = new StudentTbl();
                          $insQuery->student_no = $data[0];
                          $insQuery->lname = $data[1];
                          $insQuery->fname = $data[2];
                          $insQuery->mname = $data[3];
                          $insQuery->course = $data[4];
                          $insQuery->yearlvl = $data[5];
                          $insQuery->section = $data[6];
                          $insQuery->student_status = $data[7];
                          $insQuery->address = $data[8];
                          $insQuery->contactno = $data[9];
                          $insQuery->birthplace = $data[10];
                          if(!$insQuery->create()){
                               foreach($insQuery->getMessages() as $err){
                                   $errors [] = $err->getMessage();
                               }
                               $this->respond(array(
                                   'statusCode'    => 500,
                                   'devMessage'    => $errors
                               ));
                           }
                        // }
                      }
                      $row++;

                    }
                    fclose($handle);
                }
           }
            // printf($_FILES['file']['tmp_name']);
            // if(move_uploaded_file($_FILES['file']['tmp_name'], $path)){
            //   var_dump($getFileUploaded);
            // }
        }

      // $getFileUploaded = $_SERVER["DOCUMENT_ROOT"] ."/".$path;
      // move_uploaded_file ( $_FILES['file']['name'] , $path )
      // var_dump($path);

      // console.log($path);

      // if(!empty($_FILES))  {
      //         if(move_uploaded_file($_FILES['file']['tmp_name'], $path)){
      //
      //         }
      // }

    }

    public function getStudentListAction() {
      $this->view->disable();
      $post = $this->request->getJsonRawBody();
       if(!empty($post)){
           // For pagination
           $page       = $post->page;
           $row        = $post->count;
           $offset     = ($page - 1) * $row;
           // (click)="gotoTop()"
       }
       $a = "StudentTbl";
       $b = "SubjectTbl";
       $c = "SemesterTbl";
      $getQryCnt = StudentTbl::query()
        ->execute();
      $getQry = StudentTbl::query()
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
