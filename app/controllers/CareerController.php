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
}