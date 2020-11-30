<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
class CareerController extends ControllerBase
{

    public function initialize(){
        if (!$this->_doesUserHaveToken('User')) {
            $this->_generateToken('User');
        }
    }

    public function careerAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function listAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }
    public function addupdateAction(){
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
    }

    public function getStudentListAction(){
        $this->view->disable();
        $info = [];

        $a = "SubjectStudentGradeMapping";
        $b = "StudentTbl";
        $c = "SubjectTbl";
        $d = "SemesterTbl";

        $sql = SubjectStudentGradeMapping::query()
        ->columns("$b.student_no,CONCAT($b.lname,', ',$b.fname,' ',$b.mname) AS fullname,$c.subject_code,$c.subject_name,$d.semester_name,$a.schoolyear,
                    $c.category_id,
                    $c.subject_type")
        ->join("StudentTbl","$b.id = $a.student_id","","")
        ->join("SubjectTbl","$c.id = $a.subject_id","","")
        ->join("SemesterTbl","$d.id = $a.semester_id","","")
        ->execute();


        if($sql){
            foreach($sql as $data){
                $info [] = array(
                    "student_no"        => $data->student_no,
                    "fullname"          => $data->fullname,
                    "subject_code"      => $data->subject_code,
                    "subject_name"      => $data->subject_name,
                    "semester_name"     => $data->semester_name,
                    "schoolyear"        => $data->schoolyear,
                    "category_id"       => $data->category_id
                );
            }
        }

        $this->respond(array(
            "devMessage"  => $info,
        ));
    }


    public function addUpdateListAction(){
      $this->view->disable();
      $post = $this->request->getJsonRawBody();
      if (empty($post)) {
         $this->respond(array(
             'statusCode'    =>  199,
             'devMessage'    =>  "No data received.",
         ));
         exit;
     }
     $insQuery = new SubjectCategoryTbl();
     $insQuery->category_name    = $post->category;
     $insQuery->tags             = $post->tags;
     if(!$insQuery->create()){
          foreach($insQuery->getMessages() as $err){
              $errors [] = $err->getMessage();
          }
          $this->respond(array(
              'statusCode'    => 500,
              'devMessage'    => $errors
          ));
      }
      foreach ($post->career as $info) {
        $insQuery2 = new CareerTbl();
        $insQuery2->career = $info->career_name;
        $insQuery2->position = $info->position;
        $insQuery2->skills = $info->skills;
        $insQuery2->category_id = $insQuery->id;
        if(!$insQuery2->create()){
             foreach($insQuery2->getMessages() as $err){
                 $errors [] = $err->getMessage();
             }
             $this->respond(array(
                 'statusCode'    => 500,
                 'devMessage'    => $errors
             ));
         }
      }

      $this->respond(array(
           'statusCode'    => 200,
           'devMessage'    => "Record Saved!"
       ));
       exit;

    }

    public function getCategoryListAction () {
        $this->view->disable();
        $post = $this->request->getJsonRawBody();
         if(!empty($post)){
             // For pagination
             $page       = $post->page;
             $row        = $post->count;
             $offset     = ($page - 1) * $row;
             // (click)="gotoTop()"
         }
        $getQryCnt = SubjectCategoryTbl::query()
          ->execute();
        $getQry = SubjectCategoryTbl::query()
            ->limit($row,$offset)
            ->execute();

        $this->respond(array(
             'statusCode'    => 200,
             'devMessage'    => $getQry,
             'totalItems'    => $getQryCnt->count()
         ));
         exit;
    }

  public function getSpecificDataAction () {
      $this->view->disable();
      $post = $this->request->getJsonRawBody();
      if (empty($post)) {
         $this->respond(array(
             'statusCode'    =>  199,
             'devMessage'    =>  "No data received.",
         ));
         exit;
     }
     $id = $post->id;
     if($id){
       $getQry = SubjectCategoryTbl::query()
           ->where("id = $id")
           ->execute();
       $getQry2 = SubjectCategoryTbl::query()
               ->where("id = $id")
               ->execute();

     }
  }
}
