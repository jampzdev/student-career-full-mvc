<?php
use Phalcon\Mvc\View;
use Phalcon\Mvc\Controller;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Mvc\Model\Query\Builder as Builder;

$GLOBALS['GLOBAL_ISADDNEWLINE'] = false;
$GLOBALS['tempPayPeriod'] = null;
class MainTask extends \Phalcon\Cli\Task
{

    protected function returnEmpty($data){
        if($data == null){
            return "";
        } else {
            $trimData = str_replace('  ', '',($data == '0' ? "" : $data));
            return $trimData;
        }
    }

    public function mainAction()
    {
        echo "\nThis is the default task and the default action \n";
    }

    /**
     * @param array $params
     */

    public function syncAction() {
        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $c = "TblAtmListFile";
        $d = "TblLoanCsvFile"; //Loan Billing
        $e = "TblLoanAtmFile"; //Loan Atm
        $f = "TblDeductionCode";
        $ss = "TblServiceStatus";
        $tbl_branchSvc = "TblBos";
        $tbl_loanType = "TblLoanType";
        $tbl_billMode = "TblBillMode";
        $getQry = TblMemberInfoFile::query()
        // ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
        // $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.T24MemberNo,$a.PINAcctNo,$d.MOA1,$d.MOA2,$d.PnpBillMod,$d.id as TblLoanBillingId,$e.memberNo, $tbl_loanType.product,$d.id_loan_csv_name,
        // $e.lproNo,$e.loanAmt,$e.dateGrant,$e.maturity,$e.startDate1,$e.loanAppl")
        // ->join($d,"$e.memberNo = $d.memberNo","","left")
        // ->join($a,"$e.memberNo = $a.SapMemberNo","","left")
        // // ->join($b,"$e.memberNo = $b.MemberNo","","left")
        // // ->join($c,"$e.memberNo = $c.CLIENT","","left")
        // ->join($tbl_loanType,"$d.lnType = $tbl_loanType.loan_type","","left")
        // ->groupBy("$a.SapMemberNo")
        // ->where("$a.BranchSvc = 'ATM'")
        // ->limit(100)
        ->execute();
        $ctr =0;
        foreach($getQry as $data){
            echo $getQry->count()."\r\n";
            echo $data->id;
            $ctr++;

        }
        // $x = $this->generateAtmBilling();
        // print_r($x->generateAtmBilling());
        // echo "\nThis will migrate users \n";
    }

/* GLOBAL FUNCTIONS - DO NOT TOUCH ================================================================== */
    private function getGroupServiceStatus($serviceStatus) {
         $getSSGroup = TblServiceStatus::findFirst("service_status = '$serviceStatus'");
         return $getSSGroup->group_service_status;
    }

    private function getServiceStatusFromLnType($lnType) {
        if ($lnType == "PL" || $lnType == "AP" || $lnType == "OP") {
            return "PENSION";
        } else {
            return "ACTIVE";
        }
    }

    private function getStatusFromServiceStatus($SvcStat) {
        if ($SvcStat == "CO" || $SvcStat == "OP" || $SvcStat == "RD" || $SvcStat == "RE") {
            return "PENSION";
        } else {
            return "ACTIVE";
        }
    }


    private function getPayPeriod($idLoanCSVName, $branchSvc) {
        $getInitDate = TblLoanCsvFile::findFirst("id_loan_csv_name = '$idLoanCSVName' AND initDate IS NOT NULL");
        $formattedInitDate = date("m-d-Y", strtotime(substr($getInitDate->initDate, 0,10)));

        $checkerMonth = date("m", strtotime(substr($getInitDate->initDate, 0,10)));
        $checkerYear = date("Y", strtotime(substr($getInitDate->initDate, 0,10)));
        $tempStartCutOff = date("m-d-Y", strtotime("-1 month", strtotime($checkerYear."-".$checkerMonth."-27")));
        $tempEndCutOff = date("m-d-Y", strtotime($checkerYear."-".$checkerMonth."-26"));

        if (($formattedInitDate <= $tempEndCutOff) && ($formattedInitDate >= $tempStartCutOff)) {
            $payPayPeriod = date('Ym', strtotime("+1 month", strtotime($checkerYear."-".$checkerMonth)));
        } else if ($formattedInitDate >= $tempEndCutOff) {
            if ($branchSvc == "BFP" || $branchSvc == "BJMP" || $branchSvc == "NAPOLCOM" || $branchSvc == "PPSC") {
                $payPayPeriod = date('Ym', strtotime("+1 month", strtotime($checkerYear."-".$checkerMonth)));
            } else {
                $payPayPeriod = date('Ym', strtotime("+2 month", strtotime($checkerYear."-".$checkerMonth)));
            }
        }


        return (int)$payPayPeriod <= 200000 ? "" : $payPayPeriod;
    }

    private function getDeductionCode($groupServiceStatus,$pnpBillMode,$loanType,$branchSvc) {
        $tblDc = "TblDeductionCode";
        $tbl_branchSvc = "TblBos";
        $tbl_loanType = "TblLoanType";
        $tbl_billMode = "TblBillMode";
        $deduction_code = "";

        if ($pnpBillMode == 'PBM00') {
            $tempPnpBillMode = "PBM01";
        } else {
            $tempPnpBillMode = $pnpBillMode;
        }

        // print_r("$groupServiceStatus,$pnpBillMode,$loanType,$branchSvc");
        $getDeductionCode = TblDeductionCode::query()
        ->columns("$tblDc.group_service_status, $tblDc.bill_mode_code_id,$tblDc.bill_mode,$tblDc.product_type,$tblDc.deduction_code, $tblDc.branch_id, $tbl_branchSvc.branch_of_service,$tbl_billMode.bill_mode_t24,$tbl_loanType.loan_type,$tbl_billMode.bill_mode_sap")
        ->join($tbl_branchSvc,"$tblDc.branch_id = $tbl_branchSvc.id","","left")
        ->join($tbl_billMode, "$tblDc.bill_mode = $tbl_billMode.bill_mode_t24","","left")
        ->join($tbl_loanType, "$tblDc.product_type = $tbl_loanType.id","","left")
        // ->where("$tblDc.group_service_status = '$groupServiceStatus' AND $tblDc.bill_mode = '$pnpBillMode' AND $tbl_loanType.loan_type = '$loanType' AND $tbl_branchSvc.branch_of_service = '$branchSvc'")
        ->where("$tblDc.group_service_status = '$groupServiceStatus' AND $tbl_billMode.bill_mode_sap = '$tempPnpBillMode'AND $tbl_loanType.loan_type = '$loanType' AND $tbl_branchSvc.branch_of_service = '$branchSvc'")
        ->execute();

       if ($getDeductionCode) {
         foreach ($getDeductionCode as $deductionCodes) {
            $deduction_code = $deductionCodes->deduction_code;
        }
       } else {
           $deduction_code = "";
       }


        return $deduction_code;
    }

    private function getBillMode($groupServiceStatus,$deduction_code,$branchSvc) {
        $tblDc = "TblDeductionCode";
        $tbl_branchSvc = "TblBos";
        $tbl_billMode = "TblBillMode";

        $l_groupServiceStatus = trim($groupServiceStatus);
        $l_deduction_code = trim($deduction_code);
        $l_branchSvc = trim($branchSvc);
        $bill_mode = null;
        $bill_mode_sap = null;

        $getBillMode = TblDeductionCode::query()
        ->columns("$tblDc.group_service_status, $tblDc.bill_mode_code_id,$tblDc.bill_mode,$tblDc.product_type,$tblDc.deduction_code, $tblDc.branch_id, $tbl_branchSvc.branch_of_service")
        ->join($tbl_branchSvc,"$tblDc.branch_id = $tbl_branchSvc.id","","inner")
        ->where("$tblDc.deduction_code = '$l_deduction_code' AND $tblDc.group_service_status = '$l_groupServiceStatus' AND $tbl_branchSvc.branch_of_service = '$l_branchSvc'")
        ->limit(1)
        ->execute();

        foreach ($getBillMode as $billModes) {
            $bill_mode = $billModes->bill_mode;
        }

          $getBillModeSap = TblBillMode::query()
          ->columns("id,bill_mode_sap,bill_mode_t24")
          ->where("bill_mode_t24='$bill_mode'")
          ->execute();

        foreach ($getBillModeSap as $billModesSap) {
            $bill_mode_sap = $billModesSap->bill_mode_sap;
        }

        return array (
            'bill_mode_sap' => $bill_mode_sap,
            'bill_mode' => $bill_mode,
        );
    }

    private function getReferenceSdlis($billingType,$groupServiceStatus,$pinAcctNo) {
        $tbl_active = "Sdlis";
        $tbl_pension = "Pdlis";
        $tbl_reference_pnp_active = "TblPnpSdlisFile";
        $tbl_reference_pnp_pension = "TblPnpPdlisFile";



        if($billingType == "PnpAc" || $billingType == "BfpAc"){
                $getTblReference = $tbl_reference_pnp_active::findFirst("account_number = '$pinAcctNo'");
                if ($getTblReference) {
                    $dateGranted = $getTblReference->encoded_date_time;
                    $amortization = $getTblReference->deduction_amount;
                    $deductionCode = $getTblReference->deduction_code_name;
                } else {
                    $dateGranted = null;
                    $amortization = 0;
                    $deductionCode = "";
                }

        } else if ($billingType == "PnpRe") {
            $getTblReference = $tbl_reference_pnp_pension::findFirst("pan = '$pinAcctNo'");
                $dateGranted = $getTblReference == null ? "" : $getTblReference->date_granted;
                $amortization = $getTblReference == null ? "" : $getTblReference->monthly_amort;
                $deductionCode = $getTblReference == null ? "" : $getTblReference->deduction;

        } else if ($billingType == "BfpRe") {
            $getTblReference = $tbl_reference_pnp_pension::findFirst("pan = '$pinAcctNo'");
                $dateGranted = $getTblReference == null ? "" : $getTblReference->date_granted;
                $amortization = $getTblReference == null ? "" : $getTblReference->monthly_amort;
                $deductionCode = $getTblReference == null ? "" : $getTblReference->deduction;
        }


        $deductionCode = explode(' ',trim($deductionCode));
        $amortStr = str_replace(",","",$amortization);
        $amortDouble = (double)$amortStr;

        return array (
            'dateGranted' => $dateGranted,
            'amortization' => $amortDouble,
            'deductionCode' => $deductionCode[0]
        );
   }

   private function getBillParams($PnPBillMod,$billAmnt,$nriVal){
    $billMode = "";
    $billRemarks = "";
    $nri = $nriVal;

            if($PnPBillMod=="PBM00" || $PnPBillMod=="PBM01" || $PnPBillMod=="PBM02"){
                $billMode = "Bill to Loan";
                $billRemarks = "NEW LOAN";
            }
            if($PnPBillMod=="PBM03"){
                $billMode = "Bill to CAPCON";
                $billRemarks = "CAPITAL.CONTRIBUTION";
                $nri = 0;
            }
            if($PnPBillMod=="PBM04"){
                $billMode = "Bill to Savings";
                $billRemarks = "SAVINGS.CONTRIBUTION";
                $nri = 0;
            }

            if($PnPBillMod=="PBM05" || $PnPBillMod=="PBM07"){
                $billMode = "Bill to CASA";
                $billRemarks = "CASA.CONTRIBUTION";
                $nri = 0;
            }

            if($PnPBillMod=="PBM06"){
                $billMode = "Bill to POS";
                $billRemarks = "CASA.CONTRIBUTION";
                $nri = 0;
            }

            if($billAmnt == 0){
                $billRemarks = "STOPPAGE";
                $billPage = "Yes";
            } else {
                $billPage = "No";
            }

            return array (
                'billMode' => $billMode,
                'billRemarks' => $billRemarks,
                'billPage' => $billPage,
                'nriVal' => $nri
            );
   }

  private function processBillAmount($module,$PnPBillMod,$MOA1,$amortCollection,$amortLpro,$SapMemberNo,$TSAcctTy,
            $BranchSvc,$PINAcctNo,$T24MemberNo,$LastName,$FirstName,$MiddleName,$QualifNam,
            $SvcStat,$billAmnt,$lnType,$dateGranted,$billMode,$deduction_code,$nriVal,$billRemarks,
            $billPage,$amount,$collection_pay_period,$newLineArr,$full_name,$loanBillAmtNewSDLIS,$deduction_codeSDLIS,
            $lproNo,$payPayPeriod,$loanProc,$origDedCode,$filter,$AccountName,$startDate1){
                 
            $samplechange = 0;
            $origAmortCollection = $amortCollection;
            $sdlisAmount = 0;
            $isAddSDLIS = false;
            $origContDate = date('Ymd',strtotime($dateGranted));
            $loanType = $module == "PNPAC" ? "" : $lnType;
            //this function adds another line of a record
            $isAddLine = false;
            if(trim($PnPBillMod) == "PBM01" || trim($PnPBillMod) == "PBM02") {
                     $loanBillAmt = $MOA1;
            } else if (trim($PnPBillMod) == "PBM03" || trim($PnPBillMod) == "PBM04" ||
                    trim($PnPBillMod) == "PBM05" || trim($PnPBillMod) == "PBM07") {
                      
                                if($amortCollection > $amortLpro) {
                                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = true;

                                    $loanBillAmt = $amortCollection - $amortLpro;
                                    $amortCollection = $loanBillAmt;
                                    $getLoanOriginationId = TblMemberAccountFile::findFirst("MemberNo='$SapMemberNo' AND TSAcctTy = '$TSAcctTy'");
                                    $loanOriginationId = $this->getLoanOriginationId($PnPBillMod,$lproNo);

                                    if($loanBillAmtNewSDLIS) {
                                         if(trim($deduction_codeSDLIS) == trim($deduction_code)) {
                                            $loanBillAmt = $loanBillAmt + $loanBillAmtNewSDLIS;
                                         } else {
                                            $isAddSDLIS = true;
                                            $newLineSDLIS =  array (
                                                'up_company' => 'PH0010002', //UPLOAD.COMPANY //(1)
                                                'BranchSvc' => $this->returnEmpty($BranchSvc), //PH.BRANCH.SVC //(2)
                                                'PayPeriod' => $this->returnEmpty($payPayPeriod), //PH.PAYPERIOD //(3)
                                                'PIN' => $this->returnEmpty($PINAcctNo), //(4)
                                                'FullName' => '',
                                                'CustomerCode' => $this->returnEmpty($T24MemberNo), //(5)
                                                'LastName' => $this->returnEmpty($LastName), //(6)
                                                'FirstName' => $this->returnEmpty($FirstName), //(7)
                                                'MiddleName' => $this->returnEmpty($MiddleName), //(8)
                                                'QualifNam'  => $this->returnEmpty($QualifNam), //PH.QUAL.NAME //(9)
                                                'MemberStat' => $this->returnEmpty($SvcStat), //PH.Service Status //(10)
                                                'BillLoanAmt'  => $this->returnEmpty($loanBillAmtNewSDLIS), //(11)
                                                'CapconBillAmt' => '',
                                                'CasaBillAmt' => '',
                                                'PsaBillAmt' => '',
                                                'TotalBillAmt' => '', //(12)
                                                'LoanBillAmt' => $this->returnEmpty($loanBillAmtNewSDLIS), //(13)
                                                'LoanOrigId'   => $this->returnEmpty($loanOriginationId), //(14)
                                                'LoanType'      =>$loanType,// $this->returnEmpty($lnType), //(15)
                                                'AppType'       => '', //(16)
                                                'MaturityDate' => '', //(17)
                                                'OrigContDate' => $this->returnEmpty($origContDate), //(18)
                                                'UpdContDate' => '', //(19)
                                                'LoanTerm' => '', //(20)
                                                'OrigContAmt' => '', //(21)
                                                'LoanStat' => $this->returnEmpty($loanProc), //(22)
                                                'StartAmrtDate' => '', //(23)
                                                'BillTransType' => '', //(24)
                                                'BillTransStat' => '', //(25)
                                                'BillMode'       => $this->returnEmpty($billMode), //(26)
                                                'OrigDedNCode' => $this->returnEmpty($origDedCode), //(27)
                                                'UpdDedNCode' => '', //(28)
                                                'OrigNri'   => 'PENDING', //(29)
                                                'UpdtNri'  => '', //(30)
                                                'Remarks'    => $this->returnEmpty($billRemarks), //(31)
                                                'StopPage' => $this->returnEmpty($billPage), //(32)
                                                'AtmCardNo' => '', //(33)
                                                'FullName2' => '', //(34)
                                                'PensAcctNo' => '', //(35)
                                                'PayMtStats' => '', //(36)
                                                'StopDedNCode' => '', //(37)
                                                'IncldBilInd' => "YES", //(38)
                                                '1stLoadAmt' => "", //(39)
                                                'CollectionAmount' => $this->returnEmpty($amount), //(40)
                                                'collection_pay_period' => $this->returnEmpty($collection_pay_period), //(41)
                                                'lproNo' => $loanOriginationId
                                            );

                                         }
                                    }

                                         $newLine =  array (
                                            'up_company' => 'PH0010002', //UPLOAD.COMPANY
                                            'BranchSvc' => $this->returnEmpty($BranchSvc), //PH.BRANCH.SVC
                                            'PayPeriod' => $this->returnEmpty($payPayPeriod), //PH.PAYPERIOD
                                            'PIN' => $this->returnEmpty($PINAcctNo),
                                            'FullName' => '',
                                            'CustomerCode' => $this->returnEmpty($T24MemberNo),
                                            'LastName' => $this->returnEmpty($LastName),
                                            'FirstName' => $this->returnEmpty($FirstName),
                                            'MiddleName' => $this->returnEmpty($MiddleName),
                                            'QualifNam'  => $this->returnEmpty($QualifNam), //PH.QUAL.NAME
                                            'MemberStat' => $this->returnEmpty($SvcStat), //PH.Service Status
                                            'BillLoanAmt'  => $this->returnEmpty($loanBillAmt),
                                            'CapconBillAmt' => '',
                                            'CasaBillAmt' => '',
                                            'PsaBillAmt' => '',
                                            'TotalBillAmt' => '',
                                            'LoanBillAmt' => $this->returnEmpty($loanBillAmt),
                                            'LoanOrigId'   => $this->returnEmpty($loanOriginationId),
                                            'LoanType'      => $this->returnEmpty($lnType),
                                            'AppType'       => '',
                                            'MaturityDate' => '',
                                            'OrigContDate' => $this->returnEmpty($origContDate),
                                            'UpdContDate' => '',
                                            'LoanTerm' => '',
                                            'OrigContAmt' => '',
                                            'LoanStat' => $this->returnEmpty($loanProc),
                                            'StartAmrtDate' => $this->returnEmpty($startDate1),
                                            'BillTransType' => '',
                                            'BillTransStat' => '',
                                            'BillMode'       => $this->returnEmpty($billMode),
                                            'OrigDedNCode' => $this->returnEmpty($origDedCode),
                                            'UpdDedNCode' => '',
                                            'OrigNri'   => 'PENDING',
                                            'UpdtNri'  => '',
                                            'Remarks'    => $this->returnEmpty($billRemarks),
                                            'StopPage' => $this->returnEmpty($billPage),
                                            'AtmCardNo' => '',
                                            'FullName2' => '',
                                            'PensAcctNo' => '',
                                            'PayMtStats' => '',
                                            'StopDedNCode' => '',
                                            'IncldBilInd' => "YES",
                                            '1stLoadAmt' => "",
                                            'CollectionAmount' => $this->returnEmpty($amount),
                                            'collection_pay_period' => $this->returnEmpty($collection_pay_period),
                                        );

                                        array_push($newLineArr,$newLine);
                                        if ($isAddSDLIS == true){
                                            array_push($newLineArr,$newLineSDLIS);
                                        }

                                        $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = $newLineArr;


                                } else {
                                    $loanBillAmt = $MOA1;
                                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = $newLineArr;
                                    // return false;
                                }

               
            }
  }

private function processBillAmountGrpSDLIS($module,$PnPBillMod,$MOA1,$amortCollection,$amortLpro,$SapMemberNo,$TSAcctTy,
                                $BranchSvc,$PINAcctNo,$T24MemberNo,$LastName,$FirstName,$MiddleName,$QualifNam,
                                $SvcStat,$billAmnt,$lnType,$dateGranted,$billMode,$deduction_code,$nriVal,$billRemarks,
                                $billPage,$amount,$collection_pay_period,$newLineArr,$full_name,$newRecordArray,
                                $loanBillAmtNewSDLIS,$deduction_codeSDLIS,$lproNo,$amountCollect,$payPayPeriod,$loanProc,$origDedCode,$filter,$AccountName,$startDate1){

           
            $origAmortCollection = $amortCollection;
            $sdlisAmount = 0;
            $isAddSDLIS = false;

           //this function adds another line of a record
            if(trim($PnPBillMod) == "PBM01" || trim($PnPBillMod) == "PBM02") {
                     $loanBillAmt = $MOA1;
            } else if (trim($PnPBillMod) == "PBM03" || trim($PnPBillMod) == "PBM04" ||
                     trim($PnPBillMod) == "PBM05" || trim($PnPBillMod) == "PBM07") {
                          
                        // print_r("($LastName,$deduction_codeSDLIS,$deduction_code)");
                                if($amortCollection > $amortLpro) {
                                   
                                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = true;
                                    
                                    $loanBillAmt = $amortCollection - $amortLpro;
                                    
                                    
                                    $amortCollection = $loanBillAmt;
                                    $getLoanOriginationId = TblMemberAccountFile::findFirst("MemberNo='$SapMemberNo' AND TSAcctTy = '$TSAcctTy'");
                                    $loanOriginationId = $this->getLoanOriginationId($PnPBillMod,$lproNo);
                                 
                                   
                                    if($loanBillAmtNewSDLIS) {
                                        if(trim($deduction_codeSDLIS) == trim($deduction_code)) {
                                           $loanBillAmt = $loanBillAmt + $loanBillAmtNewSDLIS;
                                        } else {

                                            $isAddSDLIS = true;
                                            $newLineSDLIS =  array (
                                                'up_company' =>'PH0010002',
                                                'BranchSvc' =>$this->returnEmpty($BranchSvc),
                                                'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                                                'PIN' => $this->returnEmpty($PINAcctNo),
                                                'full_name' =>'',
                                                'MemberStat' => $this->returnEmpty($T24MemberNo),
                                                'LastName' => $this->returnEmpty($LastName),
                                                'FirstName' => $this->returnEmpty($FirstName),
                                                'MiddleName' => $this->returnEmpty($MiddleName),
                                                'QualifNam' => $this->returnEmpty($QualifNam),
                                                'SvcStat' => $this->returnEmpty($SvcStat),
                                                'loan_bill_amount' => $this->returnEmpty(number_format($loanBillAmtNewSDLIS, 2,".","")),
                                                'capcon_bill_amount' =>"",
                                                'casa_bill_amount' =>"",
                                                'psa_bill_amount' =>"",
                                                'total_bill_amnt' =>"",
                                                'bill_amt' =>$this->returnEmpty(number_format($loanBillAmtNewSDLIS, 2,".","")),
                                                'lproNo' => $this->returnEmpty($loanOriginationId),
                                                'lnType' =>"",
                                                'loanAppl' =>"",
                                                'maturityDate' =>"",
                                                'dateGrant' =>$this->returnEmpty($dateGranted),
                                                'updContDate' =>"",
                                                'loanTerm' =>"",
                                                'origContDate' =>$this->returnEmpty($dateGranted),
                                                'loanStat' =>$this->returnEmpty($loanProc),
                                                'startAmrtDate' =>$this->returnEmpty($startDate1),
                                                'billTransType' =>"",
                                                'bilTransStat' =>"",
                                                'billMode' =>$this->returnEmpty($billMode),
                                                'orgDeDNCode' =>$this->returnEmpty($origDedCode),
                                                'updtDeDNCode' =>"",
                                                'lnpTrate' =>$this->returnEmpty($nriVal),
                                                'uptNri' =>"",
                                                'Remarks' =>$this->returnEmpty($billRemarks),
                                                'stop_page' =>$this->returnEmpty($billPage),
                                                'atmCardNo' =>"",
                                                'fullName2' =>"",
                                                'pensAcctNo' =>"",
                                                'payMtStats' =>"",
                                                'stopDedNCode' =>"",
                                                'incldBilInd' =>"YES",
                                                '1stLoadAmt' =>"",
                                                'collection_amount' =>$this->returnEmpty(number_format($amountCollect, 2,".","")),
                                                'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                                            );
                                        }
                                    }
                                    // print_r("(NEWLINEARR: $LastName,$deduction_codeSDLIS,$deduction_code,$loanBillAmt)");
                                        $newLine =  array (
                                            'up_company' =>'PH0010002',
                                            'BranchSvc' =>$this->returnEmpty($BranchSvc),
                                            'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                                            'PIN' => $this->returnEmpty($PINAcctNo),
                                            'full_name' =>'',
                                            'MemberStat' => $this->returnEmpty($T24MemberNo),
                                            'LastName' => $this->returnEmpty($LastName),
                                            'FirstName' => $this->returnEmpty($FirstName),
                                            'MiddleName' => $this->returnEmpty($MiddleName),
                                            'QualifNam' => $this->returnEmpty($QualifNam),
                                            'SvcStat' => $this->returnEmpty($SvcStat),
                                            'loan_bill_amount' => $this->returnEmpty(number_format($loanBillAmt, 2,".","")),
                                            'capcon_bill_amount' =>"",
                                            'casa_bill_amount' =>"",
                                            'psa_bill_amount' =>"",
                                            'total_bill_amnt' => $this->returnEmpty(number_format($billAmnt, 2,".","")),
                                            'bill_amt' =>$this->returnEmpty(number_format($loanBillAmt, 2,".","")),
                                            'lproNo' => $this->returnEmpty($loanOriginationId),
                                            'lnType' =>"",
                                            'loanAppl' =>"",
                                            'maturityDate' =>"",
                                            'dateGrant' =>$this->returnEmpty($dateGranted),
                                            'updContDate' =>"",
                                            'loanTerm' =>"",
                                            'origContDate' =>$this->returnEmpty($dateGranted),
                                            'loanStat' =>$this->returnEmpty($loanProc),
                                            'startAmrtDate' =>$this->returnEmpty($startDate1),
                                            'billTransType' =>"",
                                            'bilTransStat' =>"",
                                            'billMode' =>$this->returnEmpty($billMode),
                                            'orgDeDNCode' =>$this->returnEmpty($origDedCode),
                                            'updtDeDNCode' =>"",
                                            'lnpTrate' =>$this->returnEmpty($nriVal),
                                            'uptNri' =>"",
                                            'Remarks' =>$this->returnEmpty($billRemarks),
                                            'stop_page' =>$this->returnEmpty($billPage),
                                            'atmCardNo' =>"",
                                            'fullName2' =>"",
                                            'pensAcctNo' =>"",
                                            'payMtStats' =>"",
                                            'stopDedNCode' =>"",
                                            'incldBilInd' =>"YES",
                                            '1stLoadAmt' =>"",
                                            'collection_amount' =>$this->returnEmpty(number_format($amountCollect, 2,".","")),
                                            'collection_pay_period' => $this->returnEmpty($collection_pay_period)

                                        );

                                    array_push($newLineArr,$newLine);
                                    if ($isAddSDLIS == true){
                                        array_push($newLineArr,$newLineSDLIS);

                                    }

                                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = $newLineArr;

                                } else {
                                    $loanBillAmt = $MOA1;
                                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = $newLineArr;
                                }
            }
  }

       private function getLoanOriginationId($BillMode,$loanOrig){
            switch ($BillMode) {
                case "PBM03":
                $loanOriginationId = "CA";
                break;
                case "PBM04":
                $loanOriginationId = "SA";
                break;
                case "PBM05":
                $loanOriginationId = "CS";
                break;
                case "PBM07":
                $loanOriginationId = $loanOrig;
                break;
                default:
                $loanOriginationId = $loanOrig;
            }
            return $loanOriginationId;
        }

        private function getBillRemarks($PBillMode,$dateGrant,$startDate,$endDate,$ifClientExistsCollection,$ifClientExistsLpro,$amortLpro,$amortCollection){
               $billRemarks = "";
                if($PBillMode == "PBM01" || $PBillMode == "PBM02") {
                    if(($startDate > $dateGrant && $dateGrant < $endDate) && empty($ifClientExistsCollection)){
                        $billRemarks = "NEW LOAN";
                    }
                } else if ($PBillMode == "PBM03") {
                    $billRemarks = "CAPITAL.CONTRIBUTION";
                } else if ($PBillMode == "PBM04") {
                    $billRemarks = "SAVINGS.CONTRIBUTION";
                } else if ($PBillMode == "PBM06") {
                    $billRemarks = "";
                } else if ($PBillMode == "PBM05" || $PBillMode == "PBM07" ) {
                    $billRemarks = $PBillMode == "PBM05" ? "CASA.CONTRIBUTION" : "";
                }

                if($amortLpro || $amortCollection){
                    $billRemarks = "STOPPAGE";
                }
                if(!empty($ifClientExistsLpro) && !empty($ifClientExistsCollection)){
                    $billRemarks = "EXISTING RENEWAL";
                }

                $dateGeneratedStr = explode('-', $dateGrant);
                $dateGeneratedStr[2] = '26';
                $dateGeneratedStr = implode('-', $dateGeneratedStr);
                $dateGenerated =  date('Y-m-d', strtotime($dateGeneratedStr));
                    if(($dateGrant < $dateGenerated) && ($amortLpro > $amortCollection)) {
                        $billRemarks = "EXISTING REBILL";
                    }
                    if(($dateGrant < $dateGenerated) && (empty($ifClientExistsCollection))) {
                        $billRemarks = "REBILL";
                    }
                    if(($dateGrant < $dateGenerated) && ($amortLpro <= $amortCollection )) {
                        $billRemarks = "EXISTING";
                    }

                //FOR PH.BILL.STOPPAGE
                $billPage = $billRemarks == "STOPPAGE" ? "YES" : "NO";

                return array(
                    'billRemarks' => $billRemarks,
                    'billPage' => $billPage
                );

        }

/* END OF GLOBAL FUNCTIONS ========================================================================== */

    public function exportAtmAction(){
        ini_set('memory_limit', '-1');
       $date = date("YmdHis");
       $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
       $path2 = "/var/www/html/psslai/public/export/fileLog_tempATMInventory.log";
       $pathtxt = "/var/www/html/psslai/public/export/LOCAL.ATM.CARD_ALL_ao".$date.".txt";
       $txt = fopen($pathtxt, "w") or die("Unable to open file!");
       $exportCsv1 = 'export/LOCAL.ATM.CARD_ALL_ao.log'.$date.'.csv';
       $exportTxt2 = 'export/LOCAL.ATM.CARD_ALL_ao'.$date.'.txt';
        $getQry = TblLoanCsvFile::query()
         ->execute();
         $ctr = 0;
         $info = [];
         $getQryLoanAtm =[];
         $lproNoGen = "";
         $dateGrantGen = "";
         $maturityGen = "";
         $atmSetting = "";
         $loanAmtGen = "";
         $loanApplGen = "";
         $MOA1Gen = "";
         $startDate1Gen = "";
         $placeCodeGen = "";
         $data3 = "";
         $sapCode = "";
         $fp = fopen('/var/www/html/psslai/public/export/LOCAL.ATM.CARD_ALL_ao'.$date.'.csv', 'w');
        $headerCSV = ['UPLOAD.COMPANY','ID','LOAN.ACCT.NO','LOAN.PRODUCT', 'LOAN.DATE.GRANTED', 'LOAN.MAT.DATE', 'BANK.NAME', 'ATM.ACCT.NO.1', 'ATM.CARD.NO.1', 'ATM.PIN', 'ATM.BAL.RCPT1', 'ATM.BAL.RCPT2', 'ATM.BAL.RCPT3',
            'DATE.RECEIVED', 'PICOS.NO', 'ATM.BAL.RELEASED', 'ATM.CARD.STATUS', 'ATM.PULLOUT.DATE', 'PULLOUT.REASON', 'PH.BRANCH.SVC', 'PH.SERVICE.STATUS', 'PH.LAST.NAME', 'PH.FIRST.NAME', 'PH.MIDDLE.NAME', 'PH.QUAL.NAME',
            'PH.ORIG.CONT.AMT', 'PH.MOAMORT1', 'PH.START.AMRT.DATE', 'PLACE.CODE', 'LOAN.APPL.TYPE', 'DATE.RELEASED	', 'REMARKS'];
        $removeText = [
            'up_company:',
            'MemberNo:',
            'lproNo:',
            'lnType:',
            'lnProduct:',
            'dateGrant:',
            'maturity:',
            'bank_name:',
            'REFERENCE:',
            'CONTROL:',
            'PIN:',
            'atm_bal_rcpt1:',
            'atm_bal_rcpt2:',
            'atm_bal_rcpt3:',
            'DATERCVD:',
            'PICOSNO:',
            'atm_bal_release:',
            'atm_bal_release:',
            'ATMCARDSTAT:',
            'ATMPULLOUTDATE:',
            'PULLOUTREASON:',
            'BranchSvc:',
            'SvcStat:',
            'LastName:',
            'FirstName:',
            'MiddleName:',
            'QualifNam:',
            'loanAmount:',
            'MOA1:',
            'startDate1:',
            'place_code:',
            'loanAppl:',
            'DATERELEASED:',
            'Remarks:'
        ];
            fputcsv($fp, $headerCSV );
            $checking = "";
        foreach($getQry as $data){
            //create a file handler by opening the file
            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $percent = $ctr /count($getQry) * 100;
            echo "ATM INVENTORY - Exporting file : ".number_format($percent,2)."%";
            $data1 = TblMemberInfoFile::findFirst("SapMemberNo = '$data->memberNo'");
            $data2 = TblAtmListFile::findFirst("CLIENT = '$data->memberNo'");
            if($data2 != null){
                $atmSetting = TblAtm::findFirst("atm_card_status = '".$data2->ATMCARDSTAT."'");
            }
            //Multiple Delimer
            if($data1 != null){
                $getQryLoanAtm = TblLoanAtmFile::query()
                ->where("memberNo = '".$data1->SapMemberNo."'")
                ->execute();
            }
            if(count($getQryLoanAtm) != 0){
                // foreach($getQryLoanAtm as $LoanAtm){
                //     if($LoanAtm->loanAppl != $checking){
                //         $lproNoGen .=  $LoanAtm->lproNo."::";
                //         $dateGrantGen .= date('Ymd',strtotime($LoanAtm->dateGrant))."::";
                //         $maturityGen .= date('Ymd',strtotime($LoanAtm->maturity))."::";
                //         $loanAmtGen .= $LoanAtm->loanAmt."::";
                //         $MOA1Gen .=  $LoanAtm->MOA1."::";
                //         $startDate1Gen .= date('Ymd',strtotime($LoanAtm->startDate1))."::";
                //         $sapCode .= substr($LoanAtm->lproNo,0,2);
                //         $placeCodeT24 = TblBranch::findFirst('sap_place_code ='.$sapCode );
                //         if($placeCodeT24 != null) {
                //             $placeCodeGen .= $sapCode.$placeCodeT24->t24_branch_ext."::";
                //         }
                //         $loanApplGen .=  $LoanAtm->loanAppl."::";
                //         $checking = $LoanAtm->loanAppl;
                //     }
                // }
                foreach($getQryLoanAtm as $dataLoanAtm){
                    if (!empty($dataLoanAtm->lproNo)) {
                        $sapCode = substr($dataLoanAtm->lproNo,0,2);
                        $placeCodeT24 = TblBranch::findFirst('sap_place_code ='.$sapCode );
                        // print_r($placeCodeT24);
                        $lproNoGen .=  str_replace('-','',$dataLoanAtm->lproNo)."::";
                        $lnTypeGen .=  $dataLoanAtm->lnType."!!";
                        // $dateGrantGen .= $dataLoanBilling->dateGrant."::";
                        // $maturityGen .= $dataLoanBilling->maturity."::";

                        $dateGrantGen .= $dataLoanAtm->dateGrant == "" ? "" : date('Ymd',strtotime($dataLoanAtm->dateGrant))."::";
                        $maturityGen .= $dataLoanAtm->maturity == "" ? ""  : date('Ymd',strtotime($dataLoanAtm->maturity))."::";

                        $placeCodeGen .= $placeCodeT24->t24_branch_ext."::";
                        $loanAmtGen .= number_format($dataLoanAtm->loanAmt,2,".",'')."::";

                        $getLoanType = $dataLoanAtm->lnType;
                        $loanTypeTbl = TblLoanType::findFirst("loan_type = '$getLoanType'");
                    }
                    $t24Desc = TblLoanAppType::findFirst("sap_code = '$dataLoanAtm->loanAppl'");
                    $loanApplGen .= $t24Desc->description ."::";
                    // $startDate1Gen .=  $dataLoanAtm->startDate1."::";
                    $startDate1Gen .= $dataLoanAtm->startDate1 == "" ? "" : date('Ymd',strtotime($dataLoanAtm->startDate1))."::";
                    $MOA1Gen .= number_format($dataLoanAtm->MOA1,2,".",'')."::";
                }
                $getLoanType = $dataLoanAtm->lnType;
                $data3 = TblLoanType::findFirst("loan_type = '$getLoanType'");
            }
            $remarks = $data2 == null ? "RECEIVED" : "RELEASE";
            $bankName = $data2 == null ? "" : $data2->DATERCVD;
            if($data2){
                $temp_info = array(
                    'up_company' => 'PH0010002', //UPLOAD.COMPANY
                    'MemberNo' => $data1 == null ? "" : $data1->T24MemberNo, //ID
                    'lproNo' =>  substr($lproNoGen,0,-2) == false ? "" : substr($lproNoGen,0,-2), //LproNo
                    'lnProduct' => $data3 == null ? "" :  $data3->product,
                    'dateGrant' => substr($dateGrantGen,0,-2) == false ? "" : substr($dateGrantGen,0,-2), //dateGrant
                    'maturity' => substr($maturityGen,0,-2) == false ? "" : substr($maturityGen,0,-2), //maturity
                    'bank_name' => $bankName != ""  ? "LBP" : "", //Bank Name
                    'REFERENCE' => $data2 == null ? "" : $data2->REFERENCE, //ATM.ACCT.NO.1
                    'CONTROL' =>   $data2 == null ? "" : $data2->CONTROL, //ATM.CARD.NO.1
                    'PIN' =>  $data2 == null ? "" : $data2->PIN, //ATM.PIN
                    'atm_bal_rcpt1'  => ($data2 == null ? "" : $data2->DATERCVD) == null ? "": '1.00', //'1.00', //ATM.BAL.RCPT1
                    'atm_bal_rcpt2'  => ($data2 == null ? "" : $data2->DATERCVD) == null ? "": '1.00', //'1.00', //ATM.BAL.RCPT2
                    'atm_bal_rcpt3'  => ($data2 == null ? "" : $data2->DATERCVD) == null ? "": '1.00', //'1.00', //ATM.BAL.RCPT3
                    'DATERCVD' =>  date('Ymd',strtotime($data2 == null ? "" : $data2->DATERCVD)),
                    'PICOSNO' => $data2 == null ? "" : $data2->PICOSNO, //PICOS.NO
                    'atm_bal_release'  =>  "0.00", //ATM.BAL.RELEASED
                    'ATMCARDSTAT' => $atmSetting == null ? "" : $atmSetting->atm_card_status_description, //ATM.CARD.STATUS
                    'ATMPULLOUTDATE' => $data2 == null ? "" :  date('Ymd',strtotime($data2->DATERELEASED)), //ATM.PULLOUT.DATE
                    'PULLOUTREASON' => $data2 == null ? "" : trim($data2->PULLOUTREASON), //PULLOUT.REASON
                    'BranchSvc' => $data1 == null ? "" : $data1->BranchSvc, //PH.BRANCH.SVC
                    'SvcStat' => $data1 == null ? "" : $data1->SvcStat, //PH.SERVICE.STATUS
                    'LastName' => trim($data1 == null ? "" : $data1->LastName),
                    'FirstName' => trim($data1 == null ? "" : $data1->FirstName),
                    'MiddleName' => trim($data1 == null ? "" : $data1->MiddleName),
                    'QualifNam'  => trim($data1 == null ? "" : $data1->QualifNam),
                    'loanAmount' => $loanAmtGen,//PH.ORIG.CONT.AMT
                    'MOA1'      => substr($MOA1Gen,0,-2) == false ? "" : substr($MOA1Gen,0,-2), //PH.MOAMORT1
                    'startDate1' => substr($startDate1Gen,0,-2) == false ? "" : substr($startDate1Gen,0,-2),
                    'place_code' => substr($placeCodeGen,0,-2) == false ? "" : substr($placeCodeGen,0,-2), //PLACE.CODE
                    'loanAppl' => substr($loanApplGen,0,-2) == false ? "" : substr($loanApplGen,0,-2),
                    'DATERELEASED' => $data2 == null ? "" : date('Ymd',strtotime($data2->DATERELEASED)), //ATM.PULLOUT.DATE
                    'Remarks'    => $remarks
                );
                $ctr++;
                array_push($info,$temp_info);
            }
            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
            fwrite($txt,$result);
            $lproNoGen = "";
            $lnTypeGen = "";
            $dateGrantGen = "";
            $maturityGen = "";
            $placeCodeGen = "";
            $loanAmtGen = "";
            $loanApplGen = "";
            $startDate1Gen = "";
            $MOA1Gen  = "";

        }
        $myTextFileHandler = fopen($path2,"r+");
        $d = ftruncate($myTextFileHandler, 0);
        fclose($myTextFileHandler);
        $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
        echo $fnlRes;
        exit;
    }

    public function exportAtmReconAction(){
        ini_set('memory_limit', '-1');
        $date = date("YmdHis");
        $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        $path2 = "/var/www/html/psslai/public/export/Log_tempATMRECON.log";
        $exportCsv1 = 'export/LOCAL.ATM.CARD_ALL_ao'.$date.'.csv';
        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $c = "TblAtmListFile";
        $f = "TblLoanAppType"; //Loan Atm
        $ctr = 1;
        $getQry = TblLoanCsvFile::query()
        ->execute();
        $fp = fopen('/var/www/html/psslai/public/export/LOCAL.ATM.CARD_ALL_ao'.$date.'.csv', 'w');
        $headerCSV = ['UPLOAD.COMPANY','T24 MEMBER NO.', 'SAP LOAN ACCOUNT NO.', 'T24 LOAN ACCOUNT NO.', 'DATE GRANTED', 'MATURITY DATE', 'LOAN PRODUCT', 'LOAN AMT', 'MOA1', 'START DATE', 'DATE RECEIVED', 'BANK NAME', 'ATM ACCOUNT NUMBER', 'ATM CARD NUMBER',
            'ATM PIN', 'ATM BAL RECEIPT 1', 'ATM BAL RECEIPT 2', 'ATM BAL RECEIPT 3', 'PICOS NO.', 'ATM CARD STATUS', 'ATM BALANCE RELEASED', 'DATE RELEASED', 'PULL OUT REASON'];
        fputcsv($fp, $headerCSV );
        foreach($getQry as $data){
             //create a file handler by opening the file
             $myTextFileHandler = fopen($path2,"r+");
             $d = ftruncate($myTextFileHandler, 0);
             fclose($myTextFileHandler);
             $percent = $ctr /count($getQry) * 100;
             echo "ATM RECON - Exporting file : ".number_format($percent,2)."%";
            $data1 = TblAtmListFile::findFirst("CLIENT = '$data->memberNo'");
            $data2 = TblMemberInfoFile::findFirst("SapMemberNo = '$data->memberNo'");
            if($data != null){
                $getQryLoanAtm = TblLoanAtmFile::query()
                ->where("memberNo = '".$data->memberNo."'")
                ->execute();
            }
            if($data1){
                $info = array(
                    'up_company' => 'PH0010002',
                    'T24MemberNo' => $data2 == null ? "" : $data2->T24MemberNo, //ID
                    'lproNo' => $data2 == null ? "" : $data->lproNo, //LproNo
                    'lproNo24' => str_replace('-','',($data == null ? "" : $data->lproNo)), //LproNo
                    'dateGrant' => date('Ymd',strtotime($data == null ? "" : $data->dateGrant)), //dateGrant
                    'maturity' => date('Ymd',strtotime($data == null ? "" : $data->maturity)), //maturity
                    'lnProduct' => "",
                    'loanAmount' => $data->loanAmt,//PH.ORIG.CONT.AMT
                    'MOA1' => number_format($data->MOA1,2,".",''), //PH.MOAMORT1
                    'startDate1' => date('Ymd',strtotime($data->startDate1)),
                    'DATERCVD' => $data1 == null ? "" : date('Ymd',strtotime($data1->DATERCVD)), //DATE.RECEIVED
                    'bank_name' => ($data1 == null ? "" : $data1->DATERCVD) == "" ? "" : "LBP", //Bank Name
                    'REFERENCE' => $data1 == null ? "" : $data1->REFERENCE, //ATM.ACCT.NO.1
                    'CONTROL' =>  $data1 == null ? "" : $data1->CONTROL, //ATM.CARD.NO.1
                    'PIN' => $data1 == null ? "" : $data1->PIN, //ATM.PIN
                    'atm_bal_rcpt1'  => '1.00', //ATM.BAL.RCPT1
                    'atm_bal_rcpt2'  => '1.00', //ATM.BAL.RCPT2
                    'atm_bal_rcpt3'  => '1.00', //ATM.BAL.RCPT3
                    'PICOSNO' => $data1 == null ? "" : $data1->PICOSNO, //PICOS.NO
                    'ATMCARDSTAT' => $data1 == null ? "" : $data1->ATMCARDSTAT, //ATM.CARD.STATUS
                    'atm_bal_release'  => '0.00', //ATM.BAL.RELEASED,
                    'DATERELEASED' => $data1 == null ? "" : date('Ymd',strtotime($data1->DATERELEASED)), //ATM.PULLOUT.DATE
                    'PULLOUTREASON' => $data1 == null ? "" : $data1->PULLOUTREASON, //PULLOUT.REASON
                );
                fputcsv($fp, $info );
                $ctr++;
            }
          
        }
        $myTextFileHandler = fopen($path2,"r+");
        $d = ftruncate($myTextFileHandler, 0);
        fclose($myTextFileHandler);
        $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
        echo $fnlRes;
        exit;
    }
    //Billing Module
    public function exportBillingAtmAction() {
        $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        ini_set('memory_limit', '-1');
        $ctr = 0;
        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $d = "TblLoanCsvFile";
        $getQry = TblMemberInfoFile::query()
        ->execute();
           $info = array();
            if($getQry){
            $fp = fopen('/var/www/html/psslai/public/export/BILLING_ALL-NTHL_ao'.$date.'.csv', 'w');
            $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
            $pathtxt = "/var/www/html/psslai/public/export/BILLING_ALL-NTHL_ao".$date.".txt";
            $txt = fopen($pathtxt, "w") or die("Unable to open file!");
            $exportCsv1 = 'export/BILLING_ALL-NTHL_ao'.$date.'.csv';
            $exportTxt2 = 'export/BILLING_ALL-NTHL_ao'.$date.'.txt';
            $ctr = 0;
            $info = array();
            $payPayPeriod = "";
            $remarks = "";
            $headerCSV = ['UPLOAD.COMPANY',
                'PH.BRANCH.SVC',
                'PH.PAY.PERIOD',
                'PH.PIN.NO',
                'PH.FULLNAME1',
                'CUSTOMER.CODE',
                'PH.LAST.NAME',
                'PH.FIRST.NAME',
                'PH.MIDDLE.NAME',
                'PH.QUAL.NAME',
                'PH.SERVICE.STATUS',
                'PH.LOAN.BILL.AMT',
                'PH.CAPCON.BILL.AMT',
                'PH.CASA.BILL.AMT',
                'PH.PSA.BILL.AMT',
                'PH.TOTAL.BILL.AMT',
                'PH.BILL.AMT',
                'PH.LOAN.ORIG.ID',
                'PH.LOAN.TYPE',
                'PH.APP.TYPE',
                'PH.MATURITY.DATE',
                'PH.ORIG.CONT.DATE',
                'PH.UPD.CONT.DATE',
                'PH.LOAN.TERM',
                'PH.ORIG.CONT.AMT',
                'PH.LOAN.STAT',
                'PH.START.AMRT.DATE',
                'PH.BILL.TRANS.TYPE',
                'PH.BIL.TRANS.STAT',
                'PH.BILL.MODE',
                'PH.ORIG.DEDNCODE',
                'PH.UPD.DEDNCODE',
                'PH.ORIG.NRI',
                'PH.UPDATED.NRI',
                'PH.BILL.REMARKS',
                'PH.BILL.STOPPAGE',
                'PH.ATM.CARD.NO',
                'PH.FULLNAME2',
                'PH.PENS.ACCT.NO',
                'PH.PAYMT.STAT',
                'PH.STOP.DEDN.CODE',
                'PH.INCLD.BIL.IND',
                'PH.1ST.LOAD.AMT',
                'AMOUNT.COLLECT',
                'PAY.PERIOD.COLLECT'
            ];
            fputcsv($fp, $headerCSV );
                foreach($getQry as $data){
                    if($data){
                        $myfile = fopen($path2, "w") or die("Unable to open file!");
                        fwrite($myfile,  $test);
                        $percent = $ctr /count($getQry) * 100;
                        echo "BILLING ATM - Exporting file : ".number_format($percent,2)."%";

                        $collectionPayAtm1 = TblCollectionAtm1::findFirst("member_no = '".$data->memberNo."'");
                        $collectionPayAtm2 = TblCollectionAtm2::findFirst("member_no = '".$data->memberNo."'");

                        // if ($filter != "" && $filterD != ""){
                        //     $data3 = TblLoanCsvFile::findFirst(array(
                        //         "conditions" => "(memberNo = '$data->SapMemberNo') AND $filterD",
                        //     ));
                        // } else {
                            $data3 = TblLoanCsvFile::findFirst(array(
                                "conditions" => "memberNo = '$data->SapMemberNo'",
                            ));
                        // }
                        // if($data3){
                            // if ($filter != "" && $filterB != ""){
                            //     $data1 = TblMemberAccountFile::findFirst(array(
                            //         "conditions" => "(MemberNo = '$data->SapMemberNo') AND $filterB",
                            //     ));
                            // } else {
                                $data1 = TblMemberAccountFile::findFirst(array(
                                    "conditions" => "MemberNo = '$data->SapMemberNo'",
                                ));
                            // }
                            // if($data1){
                                $data2 = TblAtmListFile::findFirst("CLIENT = '$data->SapMemberNo'");
                                //get collection atm
                                $collectionPayAtm1 = TblCollectionAtm1::findFirst("member_no = '".$data->SapMemberNo."'");
                                $collectionPayAtm2 = TblCollectionAtm2::findFirst("member_no = '".$data->SapMemberNo."'");
                                if($collectionPayAtm1 && $collectionPayAtm2){
                                    $collectionPay = $collectionPayAtm1->amount_collected + $collectionPayAtm2->hold_amort;
                                    $collectPeriod = $collectionPayAtm1->collection_pay_period;
                                } else  if($collectionPayAtm1){
                                    $collectionPay = $collectionPayAtm1->amount_collected;
                                    $collectPeriod = $collectionPayAtm1->collection_pay_period;
                                } else if($collectionPayAtm2){
                                    $collectionPay = $collectionPayAtm2->hold_amort;
                                    $collectPeriod = $collectionPayAtm1->collection_pay_period;
                                }

                                $loanBillAmt = ""; $capconBillAmt = ""; $casaBillAmt = ""; $psaBillAmt = "";
                                $billRemarks = ""; $billMode = ""; $amount_collect = "0.00";
                                if($data3){
                                    if($data3->PnPBillMod=="PBM01" || $data3->PnPBillMod=="PBM02"){
                                        $loanBillAmt = $data3->MOA1;
                                        $billMode = "Bill to POS";
                                    } else if($data3->PnPBillMod=="PBM03"){
                                        $billMode = "Bill to POS";
                                        $billRemarks = "CAPITAL.CONTRIBUTION";
                                    } else if($data3->PnPBillMod=="PBM04"){
                                        $billMode = "Bill to POS";
                                        $billRemarks = "SAVINGS.CONTRIBUTION";
                                    }else if($data3->PnPBillMod=="PBM05"){
                                        $amount_collect = $data3->contribution_amount;
                                        $billMode = "Bill to POS";
                                        $billRemarks = "CASA.CONTRIBUTION";
                                    } else if($data3->PnPBillMod=="PBM00"){
                                        $billMode = "Bill to POS";
                                    }
                                    else{
                                        $billMode = "";
                                    }
                                } else {
                                    $billMode = "";
                                }
                                // if($data3){
                                //    if($billMode = "Bill to CAPCON" || $billMode = "Bill to CASA" || $billMode = "Bill to Savings"){
                                //        $origId = $data1->AccountName;
                                //     }
                                // } else {
                                   $origId = str_replace("-", "", $data3->lproNo);
                                // }
                                $checkkingMonth = date("m");
                                $checkkingYear = date("Y");
                                $cuttOff = $checkkingMonth."-27-".$checkkingYear;
                                if (strtotime($dategrant) < strtotime($cuttOff)){
                                    $dategrant = date("Ym", strtotime($data->dateGrant));
                                    $current_month = (int) date('m',strtotime($data->dateGrant));
                                        $year = date('y',strtotime($data->dateGrant));
                                        $newDate = date('Y-m-d', strtotime($data->dateGrant.' + 1 month'));
                                        if($current_month == 12)
                                        {
                                            $new_month=0;
                                            $year++;
                                        }
                                        $remarks = "New Loan";
                                        // $payPayPeriod = new DateTime( ($current_month+2). date("d", strtotime($data->dateGrant)).$year );
                                } else {
                                    $remarks = "Existing";
                                    $dategrant = date("Y-m-d", strtotime($data->dateGrant));
                                    // $payPayPeriod =  date("dmy", strtotime( $dategrant,"1 month"));
                                }

                                $get1stdate = TblLoanCsvFile::query()
                                ->columns('initDate, id_loan_csv_name')
                                ->limit(1)
                                ->orderBy("id Desc")
                                ->execute();
                                $payPayPeriod = $this->getPayPeriod($data3->id_loan_csv_name, trim($data->BranchSvc));
                                if(!$payPayPeriod){
                                    $payPayPeriod = $this->getPayPeriod(trim($get1stdate[0]->id_loan_csv_name), trim($data->BranchSvc));
                                }
                                if ($data->MOA1 == 0) {
                                    $remarks = "STOPPAGE";
                                }
                             // Getting the PH.LOAN.TYPE
                               $loanTypeQry = TblLoanType::findFirst("loan_type = '$data3->lnType'");
                               $loanType = $loanTypeQry->product;
                            $startDate1 = $data3->startDate1 == null ? "" : date('Ymd', strtotime($data3->startDate1));

                        if (trim($data3->lnType) == "NL" || trim($data3->lnType) == "BNL" ) {
                              $temp_info = array(
                                   'up_company'            => 'PH0010002', //UPLOAD.COMPANY
                                   'BranchSvc'             => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                                   'payPeriod'             => $this->returnEmpty($payPayPeriod),
                                   'PIN'                   => $this->returnEmpty($data->PINAcctNo), //ATM.PIN
                                   'full_name'             => '',
                                   't24Mem'                => $this->returnEmpty($data->T24MemberNo),
                                   'LastName'              => $this->returnEmpty($data->LastName),
                                   'FirstName'             => $this->returnEmpty($data->FirstName),
                                   'MiddleName'            => $this->returnEmpty($data->MiddleName),
                                   'QualifNam'             => $this->returnEmpty($data->QualifNam), //PH.QUAL.NAME
                                   'SvcStat'               => $this->returnEmpty($data->SvcStat), //PH.SERVICE.STATUS
                                   'loanBillAmt'           => $data3->MOA1 == "" ? $this->returnEmpty($collectionPayAtm1->amount_collected) : $this->returnEmpty($data3->MOA1),
                                   'capconBillAmt'         => "", //PH.CAPCON.BILL.AMT
                                   'casa_bill_amount'      => "", //PH.CASA.BILL.AMT
                                   'psa_bill_amount'       => "", //PH.PSA.BILL.AMT
                                   'totalBillAmt'          => "",
                                   'BillAmt'               => $this->returnEmpty($data3->MOA1), //LOANBillAmount
                                   'lproNo'                => $this->returnEmpty($origId), //LproNo
                                   'lnType'                => $this->returnEmpty($loanType),//lnType
                                   'loanAppl'              => "",
                                   'maturity'              => $data3->maturity == "" ? "" : date("Ymd", strtotime($data3->maturity)), //maturity
                                   'dateGrant'             => $data3->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate))  : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                                   'updContDate'           => "",
                                   'loanTerm'              => "",
                                   'origContAmt'           => $dat3->loanAmt, //updContDate
                                   'loanStat'              => "",
                                   'startAmrtDate'         => $this->returnEmpty($startDate1), //PH.MOAMORT1
                                   'billTransType'         => "", //PH.BILL.TRANS.TYPE
                                   'bilTransStat'          => "", //PH.BIL.TRANS.STAT
                                   'billMode'              => $billMode,
                                   'origDedNCode'          => "",
                                   'updtDeDNCode'          => "",
                                   'origNri'               => "",
                                   'uptNri'                => "",
                                   'remarks'               => $this->returnEmpty($remarks),
                                   'isStoppage'            => $remarks == "STOPPAGE" ? "YES" : "NO",
                                   'atmCardNo'             => "",
                                   'fullName2'             => '',
                                   'pensAcctNo'            => "",
                                   'payMtStats'            => "",
                                   'stopDedNCode'          => "",
                                   'incld_bil_ind'         => "YES",
                                   '1stLoadAmt'            => $this->returnEmpty( $collectionPay),
                                   'amtCollect'            =>  $this->returnEmpty($collectionPay),
                                   'collection_pay_period' =>  $this->returnEmpty($collectPeriod),
                                );
                                if ($filter == 'FULLY PAID') {
                                    $filterD = ($data3->lproNo == null OR $data3->lproNo == '') && ($data3->PnPBillMod == 'PBM00' || $data3->PnPBillMod == 'PBM01' || $data3->PnPBillMod == 'PBM02');
                                    $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat =='RE');
                                    $filterB = "";
                                    if($filterD ){
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'NO ACCOUNT') {
                                    $filterD = ($data3->PnPBillMod == 'PBM03' || $data3->PnPBillMod == 'PBM04' || $data3->PnPBillMod == 'PBM05' || $data3->PnPBillMod == 'PBM06' || $data3->PnPBillMod == 'PBM07');
                                    $filterB = $data1->AccountName == null || $data1->AccountName == "";
                                    // $filterA = ($data->SvcStat == 'CO' || $data->SvcStat =='OP' || $data->SvcStat == 'RD' || $data->SvcStat == 'RE');
                                    // var_dump($data1->id);
                                    // exit;
                                    if($filterD  && $filterB){
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'OPTIONAL') {
                                    $filterD = ($data3->lnType != 'PL' && $data3->lnType != 'AP' && $data3->lnType != 'OP');
                                    $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat == 'RE');
                                    if($filterD && $filterA){
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'NORMAL') {
                                    $filterD = $data3->lproNo != null && ($data3->lnType == 'PL' || $data3->lnType == 'AP' || $data3->lnType == 'OP');
                                    $filterB = ($data1->AccountName != null || $data1->AccountName != '');
                                    $filterA = ($data->SvcStat != 'CO' && $data->SvcStat != 'OP' && $data->SvcStat != 'RD' && $data->SvcStat != 'RE');
                                    if($filterD && $filterA && $filterB){
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                    $info [] = $temp_info;
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                        }
                                $ctr++;
                                // echo print_r($temp_info);
                            // }

                        // }

                    }
                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
                    echo $fnlRes;
               }

            }
        exit;
    }
    public function exportBillingPsslaiAction(){
        try{
            $ctr =0;
            $date = date("YmdHis");
            $fp = fopen('/var/www/html/psslai/public/export/BILLING_PSSLAI_ao'.$date.'.csv', 'w');
            $path2 = "/var/www/html/psslai/public/export/fileLog_tempPSSLAI.log";
            $pathtxt = "/var/www/html/psslai/public/export/BILLING_PSSLAI_ao".$date.".txt";
            $txt = fopen($pathtxt, "w") or die("Unable to open file!");
            $exportCsv1 = 'export/BILLING_PSSLAI_ao'.$date.'.csv';
            $exportTxt2 = 'export/BILLING_PSSLAI_ao'.$date.'.txt';
            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $d = "TblLoanCsvFile";

                $getQry = TblMemberInfoFile::query()
                ->where("BranchSvc LIKE 'PSSLAI%'")
                ->limit($row,$offset)
                ->execute();

           $info = array();
            if($getQry){
                foreach($getQry as $data){
                    if($data){
                        $myTextFileHandler = fopen($path2,"r+");
                        $d = ftruncate($myTextFileHandler, 0);
                        fclose($myTextFileHandler);
                        $percent = $ctr /count($getQry) * 100;
                        echo "BILLING PSSLAI - Exporting file : ".number_format($percent,2)."%";
                        if ($filter != "" && $filterD != ""){
                            $data3 = TblLoanCsvFile::findFirst(array(
                                "conditions" => "(memberNo = '$data->SapMemberNo') AND $filterD",
                            ));
                        } else {
                            $data3 = TblLoanCsvFile::findFirst(array(
                                "conditions" => "memberNo = '$data->SapMemberNo'",
                            ));
                        }
                                $data1 = TblMemberAccountFile::findFirst(array(
                                    "conditions" => "MemberNo = '$data->SapMemberNo'",
                                ));
                                $data2 = TblAtmListFile::findFirst("CLIENT = '$data->SapMemberNo'");
                                $capconBillAmt = ""; $casaBillAmt = ""; $psaBillAmt = "";
                                $billRemarks = ""; $billMode = ""; $amount_collect = "";
                                $collection = TblCollectionPsslai::findFirst("member_no = '".$data->SapMemberNo."'");
                                 $loanBillAmt = $data3 == null ? $collection->amount : $data3->MOA1;
                                //Deduction Code
                                $group_service_status = $this->getGroupServiceStatus($data->SvcStat);
                                $branchSvc = trim($data->BranchSvc);
                                $deduction_code = $this->getDeductionCode($group_service_status,$data3->PnPBillMod,$data3->lnType,$branchSvc);
                                if($deduction_code == ""){
                                    $deduction = $collection->deduction_code;
                                } else {
                                    $deduction = $deduction_code;
                                }
                                $newBillMode = $this->getBillMode($group_service_status,$deduction,$branchSvc);
                                $billMode = $newBillMode["bill_mode"];
                                // Getting the PH.LOAN.TYPE
                                $loanTypeQry = TblLoanType::findFirst("loan_type = '$data3->lnType'");
                                $loanType = $loanTypeQry->product;
                                $payPayPeriod = $this->getPayPeriod($data3->id_loan_csv_name, trim($data->BranchSvc));
                                if($payPayPeriod != ""){
                                    $GLOBALS['tempPayPeriod'] = $payPayPeriod;
                               }
                               else{
                                   $payPayPeriod = $GLOBALS['tempPayPeriod'];
                               }
                               if(!$data3){
                                     $getLastData = TblLoanCsvFile::query()
                                    ->columns('initDate, id_loan_csv_name')
                                    ->limit(1)
                                    ->orderBy("id Desc")
                                    ->execute();

                                    $payPayPeriod = $this->getPayPeriod(trim($getLastData[0]->id_loan_csv_name), trim($branchSvc));
                                if($billMode == "Bill to CAPCON" || $billMode == "Bill to CASA" || $billMode == "Bill to Savings"){
                                    $origId = $data1->AccountName;
                                 } else {
                                    $origId = "";
                                 }
                                } else {
                                    $origId = str_replace("-", "", $data3->lproNo);
                                }
                                if($data3->PnPBillMod=="PBM00" || $data3->PnPBillMod=="PBM01" || $data3->PnPBillMod=="PBM02"){
                                    $loanBillAmt = $data3->MOA1;
                                    // $amount_collect = $data->loanAmt;
                                    $loanBillAmtNew = $data3->MOA1;
                                    if($collection->TblCollectionPpscID == null){
                                        $billRemarks = "NEW LOAN";
                                    }
                                } else if($data3->PnPBillMod=="PBM03"){
                                    // $amount_collect = $data->contribution_amount;
                                    $capconBillAmt = $data3->MOA1;
                                    $billRemarks = "CAPITAL.CONTRIBUTION";

                                } else if($data3->PnPBillMod == "PBM05"){
                                    // $amount_collect = $data->contribution_amount;
                                    $casaBillAmt = $data3->MOA1;
                                    $billRemarks = "SAVINGS.CONTRIBUTION";
                                }else if($data3->PnPBillMod=="PBM04"){
                                    // $amount_collect = $data->contribution_amount;
                                    $psaBillAmt = $data3->MOA1;
                                    $billRemarks = "CASA.CONTRIBUTION";
                                } else if($data3->PnPBillMod=="PBM00"){
                                    $loanBillAmtNew = $data3->MOA1;
                                    // $amount_collect = $data->loanAmt;
                                    if($collection->TblCollectionPpscID == null){
                                        $billRemarks = "NEW LOAN";
                                    }
                                } else if($data->PnPBillMod=="PBM07"){
                                    $amount_collect = "";
                                }
                                $totalBillAmt = str_replace(',','',number_format($loanBillAmt + $capconBillAmt + $casaBillAmt + $psaBillAmt,2));
                                $totalBillAmt = $totalBillAmt == 0 ? "" : $totalBillAmt;
                                $data4 = TblLoanAtmFile::findFirst("memberNo = '$data->SapMemberNo'");
                                $get1stdate = TblLoanCsvFile::query()
                                ->columns('initDate')
                                ->limit(1)
                                ->orderBy("id Desc")
                                ->execute();

                                $startDate = $data3->startDate1 == null ? "" : date('Ymd', strtotime($data3->startDate1));
                                $maturityDate = $data3->maturity == null ? "" : date('Ymd', strtotime($data3->maturity));
                            if(trim($data3->lnType) != "BL" && trim($data3->lnType) != "NL") {
                                $temp_info = array(
                                    'up_company'        => 'PH0010002', //UPLOAD.COMPANY
                                    'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                                    'payPeriod'  => $this->returnEmpty($payPayPeriod),
                                    'PIN'               => "", //ATM.PIN
                                    'full_name'         => "",
                                    'customerCode'      => $this->returnEmpty($data->T24MemberNo),
                                    'LastName'          => trim($data->LastName),
                                    'FirstName'         => trim($data->FirstName),
                                    'MiddleName'        => trim($data->MiddleName),
                                    'QualifNam'         => $this->returnEmpty(trim($data->QualifNam)), //PH.QUAL.NAME
                                    'SvcStat'           => $this->returnEmpty ($data->SvcStat), //PH.SERVICE.STATUS
                                    'loanBillAmt'       => $this->returnEmpty($loanBillAmt),
                                    'capconBillAmt'     => $this->returnEmpty($capconBillAmt), //PH.CAPCON.BILL.AMT
                                    'casaBillAmount'    => $this->returnEmpty($casaBillAmt), //PH.CASA.BILL.AMT
                                    'psaBillAmount'     => $this->returnEmpty($psaBillAmt), //PH.PSA.BILL.AMT
                                    'totalBillAmt'      => $this->returnEmpty($totalBillAmt),
                                    'BillAmt'           => "",
                                    'lproNo'            => $this->returnEmpty($origId), //LproNo
                                    'lnType'            => $this->returnEmpty($loanType), //lnType
                                    'loanAppl'          => "",
                                    'maturity'          => $this->returnEmpty($maturityDate), //maturity
                                    'dateGrant'         => $data3->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate)) : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                                    'updContDate'       => "", //updContDate
                                    'loanTerm'          => $this->returnEmpty($data3->lnpTrate), //updContDate
                                    'loanAmount'        => $this->returnEmpty($data3->loanAmt),
                                    'loanStat'          => $this->returnEmpty($data3->loanProc), //updContDate
                                    'startAmrtDate'     => $this->returnEmpty($startDate),
                                    'billTransType'     => "", //PH.BILL.TRANS.TYPE
                                    'bilTransStat'      => "", //PH.BIL.TRANS.STAT
                                    'billMode'          => $billMode,
                                    'origDedNCode'      => $this->returnEmpty($deduction),
                                    'updtDeDNCode'      => "",
                                    'origNri'           => "",
                                    'uptNri'            => "",
                                    'billRemarks'       => $this->returnEmpty($billRemarks),
                                    'isStoppage'        => $billRemarks == "STOPPAGE" ? "YES" : "NO",
                                    'AtmCardNo'         => "", //ATM.CARD.NO.1
                                    'fullName2'         => "",
                                    'pensAcctNo'        => "",
                                    'payMtStats'        => "",
                                    'stopDedNCode'      => "",
                                    'incldBilInd'     => "YES",
                                    '1stLoadAmt'        => "",
                                    'amtCollect'        => $this->returnEmpty($collection->amount),
                                    'collectionPayPeriod' => $this->returnEmpty($collection->collection_pay_period)
                                );
                                if ($filter == 'FULLY PAID') {
                                    $filterD = ($data3->lproNo == null OR $data3->lproNo == '') && ($data3->PnPBillMod == 'PBM00' || $data3->PnPBillMod == 'PBM01' || $data3->PnPBillMod == 'PBM02');
                                    $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat =='RE');
                                    $filterB = "";
                                    if($filterD ){
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'NO ACCOUNT') {
                                    $filterD = ($data3->PnPBillMod == 'PBM03' || $data3->PnPBillMod == 'PBM04' || $data3->PnPBillMod == 'PBM05' || $data3->PnPBillMod == 'PBM06' || $data3->PnPBillMod == 'PBM07');
                                    $filterB = $data1->AccountName == null || $data1->AccountName == "";
                                    if($filterD  && $filterB){
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'OPTIONAL') {
                                    $filterD = ($data3->lnType != 'PL' && $data3->lnType != 'AP' && $data3->lnType != 'OP');
                                    $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat == 'RE');
                                    if($filterD && $filterA){
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if ($filter == 'NORMAL') {
                                    $filterD = $data3->lproNo != null && ($data3->lnType == 'PL' || $data3->lnType == 'AP' || $data3->lnType == 'OP');
                                    $filterB = ($data1->AccountName != null || $data1->AccountName != '');
                                    $filterA = ($data->SvcStat != 'CO' && $data->SvcStat != 'OP' && $data->SvcStat != 'RD' && $data->SvcStat != 'RE');
                                    if($filterD && $filterA && $filterB){
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                            }
                            // }

                        // }

                    }
               }
            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
            echo $fnlRes;
            }
        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }
    // public function exportBillingPsslaiAction(){
    //     $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
    //     ini_set('memory_limit', '-1');
    //     $date = date("YmdHis");
    //     $a = "TblMemberInfoFile";
    //     $b = "TblMemberAccountFile";
    //     $d = "TblLoanCsvFile";
    //     $filterA = "";
    //     $filterB = "";
    //     $filterD = "";
    //     if ($filter == 'FULLY PAID') {
    //         $filterD = "(lproNo IS null OR lproNo = '') AND (PnPBillMod = 'PBM00' OR PnPBillMod = 'PBM01' OR PnPBillMod = 'PBM02')";
    //         $ext = "_Fully Paid";
    //     } else if ($filter == 'NO ACCOUNT') {
    //         $filterD = "(PnPBillMod = 'PBM03' OR PnPBillMod = 'PBM04' OR PnPBillMod = 'PBM05' OR PnPBillMod = 'PBM06' OR PnPBillMod = 'PBM07')";
    //         $filterB = "AccountName IS null OR AccountName = ''";
    //         $ext = "_No Account";
    //     } else if ($filter == 'OPTIONAL') {
    //         $filterD = "(lnType != 'PL' AND lnType != 'AP' AND lnType != 'OP')";
    //         $filterA = "(SvcStat = 'CO' OR SvcStat = 'OP' OR SvcStat = 'RD' OR SvcStat = 'RE')";
    //         $ext = "_Optional";
    //     } else if ($filter == 'NORMAL') {
    //         $filterD = "lproNo IS NOT null AND (lnType = 'PL' OR lnType = 'AP' OR lnType = 'OP')";
    //         $filterB = "AccountName IS NOT null OR AccountName OR AccountName != ''";
    //         $ext = "_Normal";
    //     }
    //     if ($filter != "" && $filterA != "" ){
    //         $getQry = TblMemberInfoFile::query()
    //         ->where("BranchSvc LIKE 'PSSLAI%' AND $filterA")
    //         ->execute();
    //     } else {
    //         $getQry = TblMemberInfoFile::query()
    //         ->where("BranchSvc LIKE 'PSSLAI%'")
    //         ->execute();
    //     }
    //    $ctr =0;
    //    $date = date("YmdHis");
    //    $fp = fopen('/var/www/html/psslai/public/export/BILLING_PSSLAI_ao'.$date.'.csv', 'w');
    //    $path2 = "/var/www/html/psslai/public/export/fileLog_tempPSSLAI.log";
    //    $pathtxt = "/var/www/html/psslai/public/export/BILLING_PSSLAI_ao".$date.".txt";
    //    $txt = fopen($pathtxt, "w") or die("Unable to open file!");
    //    $exportCsv1 = 'export/BILLING_PSSLAI_ao'.$date.'.csv';
    //    $exportTxt2 = 'export/BILLING_PSSLAI_ao'.$date.'.txt';
    //    $info = array();
    //    $info1 = "";
    //    $info2 = "";
    //    $info3 = "";
    //    $info4 = "";
    //    $data4 = null;
    //    $totalBillAmt = 0;
    //    $casaAmt = 0;
    //    $psaAmt = 0;
    //    $capconAmt = 0;
    //    $deduction_code = 0;
    //    $loanAmt = 0;
    //    $headerCSV = ['UPLOAD.COMPANY',
    //             'PH.BRANCH.SVC',
    //             'PH.PAY PERIOD',
    //             'PH.PIN NO',
    //             'PH.FULLNAME 1',
    //             'CUSTOMER CODE',
    //             'PH.LAST.NAME',
    //             'PH.FIRST.NAME',
    //             'PH.MIDDLE.NAME',
    //             'PH.QUAL.NAME',
    //             'PH.SERVICE STATUS',

    //             'PH.LOAN.BILL.AMT',

    //             'PH.CAPCON.BILL.AMT',
    //             'PH.CASA.BILL.AMT',
    //             'PH.PSA.BILL.AMT',

    //             'PH.TOTAL.BILL.AMT',
    //             'PH.BILL.AMT',
    //             'PH.LOAN.ORIG.ID',
    //             'PH.LOAN.TYPE',
    //             'PH.APP.TYPE',
    //             'PH.MATURITY.DATE',
    //             'PH.ORIG.CONT.DATE',
    //             'PH.UPD.CONT.DATE',
    //             'PH.LOAN.TERM',
    //             'PH.ORIG.CONT.AMT',
    //             'PH.LOAN.STAT',
    //             'PH.START.AMRT.DATE',
    //             'PH.BILL.TRANS.TYPE',
    //             'PH.BILL.TRANS.STAT',
    //             'PH.BILL.MODE',
    //             'PH.ORIG.DEDNCODE',
    //             'PH.UPD.DEDNCODE',
    //             'PH.ORIG.NRI',
    //             'PH.UPDATED.NRI',
    //             'PH.BILL.REMARKS',
    //             'PH.BILL.STOPPAGE',
    //             'PH.ATM.CARD.NO',
    //             'PH.FULLNAME2',
    //             'PH.PENS.ACCT.STAT',
    //             'PH.PAYMT.STAT',
    //             'PH.STOP.DEDN.CODE',
    //             'PH.INCLD.BIL.IND',
    //             'PH.1ST.LOAD.AMT',
    //             'AMOUNT.COLLECT',
    //             'PAY.PERIOD.COLLECT'];
    //             $removeText = [
    //                 'up_company:',
    //                 'BranchSvc:',
    //                 'payPeriod:',
    //                 'PIN:',
    //                 'full_name:',
    //                 'customerCode:',
    //                 'LastName:',
    //                 'FirstName:',
    //                 'MiddleName:',
    //                 'QualifNam:',
    //                 'SvcStat:',
    //                 'loanBillAmt:',
    //                 'capconBillAmt:',
    //                 'casaBillAmount:',
    //                 'psaBillAmount:',
    //                 'totalBillAmt:',
    //                 'BillAmt:',
    //                 'loanOrigId:',
    //                 'loanType:',
    //                 'appType:',
    //                 'maturityDate:',
    //                 'origContDate:',
    //                 'updContDate:',
    //                 'loanTerm:',
    //                 'origContAmt:',
    //                 'loanStat:',
    //                 'startAmrtDate:',
    //                 "transType:",
    //                 "transStat:",
    //                 'billMode:',
    //                 'origDedNCode:',
    //                 "updateDedNcode:",
    //                 'origNri:',
    //                 'updateNri:',
    //                 'billRemarks:',
    //                 'isStoppage:',
    //                 "atmCardNo:",
    //                 "fullName2:",
    //                 "accntNo:",
    //                 "paymStat:",
    //                 "stopDedNcode:",
    //                 'incldBilInd:',
    //                 '1stloadAmt:',
    //                 'amtCollect:',
    //                 'collectionPayPeriod:',
    //             ];
    //     fputcsv($fp, $headerCSV );
    //     if($getQry){
    //         foreach($getQry as $data){
    //             if($data){
    //                 if ($filterD != ""){
    //                     $data3 = TblLoanCsvFile::findFirst(array(
    //                         "conditions" => "(memberNo = '$data->SapMemberNo') AND $filterD",
    //                     ));
    //                 } else {
    //                     $data3 = TblLoanCsvFile::findFirst(array(
    //                         "conditions" => "memberNo = '$data->SapMemberNo'",
    //                     ));
    //                 }
    //                 if($data3){
    //                     if ($filterB != ""){
    //                         $data1 = TblMemberAccountFile::findFirst(array(
    //                             "conditions" => "(MemberNo = '$data->SapMemberNo') AND $filterB",
    //                         ));
    //                     } else {
    //                         $data1 = TblMemberAccountFile::findFirst(array(
    //                             "conditions" => "MemberNo = '$data->SapMemberNo'",
    //                         ));
    //                     }
    //                     if($data1){
    //                               //create a file handler by opening the file
    //                               $myTextFileHandler = fopen($path2,"r+");
    //                               $d = ftruncate($myTextFileHandler, 0);
    //                               fclose($myTextFileHandler);
    //                               $percent = $ctr /count($getQry) * 100;
    //                               echo "BILLING PSSLAI - Exporting file : ".number_format($percent,2)."%";
    //                               $data2 = TblAtmListFile::findFirst("CLIENT = '$data->SapMemberNo'");
    //                               // $data3 = TblLoanCsvFile::findFirst("$data->SapMemberNo");
    //                               $collection = TblCollectionPsslai::findFirst("member_no = '".$data->SapMemberNo."'");
    //                               $loanBillAmt = ""; $capconBillAmt = ""; $casaBillAmt = ""; $psaBillAmt = "";
    //                               $billRemarks = ""; $billMode = ""; $amount_collect = "0.00";
    //                               if(($data3 == null ? "" : $data3->PnPBillMod =="PBM01") ||( $data3 == null ? "" : $data3->PnPBillMod) == "PBM02"){
    //                               $loanBillAmt = $data3 == null ? "" : $data3->MOA1;
    //                               $billMode = "Bill to Loan";
    //                               $amount_collect = $data3 == null ? "" : $data3->loanAmt;
    //                               $loanBillAmtNew = $data3 == null ? "" :$data3->MOA1;
    //                               $billRemarks = "NEW LOAN";

    //                               } else if(($data3 == null ? "" :$data3->PnPBillMod) =="PBM03"){
    //                                   $billMode = "Bill to CAPCON";
    //                               // $amount_collect = $data->contribution_amount;
    //                                   $capconAmt = $data3 == null ? "" :$data3->MOA1;
    //                                   $billRemarks = "CAPITAL.CONTRIBUTION";

    //                               } else if(($data3 == null ? "" :$data3->PnPBillMod) =="PBM04"){
    //                                   $billMode = "Bill to Savings";
    //                                   $casaAmt =  $data3 == null ? "" :$data3->MOA1;
    //                                   $billRemarks = "SAVINGS.CONTRIBUTION";
    //                               }else if(($data3 == null ? "" : $data3->PnPBillMod) =="PBM05"){
    //                                   //$amount_collect = $data->contribution_amount;
    //                                   $billMode = "Bill to CASA";
    //                                   $psaAmt = $data->MOA1;
    //                                   //$psaBillAmt = $data->contribution_amount;
    //                                   $billRemarks = "CASA.CONTRIBUTION";
    //                               } else if(($data3 == null ? "" : $data3->PnPBillMod) =="PBM00"){
    //                                   $loanBillAmtNew = $data3 == null ? "" : $dat3->MOA1;
    //                                   $billMode = "Bill to Loan";
    //                               // $amount_collect = $data->loanAmt;
    //                                   if($data->TblCollectionPpscID == null){
    //                                       $billRemarks = "NEW LOAN";
    //                                       // $psaAmt =$data3 == null ? "" : $dat3->MOA1;
    //                                   }
    //                               }
    //                               else{
    //                                   $billMode = "Bill to POS";
    //                               }
    //                           $totalBillAmt = str_replace(',','',number_format($psaAmt + $casaAmt + $capconAmt + $loanAmt,2));
    //                           $totalBillAmt = $totalBillAmt == 0 ? "" : $totalBillAmt;
    //                           if($totalBillAmt<=0){
    //                               $billRemarks = "STOPPAGE";
    //                           }
    //                            // Getting the PH.LOAN.TYPE
    //                             $loanTypeQry = TblLoanType::findFirst("loan_type = '$data3->lnType'");
    //                             $loanType = $loanTypeQry->product;
    //                           $payPayPeriod = $this->getPayPeriod($data3->id_loan_csv_name, trim($data->BranchSvc));
    //                           $temp_info = array(
    //                               'up_company'        => 'PH0010002', //UPLOAD.COMPANY
    //                               'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
    //                               'payPeriod'         => $this->returnEmpty($payPayPeriod),
    //                               'PIN'               => "", //ATM.PIN
    //                               'full_name'         => "",
    //                               'customerCode'      => $this->returnEmpty($data->T24MemberNo),
    //                               'LastName'          => $this->returnEmpty($data->LastName),
    //                               'FirstName'         => $this->returnEmpty($data->FirstName),
    //                               'MiddleName'        => $this->returnEmpty($data->MiddleName),
    //                               'QualifNam'         => $this->returnEmpty($data->QualifNam), //PH.QUAL.NAME
    //                               'SvcStat'           => $this->returnEmpty($data->SvcStat), //PH.SERVICE.STATUS
    //                               'loanBillAmt'       => $this->returnEmpty($loanBillAmtNew),
    //                               'capconBillAmt'     => $capconAmt  == '0' ? "" : $capconAmt, //PH.CAPCON.BILL.AMT
    //                               'casaBillAmount'    => $casaAmt == '0' ? "" : $casaAmt, //PH.CASA.BILL.AMT
    //                               'psaBillAmount'     => $psaAmt  == '0' ? "" : $psaAmt, //PH.PSA.BILL.AMT
    //                               'totalBillAmt'      => $this->returnEmpty($totalBillAmt),
    //                               'BillAmt'           => $this->returnEmpty($loanBillAmtNew), //LOANBillAmount
    //                               'loanOrigId'        => $this->returnEmpty($data3->lproNo), //LproNo
    //                               'loanType'          =>  $this->returnEmpty($loanType),
    //                               'appType'           => "",
    //                               'maturityDate'      => date('Ymd', strtotime($dat3->maturity)), //maturity
    //                               'origContDate'      => date('Ymd', strtotime($data3->dateGrant)), //dateGrant
    //                               'updContDate'       => "", //updContDate
    //                               'loanTerm'          => $this->returnEmpty($data3->lnpTrate), //updContDate
    //                               'origContAmt'       => $this->returnEmpty($data3->loanAmt),
    //                               'loanStat'          => $this->returnEmpty($data3->loanProc),
    //                               'startAmrtDate'     => date('Ymd', strtotime($data3->startDate1)), //updContDate
    //                               "transType"         => "",
    //                               "transStat"         => "",
    //                               'billMode'          => $this->returnEmpty($billMode),
    //                               'origDedNCode'      => $this->returnEmpty($deduction_code),
    //                               "updateDedNcode"    => "",
    //                               'origNri'           => "",
    //                               'updateNri'         => "",
    //                               'billRemarks'       => $this->returnEmpty($billRemarks),
    //                               'isStoppage'        => $billRemarks == "STOPPAGE" ? "YES" : "NO",
    //                               "atmCardNo"         => "",
    //                               "fullName2"         => "",
    //                               "accntNo"           => "",
    //                               "paymStat"          => "",
    //                               "stopDedNcode"      => "",
    //                               'incldBilInd'       => "YES",
    //                               '1stloadAmt'        => '',
    //                               'amtCollect'        => $this->returnEmpty($collection->amount),
    //                               'collectionPayPeriod' => $this->returnEmpty($collection->collection_pay_period)
    //                           );
    //                           array_push($info,$temp_info);
    //                           $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
    //                           fwrite($txt,str_replace($removeText,"",$finalOutput). "\r\n");
    //                           fputcsv($fp, $temp_info );
    //                           $ctr++;
    //                     }

    //                 }

    //             }

    //        }
    //     }

    //    $myTextFileHandler = fopen($path2,"r+");
    //     $d = ftruncate($myTextFileHandler, 0);
    //     fclose($myTextFileHandler);
    //     $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
    //     echo $fnlRes;
    //     exit;
    // }
    public function exportBillingPpscAction(){
        try{
             $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
            ini_set('memory_limit', '-1');
            $ctr = 0;
            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $d = "TblLoanCsvFile";
            $date = date("YmdHis");
            $fp = fopen('/var/www/html/psslai/public/export/BILLING_PPSC_ao'.$date.'.csv', 'w');
            $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
            $pathtxt = "/var/www/html/psslai/public/export/BILLING_PPSC_ao".$date.".txt";
            $txt = fopen($pathtxt, "w") or die("Unable to open file!");
            $exportCsv1 = 'export/BILLING_PPSC_ao'.$date.'.csv';
            $exportTxt2 = 'export/BILLING_PPSC_ao'.$date.'.txt';
            $headerCSV = ['UPLOAD.COMPANY',
        'PH.BRANCH.SVC',
        'PH.PAY.PERIOD',
        'PH.PIN.NO',
        'PH.FULLNAME1',
        'CUSTOMER.CODE',
        'PH.LAST.NAME',
        'PH.FIRST.NAME',
        'PH.MIDDLE.NAME',
        'PH.QUAL.NAME',
        'PH.SERVICE.STATUS',
        'PH.LOAN.BILL.AMT',
        'PH.CAPCON.BILL.AMT',
        'PH.CASA.BILL.AMT',
        'PH.PSA.BILL.AMT',
        'PH.TOTAL.BILL.AMT',
        'PH.BILL.AMT',
        'PH.LOAN.ORIG.ID',
        'PH.LOAN.TYPE',
        'PH.APP.TYPE',
        'PH.MATURITY.DATE',
        'PH.ORIG.CONT.DATE',
        'PH.UPD.CONT.DATE',
        'PH.LOAN.TERM',
        'PH.ORIG.CONT.AMT',
        'PH.LOAN.STAT',
        'PH.START.AMRT.DATE',
        'PH.BILL.TRANS.TYPE',
        'PH.BIL.TRANS.STAT',
        'PH.BILL.MODE',
        'PH.ORIG.DEDNCODE',
        'PH.UPD.DEDNCODE',
        'PH.ORIG.NRI',
        'PH.UPDATED.NRI',
        'PH.BILL.REMARKS',
        'PH.BILL.STOPPAGE',
        'PH.ATM.CARD.NO',
        'PH.FULLNAME2',
        'PH.PENS.ACCT.NO',
        'PH.PAYMT.STAT',
        'PH.STOP.DEDN.CODE',
        'PH.INCLD.BIL.IND',
        'PH.1ST.LOAD.AMT',
        'AMOUNT.COLLECT',
        'PAY.PERIOD.COLLECT'
       ];
            $getQry = TblMemberInfoFile::query()
            ->where("BranchSvc = 'PPSC'")
            ->limit($row,$offset)
            ->execute();

           $info = array();
            if($getQry){
                fputcsv($fp, $headerCSV );
                foreach($getQry as $data){
                    if($data){
                        $myfile = fopen($path2, "w") or die("Unable to open file!");
                        fwrite($myfile,  $test);
                        $percent = $ctr /count($getQry) * 100;
                        // echo "BILLING PPSC - Exporting file : ".number_format($percent,2)."%";
                        $test =  "BILLING PPSC - Exporting file : ".number_format($percent,2)."%";
                            $data3 = TblLoanCsvFile::findFirst(array(
                                "conditions" => "memberNo = '$data->SapMemberNo'",
                            ));

                                $data1 = TblMemberAccountFile::findFirst(array(
                                    "conditions" => "MemberNo = '$data->SapMemberNo'",
                                ));

                                $data2 = TblAtmListFile::findFirst("CLIENT = '$data->SapMemberNo'");
                                $collection = TblCollectionPpsc::findFirst("member_no = '".$data->SapMemberNo."'");
                                $loanBillAmt = ""; $capconBillAmt = ""; $casaBillAmt = ""; $psaBillAmt = "";
                                $billRemarks = ""; $billMode = ""; $amount_collect = "";
                                if($data3->PnPBillMod=="PBM00" || $data3->PnPBillMod=="PBM01" || $data3->PnPBillMod=="PBM02"){
                                    $loanBillAmt = $data3->MOA1;
                                    // $billMode = "Bill to Loan";
                                    // $amount_collect = $data->loanAmt;
                                    $loanBillAmtNew = $data3->MOA1;
                                    if($data->TblCollectionPpscID == null){
                                        $billRemarks = "NEW LOAN";
                                    }
                                } else if($data3->PnPBillMod=="PBM03"){
                                    // $billMode = "Bill to CAPCON";
                                    // $amount_collect = $data->contribution_amount;
                                    $capconBillAmt = $data3->MOA1;
                                    $billRemarks = "CAPITAL.CONTRIBUTION";

                                } else if($data3->PnPBillMod == "PBM05"){
                                    // $amount_collect = $data->contribution_amount;
                                    // $billMode = "Bill to Savings";
                                    $casaBillAmt = $data3->MOA1;
                                    $billRemarks = "SAVINGS.CONTRIBUTION";
                                }
                                else if($data3->PnPBillMod=="PBM04"){
                                    // $amount_collect = $data->contribution_amount;
                                    // $billMode = "Bill to CASA";
                                    $psaBillAmt = $data3->MOA1;
                                    $billRemarks = "CASA.CONTRIBUTION";
                                }
                                else if($data3->PnPBillMod=="PBM00"){
                                    $loanBillAmtNew = $data3->MOA1;
                                    // $billMode = "Bill to Loan";
                                    // $amount_collect = $data->loanAmt;
                                    if($data3->TblCollectionPpscID == null){
                                        $billRemarks = "NEW LOAN";
                                    }
                                } else if($data3->PnPBillMod=="PBM07"){
                                    $amount_collect = "";
                                }
                                else{
                                    // $billMode = "";
                                }
                                $totalBillAmt = str_replace(',','',number_format($loanBillAmt + $capconBillAmt + $casaBillAmt + $psaBillAmt,2));
                                $totalBillAmt = $totalBillAmt == 0 ? "" : $totalBillAmt;
                                //Deduction Code
                                $group_service_status = $this->getGroupServiceStatus($data->SvcStat);
                                $branchSvc = trim($data->BranchSvc);
                                $deduction_code = $this->getDeductionCode($group_service_status,$data3->PnPBillMod,$data3->lnType,$branchSvc);
                                $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                                $billMode = $newBillMode["bill_mode"];
                                // Getting the PH.LOAN.TYPE
                                $loanTypeQry = TblLoanType::findFirst("loan_type = '$data3->lnType'");
                                $loanType = $loanTypeQry->product;

                                $get1stdate = TblLoanCsvFile::query()
                                ->columns('initDate, id_loan_csv_name')
                                ->limit(1)
                                ->orderBy("id Desc")
                                ->execute();
                                $payPayPeriod = $this->getPayPeriod($data3->id_loan_csv_name, trim($data->BranchSvc));
                                if(!$payPayPeriod){
                                    $payPayPeriod = $this->getPayPeriod(trim($get1stdate[0]->id_loan_csv_name), trim($data->BranchSvc));
                                }

                               $origId = str_replace("-", "", $data3->lproNo);

                              $startDate1 = $data3->startDate1 == null ? "" : date('Ymd', strtotime($data3->startDate1));
                                if((trim($data3->lnType) != "BL") && (trim($data3->lnType) != "NL")) {
                                    $temp_info = array(
                                        'up_company'        => 'PH0010002', //UPLOAD.COMPANY
                                        'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                                        'collectionPeriod'  => $this->returnEmpty($payPayPeriod),
                                        'PIN'               => "", //ATM.PIN
                                        'full_name'         => "",
                                        'customerCode'      => $this->returnEmpty($data->T24MemberNo),
                                        'LastName'          => trim($data->LastName),
                                        'FirstName'         => trim($data->FirstName),
                                        'MiddleName'        => trim($data->MiddleName),
                                        'QualifNam'         => $this->returnEmpty(trim($data->QualifNam)), //PH.QUAL.NAME
                                        'SvcStat'           => $this->returnEmpty ($data->SvcStat), //PH.SERVICE.STATUS
                                        'loanBillAmt'       => trim($loanBillAmt) == "" ? $this->returnEmpty($collection->loan_amount) : $this->returnEmpty($loanBillAmt),
                                        'capconBillAmt'     => $this->returnEmpty($capconBillAmt), //PH.CAPCON.BILL.AMT
                                        'casaBillAmount'    => $this->returnEmpty($casaBillAmt), //PH.CASA.BILL.AMT
                                        'psaBillAmount'     => $this->returnEmpty($psaBillAmt), //PH.PSA.BILL.AMT
                                        'totalBillAmt'      => $this->returnEmpty($totalBillAmt),
                                        'BillAmt'           => $this->returnEmpty($casaBillAmt), //LOANBillAmount
                                        'lproNo'            => $this->returnEmpty($origId), //LproNo
                                        'lnType'            => $this->returnEmpty($loanType), //lnType
                                        'loanAppl'          => "",
                                        'maturity'          => "", //maturity
                                        'dateGrant'         => $data3->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate))  : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                                        'updContDate'       => "", //updContDate
                                        'loanTerm'          => $this->returnEmpty($data3->lnpTrate), //updContDate
                                        'loanAmount'        => $this->returnEmpty($data3->loanAmt),
                                        'loanStat'          => "", //updContDate
                                        'startAmrtDate'     => $this->returnEmpty($startDate1),
                                        'billTransType'     => "", //PH.BILL.TRANS.TYPE
                                        'bilTransStat'      => "", //PH.BIL.TRANS.STAT
                                        'billMode'          => $billMode,
                                        'OrigDedNCode'      => $this->returnEmpty($deduction_code),
                                        'updtDeDNCode'      => "",
                                        'origNri'           => "",
                                        'uptNri'            => "",
                                        'billRemarks'       => $this->returnEmpty($billRemarks),
                                        'isStoppage'        => $billRemarks == "STOPPAGE" ? "YES" : "NO",
                                        'AtmCardNo'         => "", //ATM.CARD.NO.1
                                        'fullName2'         => "",
                                        'pensAcctNo'        => "",
                                        'payMtStats'        => "",
                                        'stopDedNCode'      => "",
                                        'incld_bil_ind'     => "YES",
                                        '1stLoadAmt'        => "",
                                        'amtCollect'        => $this->returnEmpty($amount_collect),
                                        'payPeriodCollect'  => $this->returnEmpty($collection->collection_pay_period)
                                    );
                                    if ($filter == 'FULLY PAID') {
                                        $filterD = ($data3->lproNo == null OR $data3->lproNo == '') && ($data3->PnPBillMod == 'PBM00' || $data3->PnPBillMod == 'PBM01' || $data3->PnPBillMod == 'PBM02');
                                        $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat =='RE');
                                        $filterB = "";
                                        if($filterD){
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                            fputcsv($fp, $temp_info );
                                        }
                                    } else if ($filter == 'NO ACCOUNT') {
                                        $filterD = ($data3->PnPBillMod == 'PBM03' || $data3->PnPBillMod == 'PBM04' || $data3->PnPBillMod == 'PBM05' || $data3->PnPBillMod == 'PBM06' || $data3->PnPBillMod == 'PBM07');
                                        $filterB = $data1->AccountName == null || $data1->AccountName == "";
                                        if($filterD  && $filterB){
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                            fputcsv($fp, $temp_info );
                                        }
                                    } else if ($filter == 'OPTIONAL') {
                                        $filterD = ($data3->lnType != 'PL' && $data3->lnType != 'AP' && $data3->lnType != 'OP');
                                        $filterA = ($data->SvcStat == 'CO' || $data->SvcStat == 'OP' || $data->SvcStat == 'RD' || $data->SvcStat == 'RE');
                                        if($filterD && $filterA){
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                            fputcsv($fp, $temp_info );
                                        }
                                    } else if ($filter == 'NORMAL') {
                                        $filterD = $data3->lproNo != null && ($data3->lnType == 'PL' || $data3->lnType == 'AP' || $data3->lnType == 'OP');
                                        $filterB = ($data1->AccountName != null || $data1->AccountName != '');
                                        $filterA = ($data->SvcStat != 'CO' && $data->SvcStat != 'OP' && $data->SvcStat != 'RD' && $data->SvcStat != 'RE');
                                        if($filterD && $filterA && $filterB){
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                            fputcsv($fp, $temp_info );
                                        }
                                    } else {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                        fputcsv($fp, $temp_info );
                                    }
                                }
                                    $ctr++;
                    }
               }
                $myTextFileHandler = fopen($path2,"r+");
                $d = ftruncate($myTextFileHandler, 0);
                fclose($myTextFileHandler);
                $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
                echo $fnlRes;
            }
        }
        catch(Exception $e){
            $this->respond(array(
                'statusCode'    => 500,
                'devMessage'    => $e->getMessage()
            ));
        }
    }

    // public function exportBillingPpscAction(){
    //     try{
    //         $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
    //         ini_set('memory_limit', '-1');
    //         $ctr = 0;
    //         $a = "TblMemberInfoFile";
    //         $b = "TblMemberAccountFile";
    //         $d = "TblLoanCsvFile";
    //         $date = date("YmdHis");
    //         $fp = fopen('/var/www/html/psslai/public/export/BILLING_PPSC_ao'.$date.'.csv', 'w');
    //         $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
    //         $pathtxt = "/var/www/html/psslai/public/export/BILLING_PPSC_ao".$date.".txt";
    //         $txt = fopen($pathtxt, "w") or die("Unable to open file!");
    //         $exportCsv1 = 'export/BILLING_PPSC_ao'.$date.'.csv';
    //         $exportTxt2 = 'export/BILLING_PPSC_ao'.$date.'.txt';
    //         $a = "TblMemberInfoFile";
    //         $b = "TblMemberAccountFile";
    //         $d = "TblLoanCsvFile";
    //         // if ($filter == "NORMAL" || $filter == "OPTIONAL"  && $filterA != "" ){
    //         //     $getQry = TblMemberInfoFile::query()
    //         //     ->where("BranchSvc = 'PPSC' AND $filterA")
    //         //     ->limit($row,$offset)
    //         //     ->execute();
    //         // } else {
    //             $getQry = TblMemberInfoFile::query()
    //             ->where("BranchSvc = 'PPSC'")
    //             ->limit($row,$offset)
    //             ->execute();
    //         // }
    //         $info = array();
    //         fputcsv($fp, $headerCSV );
    //         if($getQry){
    //             foreach($getQry as $data){
    //                 if($data){
    //                     $myfile = fopen($path2, "w") or die("Unable to open file!");
    //                     fwrite($myfile,  $test);
    //                     $percent = $ctr /count($getQry) * 100;
    //                     // echo "BILLING PPSC - Exporting file : ".number_format($percent,2)."%";
    //                     $test =  "BILLING PPSC - Exporting file : ".number_format($percent,2)."%";
    //                     // if ($filter != "" && $filterD != ""){
    //                     //     $data3 = TblLoanCsvFile::findFirst(array(
    //                     //         "conditions" => "(memberNo = '$data->SapMemberNo') AND $filterD",
    //                     //     ));
    //                     // } else {
    //                         $data3 = TblLoanCsvFile::findFirst(array(
    //                             "conditions" => "memberNo = '$data->SapMemberNo'",
    //                         ));
    //                     // }
    //                     // if ($filter != "" && $filterB != ""){
    //                     //     $data1 = TblMemberAccountFile::findFirst(array(
    //                     //         "conditions" => "(MemberNo = '$data->SapMemberNo') AND $filterB",
    //                     //     ));
    //                     // } else {
    //                         $data1 = TblMemberAccountFile::findFirst(array(
    //                             "conditions" => "MemberNo = '$data->SapMemberNo'",
    //                         ));
    //                     // }
    //                     $data2 = TblAtmListFile::findFirst("CLIENT = '$data->SapMemberNo'");
    //                     // if(!$data3){
    //                     //     if($billMode = "Bill to CAPCON" || $billMode = "Bill to CASA" || $billMode = "Bill to Savings"){
    //                     //         $origId = $data1->AccountName;
    //                     //     }
    //                     // } else {
    //                         // $origId = ;
    //                     // }

    //                     $loanBillAmt = ""; $capconBillAmt = ""; $casaBillAmt = ""; $psaBillAmt = "";
    //                     $billRemarks = ""; $billMode = ""; $amount_collect = "";
    //                     if($data3->PnPBillMod=="PBM00" || $data3->PnPBillMod=="PBM01" || $data3->PnPBillMod=="PBM02"){
    //                         $loanBillAmt = $data3->MOA1;
    //                         // $billMode = "Bill to Loan";
    //                         // $amount_collect = $data->loanAmt;
    //                         $loanBillAmtNew = $data3->MOA1;
    //                         if($data->TblCollectionPpscID == null){
    //                             $billRemarks = "NEW LOAN";
    //                         }
    //                     } else if($data3->PnPBillMod=="PBM03"){
    //                         // $billMode = "Bill to CAPCON";
    //                         // $amount_collect = $data->contribution_amount;
    //                         $capconBillAmt = $data3->MOA1;
    //                         $billRemarks = "CAPITAL.CONTRIBUTION";

    //                     } else if($data3->PnPBillMod == "PBM04"){
    //                         // $amount_collect = $data->contribution_amount;
    //                         // $billMode = "Bill to Savings";
    //                         $casaBillAmt = $data3->MOA1;
    //                         $billRemarks = "SAVINGS.CONTRIBUTION";
    //                     }else if($data3->PnPBillMod=="PBM05"){
    //                         // $amount_collect = $data->contribution_amount;
    //                         // $billMode = "Bill to CASA";
    //                         $psaBillAmt = $data3->MOA1;
    //                         $billRemarks = "CASA.CONTRIBUTION";
    //                     } else if($data3->PnPBillMod=="PBM00"){
    //                         $loanBillAmtNew = $data3->MOA1;
    //                         // $billMode = "Bill to Loan";
    //                         // $amount_collect = $data->loanAmt;
    //                         if($data->TblCollectionPpscID == null){
    //                             $billRemarks = "NEW LOAN";
    //                         }
    //                     } else if($data->PnPBillMod=="PBM07"){
    //                         $amount_collect = "";
    //                     }
    //                     else{
    //                         // $billMode = "";
    //                     }
    //                     $totalBillAmt = str_replace(',','',number_format($loanBillAmt + $capconBillAmt + $casaBillAmt + $psaBillAmt,2));
    //                     $totalBillAmt = $totalBillAmt == 0 ? "" : $totalBillAmt;
    //                     //Deduction Code
    //                     $group_service_status = $this->getGroupServiceStatus($data->SvcStat);
    //                     $branchSvc = trim($data->BranchSvc);
    //                     $deduction_code = $this->getDeductionCode($group_service_status,$data3->PnPBillMod,$data3->lnType,$branchSvc);
    //                     $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
    //                     $billMode = $newBillMode["bill_mode"];
    //                     // Getting the PH.LOAN.TYPE
    //                     $loanTypeQry = TblLoanType::findFirst("loan_type = '$data3->lnType'");
    //                     $loanType = $loanTypeQry->product;
    //                     $payPayPeriod = $this->getPayPeriod($data3->id_loan_csv_name, trim($data->BranchSvc));
    //                     if($payPayPeriod != ""){
    //                         $GLOBALS['tempPayPeriod'] = $payPayPeriod;
    //                     }
    //                     else{
    //                         $payPayPeriod = $GLOBALS['tempPayPeriod'];
    //                     }
    //                     // $origId = $data1->AccountName;
    //                     $get1stdate = TblLoanCsvFile::query()
    //                     ->columns('initDate')
    //                     ->limit(1)
    //                     ->orderBy("id Desc")
    //                     ->execute();
    //                     $temp_info = array(
    //                         'up_company'        => 'PH0010002', //UPLOAD.COMPANY
    //                         'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
    //                         'collectionPeriod'  => $this->returnEmpty($payPayPeriod),
    //                         'PIN'               => "", //ATM.PIN
    //                         'full_name'         => "",
    //                         'customerCode'      => $this->returnEmpty($data->T24MemberNo),
    //                         'LastName'          => trim($data->LastName),
    //                         'FirstName'         => trim($data->FirstName),
    //                         'MiddleName'        => trim($data->MiddleName),
    //                         'QualifNam'         => $this->returnEmpty(trim($data->QualifNam)), //PH.QUAL.NAME
    //                         'SvcStat'           => $this->returnEmpty ($data->SvcStat), //PH.SERVICE.STATUS
    //                         'loanBillAmt'       => $this->returnEmpty($loanBillAmt),
    //                         'capconBillAmt'     => $this->returnEmpty($capconBillAmt), //PH.CAPCON.BILL.AMT
    //                         'casaBillAmount'    => $this->returnEmpty($casaBillAmt), //PH.CASA.BILL.AMT
    //                         'psaBillAmount'     => $this->returnEmpty($psaBillAmt), //PH.PSA.BILL.AMT
    //                         'totalBillAmt'      => $this->returnEmpty($totalBillAmt),
    //                         'BillAmt'           => $this->returnEmpty($casaBillAmt), //LOANBillAmount
    //                         'lproNo'            => $this->returnEmpty($data3->lproNo), //LproNo
    //                         'lnType'            => $this->returnEmpty($loanType), //lnType
    //                         'loanAppl'          => "",
    //                         'maturity'          => "", //maturity
    //                         'dateGrant'         => $data3->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate))  : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
    //                         'updContDate'       => "", //updContDate
    //                         'loanTerm'          => $this->returnEmpty($data3->lnpTrate), //updContDate
    //                         'loanAmount'        => $this->returnEmpty($data3->loanAmt),
    //                         'loanStat'          => "", //updContDate
    //                         'startAmrtDate'     => "",
    //                         'billTransType'     => "", //PH.BILL.TRANS.TYPE
    //                         'bilTransStat'      => "", //PH.BIL.TRANS.STAT
    //                         'billMode'          => $billMode,
    //                         'OrigDedNCode'      => $this->returnEmpty($deduction_code),
    //                         'updtDeDNCode'      => "",
    //                         'origNri'           => "",
    //                         'uptNri'            => "",
    //                         'billRemarks'       => $this->returnEmpty($billRemarks),
    //                         'isStoppage'        => $billRemarks == "STOPPAGE" ? "YES" : "NO",
    //                         'AtmCardNo'         => "", //ATM.CARD.NO.1
    //                         'fullName2'         => "",
    //                         'pensAcctNo'        => "",
    //                         'payMtStats'        => "",
    //                         'stopDedNCode'      => "",
    //                         'incld_bil_ind'     => "YES",
    //                         '1stLoadAmt'        => "",
    //                         'amtCollect'        => $this->returnEmpty($amount_collect),
    //                         'payPeriodCollect'  => $this->returnEmpty($collection->collection_pay_period)
    //                     );
    //                     $info [] = $temp_info;
    //                     array_push($info,$temp_info);
    //                     // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
    //                     $result = html_entity_decode(implode("|",$temp_info)."\r\n");
    //                     fwrite($txt,$result);
    //                     fputcsv($fp, $temp_info );
    //                     $ctr++;
    //                 }
    //            }
    //         $myTextFileHandler = fopen($path2,"r+");
    //         $d = ftruncate($myTextFileHandler, 0);
    //         fclose($myTextFileHandler);
    //         $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
    //         echo $fnlRes;
    //         }
    //     }
    //     catch(Exception $e){
    //         $this->respond(array(
    //             'statusCode'    => 500,
    //             'devMessage'    => $e->getMessage()
    //         ));
    //     }
    // }

    public function exportBillingNapolcomAction() {
        ini_set('memory_limit', '-1');
        $date = date("YmdHis");
        $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        $fp = fopen('/var/www/html/psslai/public/export/BILLING_NAPOLCOM_ao'.$date.'.csv', 'w');
        $path2 = "/var/www/html/psslai/public/export/fileLog_tempNAPOLCOM.log";
        $pathtxt = "/var/www/html/psslai/public/export/BILLING_NAPOLCOM_ao".$date.".txt";
        $txt = fopen($pathtxt, "w") or die("Unable to open file!");
        $exportCsv1 = 'export/BILLING_NAPOLCOM_ao'.$date.'.csv';
        $exportTxt2 = 'export/BILLING_NAPOLCOM_ao'.$date.'.txt';
            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $c = "TblAtmListFile";
            $d = "TblLoanCsvFile"; //Loan Billing
            $e = "TblLoanAtmFile"; //Loan Atm
            $f = "TblCollectionNapolcom";
            $g = "TblDeductionCode";
            $ss = "TblServiceStatus";
            $tbl_branchSvc = "TblBos";
            $tbl_loanType = "TblLoanType";
            $tbl_billMode = "TblBillMode";
            $cod = "";

            if ($filter == 'FULLY PAID') {
                $cod = "($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
                $ext = "_Fully Paid";
            } else if ($filter == 'NO ACCOUNT') {
                $cod = "($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
                $ext = "_No Account";
            } else if ($filter == 'OPTIONAL') {
                $cod = "($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $ext = "_Optional";
            } else if ($filter == 'NORMAL') {
                $cod = " $d.lproNo IS NOT null AND
                    ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' AND $a.SvcStat != 'OP' AND $a.SvcStat != 'RD' AND $a.SvcStat != 'RE')";
                $ext = "_Normal";
            }

            if($cod){
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName,' ',$a.QualifNam) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.T24MemberNo,$a.PINAcctNo,$d.MOA1,$d.MOA2,$d.PnpBillMod,$d.id as TblLoanBillingId,$e.memberNo, $tbl_loanType.product,
                $d.lproNo,$e.loanAmt,$e.maturity,$e.startDate1,$e.loanAppl,$e.lnpTrate, $d.lnType")
                ->join($d,"$a.SapMemberNo = $d.memberNo","","left")
                ->join($e,"$e.memberNo = $a.SapMemberNo","","left")
                ->join($tbl_loanType,"$d.lnType = $tbl_loanType.loan_type","","left")
                ->where("$a.BranchSvc = 'NAPOLCOM' AND $cod")
                ->limit($row,$offset)
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT(TRIM($a.LastName),', ',TRIM($a.FirstName),' ',TRIM($a.MiddleName),' ',TRIM($a.QualifNam)) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.T24MemberNo,$a.PINAcctNo,$d.MOA1,$d.MOA2,$d.PnpBillMod,$d.id as TblLoanBillingId,$e.memberNo, $tbl_loanType.product,$d.id_loan_csv_name,
                $d.lproNo,$e.loanAmt,$e.dateGrant,$e.maturity,$e.startDate1,$e.loanAppl,$e.lnpTrate, $d.lnType")
                ->join($d,"$a.SapMemberNo = $d.memberNo","","left")
                ->join($e,"$e.memberNo = $a.SapMemberNo","","left")
                ->join($tbl_loanType,"$d.lnType = $tbl_loanType.loan_type","","left")
                ->where("$a.BranchSvc = 'NAPOLCOM'")
                ->limit($row,$offset)
                ->execute();
            }

            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info = array();

                $b_AccountName          = null;
                $SvcStat                = null;
                $startDate              = null;
                $endDate                = null;
                $dateGrant              = null;
                $amortLpro              = null;
                $amortCollection        = null;
                $dateGranted            = null;
                $group_service_status   = null;
                $ifClientExistsLpro     = null;
                $lproDateGrant          = null;
                $billMode               = null;
                $payPayPeriod           = null;
                $getBillRemarks         = null;
                $billPage               = null;
                $getInitDate            = null;
                $formattedInitDate      = null;
                $currentDate            = null;
                $checkerMonthCurrent    = null;
                $checkerYearCurrent     = null;
                $startOfCutOffCurrent   = null;
                $endOfCutOffCurrent     = null;
                $billRemarks            = null;
                $checkerMonth           = null;
                $checkerYear            = null;
                $tempStartCutOff        = null;
                $tempEndCutOff          = null;
                $billRemarks            = null;
                $dateGranted            = null;
                $branchSvc              = null;
                $deduction_code         = null;
                $loanBillAmtNew         = null;
                $loanType               = null;
                $billTransType          = null;
                $loanBillAmt            = null;
                $loanBillAmt            = null;
                $loanOriginationId      = null;

                $SapMemberNo = null;
                $startDate1  = null;
                $startDate2  = null;
                $dateGrant   = null;
                $MOA1       = null;
                $dateGrant   = null;
                $SapMemberNo = null;
                $PnpBillMod  = null;
                $id_loan_csv_name    = null;
                $BranchSvc   = null;
                $PnpBillMod  = null;
                $startDate1  = null;
                $startDate2  = null;
                $id_loan_csv_name    = null;
                $BranchSvc   = null;
                $dateGrant   = null;
                $lnType  = null;
                $PnpBillMod  = null;
                $startDate2 = null;
                $lnType = null;
                $amount = null;
                $collection_pay_period  = null;
                $loanType = null;
                $test = "";
                $ctr = 0;
                $headerCSV = ['UPLOAD.COMPANY',
                'PH.BRANCH.SVC',
                'PH.PAY.PERIOD',
                'PH.PIN.NO',
                'PH.FULLNAME1',
                'CUSTOMER.CODE',
                'PH.LAST.NAME',
                'PH.FIRST.NAME',
                'PH.MIDDLE.NAME',
                'PH.QUAL.NAME',
                'PH.SERVICE.STATUS',
                'PH.LOAN.BILL.AMT',
                'PH.CAPCON.BILL.AMT',
                'PH.CASA.BILL.AMT',
                'PH.PSA.BILL.AMT',
                'PH.TOTAL.BILL.AMT',
                'PH.BILL.AMT',
                'PH.LOAN.ORIG.ID',
                'PH.LOAN.TYPE',
                'PH.APP.TYPE',
                'PH.MATURITY.DATE',
                'PH.ORIG.CONT.DATE',
                'PH.UPD.CONT.DATE',
                'PH.LOAN.TERM',
                'PH.ORIG.CONT.AMT',
                'PH.LOAN.STAT',
                'PH.START.AMRT.DATE',
                'PH.BILL.TRANS.TYPE',
                'PH.BIL.TRANS.STAT',
                'PH.BILL.MODE',
                'PH.ORIG.DEDNCODE',
                'PH.UPD.DEDNCODE',
                'PH.ORIG.NRI',
                'PH.UPDATED.NRI',
                'PH.BILL.REMARKS',
                'PH.BILL.STOPPAGE',
                'PH.ATM.CARD.NO',
                'PH.FULLNAME2',
                'PH.PENS.ACCT.NO',
                'PH.PAYMT.STAT',
                'PH.STOP.DEDN.CODE',
                'PH.INCLD.BIL.IND',
                'PH.1ST.LOAD.AMT',
                'AMOUNT.COLLECT',
                'PAY.PERIOD.COLLECT'];

                fputcsv($fp, $headerCSV );
                foreach($getQry as $data){
                    $myfile = fopen($path2, "w") or die("Unable to open file!");
                    fwrite($myfile,  $test);
                    $percent = $ctr /count($getQry) * 100;
                    echo "BILLING NAPOLCOM - Exporting file : ".number_format($percent,2)."%";

                    $SapMemberNo = $data->SapMemberNo;
                    $startDate1 = $data->startDate1;
                    $startDate2 = $data->startDate2;
                    $dateGrant = $data->dateGrant;

                    $get1stdate = TblLoanCsvFile::query()
                    ->columns('initDate, id_loan_csv_name')
                    ->limit(1)
                    ->orderBy("id Desc")
                    ->execute();

                    $collection = TblCollectionNapolcom::findFirst("member_no = '".$SapMemberNo."'");
                    if ($collection) {
                        $amount = $collection->amount;
                        $collection_pay_period = $collection->collection_pay_period;
                    }

                    $getQryMemberAccountFile = TblMemberAccountFile::findFirst(array(
                        "columns"    => "AccountName",
                        "conditions" => "MemberNo = '$SapMemberNo'",
                    ));
                    if($getQryMemberAccountFile){
                        $b_AccountName = $this->returnEmpty(str_replace("-", "", $getQryMemberAccountFile->AccountName));
                    }

                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billRemarks = "";
                    // $billAmnt == 0;
                    $SvcStat = trim($data->SvcStat);

                    $startDate = date('Y-m-d', strtotime($startDate1));
                    $endDate = date('Y-m-d',  strtotime($startDate2));
                    $dateGrant = date('Y-m-d', strtotime($dateGrant));

                    $amortLpro = (double)$data->MOA1;
                    $amortCollection = (double)$collection->amount;
                    $dateGranted = $data->dateGrant;

                    //Get Group and Deduction Code
                    $group_service_status = $this->getGroupServiceStatus($SvcStat);

                    $ifClientExistsLpro = TblLoanCsvFile::findFirst("memberNo = '$data->SapMemberNo'");
                    $lproDateGrant = $ifClientExistsLpro->dateGrant;
                    if (!$ifClientExistsLpro) {
                        $lproDateGrant = $get1stdate[0]->initDate;
                    }

                 //FOR BILLMODE
                    $getBillParams = $this->getBillParams($data->PnpBillMod,$amortLpro,0);
                    $billMode = $getBillParams["billMode"];

                 // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($data->id_loan_csv_name, trim($data->BranchSvc));
                    if(!$payPayPeriod){
                        $payPayPeriod = $this->getPayPeriod(trim($get1stdate[0]->id_loan_csv_name), trim($data->BranchSvc));
                    }

                 // Get bill remarks ---------------------------------------------------------------------------------------------
                        $getBillRemarks = $this->getBillRemarks($data->PnpBillMod,$dateGranted,$data->startDate1,$data->startDate2,0,0,$amortLpro,$amortCollection);
                        // $billRemarks = $getBillParams["billRemarks"];
                        $billPage = $getBillParams["billPage"];

                         $getInitDate = TblLoanCsvFile::findFirst("id_loan_csv_name = '$data->id_loan_csv_name' AND initDate IS NOT NULL");
                         $formattedInitDate = date("m-d-Y", strtotime(substr($getInitDate->initDate, 0,10)));

                         $currentDate = date("Y-m-d");
                         $checkerMonthCurrent = date("m");
                         $checkerYearCurrent = date("Y");
                             $startOfCutOffCurrent = date('m-01-Y');
                             $endOfCutOffCurrent  = date('m-t-Y');
                        if ($data->BranchSvc == "BFP" || $data->BranchSvc == "BJMP" || $data->BranchSvc == "NAPOLCOM" || $data->BranchSvc == "PPSC") {

                            //DateGranted or InitDate?
                                if (($startOfCutOffCurrent <= $formattedInitDate) && ($formattedInitDate <= $endOfCutOffCurrent))  {
                                    $billRemarks = !($data->member_no) ? "New Billing" : "Previous Billing";
                                } else if (($formattedInitDate < $startOfCutOffCurrent)) {
                                    $billRemarks = "Previous Billing";
                                }
                        } else {
                            $checkerMonth = date("m", strtotime(substr($currentDate, 0,10)));
                            $checkerYear = date("Y", strtotime(substr($currentDate, 0,10)));
                            $tempStartCutOff = date('m-27-Y', strtotime("-1 month")); //date("m-d-Y", strtotime("-1 month", strtotime($checkerYear."-".$checkerMonth."-27")));
                            $tempEndCutOff = date("m-26-Y");

                                if (($tempStartCutOff <= $formattedInitDate) && ($formattedInitDate <= $tempEndCutOff))  {
                                    $billRemarks = !($data->member_no) ? "New Billing" : "Previous Billing";
                                } else if (($formattedInitDate < $tempStartCutOff)) {
                                    $billRemarks = "Previous Billing";
                                }
                        }

                 //---------------------------------------------------------------------------------------------------------------

                //  $newClient = $this->getReferenceSdlis($billingType,$group_service_status,trim($data->PINAcctNo)); //"20020302002"
                //  $loanBillAmtNewSDLIS = $newClient["amortization"];
                //  $deduction_codeSDLIS = $newClient["deductionCode"];
                //  $dateGranted = $newClient["dateGranted"];

                $dateGranted = $data->dateGrant;
                $branchSvc = trim($data->BranchSvc);
                $deduction_code = $this->getDeductionCode($group_service_status,$data->PnpBillMod,$data->lnType,$branchSvc);
                if($deduction_code == ""){
                    $deduction = trim($collection->deduction_code);
                } else {
                    $deduction = $deduction_code;
                }
                $newBillMode = $this->getBillMode($group_service_status,$deduction,$branchSvc);
                $billMode = $newBillMode["bill_mode"];

                if($data->lproNo == null || $data->lproNo == ""){
                    if($billMode == "Bill to CAPCON" || $billMode == "Bill to CASA" || $billMode == "Bill to Savings"){
                        $origId = str_replace("-", "", $b_AccountName);
                     } else {
                        $origId = "";
                     }
                } else {
                    $origId = str_replace("-", "", $data->lproNo);
                }
                $loanBillAmtNew = $data->MOA1;
                $nriVal = "";
                if ($data->MOA1 == null || $data->MOA1 == "") {
                    $loanBillAmtNew = $collection->amount;
                }

                // Getting the PH.LOAN.TYPE
                $loanTypeQry = TblLoanType::findFirst("loan_type = '$data->lnType'");
                $loanType = $loanTypeQry->product;

                // Getting the value of PH.BILL.TRANS.TYPE
                if ($data->PnpBillMod == "PBM01" || $data->PnpBillMod == "PBM02") {
                    $billTransType = "Loan Payment";
                } else if ($data->PnpBillMod == "PBM03" || $data->PnpBillMod == "PBM05") {
                    $billTransType = "Contribution";
                }


            $startDate1 = $data->startDate1 == null ? "" : date('Ymd', strtotime($data->startDate1));
            $fullName1 = $data->full_name;      // with QualifNam
            $fullName2 = $data->LastName.' '.$data->FirstName.' '.$data->MiddleName;    // without QualifNam

                //  //Adding of New line (BILL AMOUNT PROCESS)
            if(trim($data->lnType) != "BL" && trim($data->lnType) != "NL") {
                $newLine = array();
                if(trim($data->PnpBillMod) == "PBM01" || trim($data->PnpBillMod) == "PBM02"){
                    $loanBillAmt = $data->MOA1;
                } else if (trim($data->PnpBillMod) == "PBM03" || trim($data->PnpBillMod) == "PBM04" ||
                            trim($data->PnpBillMod) == "PBM05" || trim($data->PnpBillMod) == "PBM07") {

                                if($amortCollection > $amortLpro) {
                                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = true;
                                    $loanBillAmt = $amortCollection - $amortLpro;
                                    $getLoanOriginationId = TblMemberAccountFile::findFirst("MemberNo='$data->SapMemberNo' AND TSAcctTy = '$data->TSAcctTy'");
                                    $loanOriginationId = $this->getLoanOriginationId($data->PnpBillMod,$data->lproNo);

                                        $temp_newLine = array(
                                            'up_company'        => 'PH0010002', //UPLOAD.COMPANY
                                            'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                                            'PayPeriod'         => $this->returnEmpty($payPayPeriod),
                                            'PINAcctNo'         => $this->returnEmpty($data->PINAcctNo),
                                            // 'full_name'         => $data->LastName.' '.$data->FirstName.' '.$data->MiddleName.' '.$this->returnEmpty($data->QualifNam),
                                            'full_name'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),

                                            'T24MemberNo'       => $this->returnEmpty($data->T24MemberNo),
                                            // 'loanType'  => $this->returnEmpty($data->product),
                                            // 'MemberStat' => $this->returnEmpty($data->SapMemberNo),
                                            'LastName'          => "",
                                            'FirstName'         => "",
                                            'MiddleName'        => "",
                                            'QualifNam'         => "", //PH.QUAL.NAME
                                            'SvcStat'           => $this->returnEmpty($data->SvcStat), //PH.SERVICE.STATUS
                                            'loanBillAmt'       => $this->returnEmpty($loanBillAmt),
                                            'capconBillAmt'     =>  "", //PH.CAPCON.BILL.AMT
                                            'casa_bill_amount'  =>  "", //PH.CASA.BILL.AMT
                                            'psa_bill_amount'   =>  "", //PH.PSA.BILL.AMT
                                            'totalBillAmt'      => "",
                                            'BillAmt'           => $this->returnEmpty($loanBillAmt), //LOANBillAmount
                                            'lproNo'            => $this->returnEmpty($origId), //LproNo
                                            'loanType'          => $this->returnEmpty($loanType),
                                            'loanAppl'          => $this->returnEmpty($data->loanAppl),
                                            'maturity'          => date('Ymd', strtotime($data->maturity)), //maturity
                                            // 'dateGrant'         => $data->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate)) : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                                            'dateGrant'         => date('Ymd', strtotime($lproDateGrant)),
                                            'updContDate'       => "", //updContDate
                                            'loanTerm'          => $this->returnEmpty($data->lnpTrate), //updContDate
                                            'loanAmount'        => $this->returnEmpty($data->loanAmt),
                                            'loanStat'          => trim($data->loanProc), //updContDate
                                            'startAmrtDate'     => $this->returnEmpty($startDate1),
                                            // 'MOA1' => $this->returnEmpty($data->MOA1), //PH.MOAMORT1
                                            'billTransType'     => $this->returnEmpty($billTransType), //PH.BILL.TRANS.TYPE
                                            'bilTransStat'      => "", //PH.BIL.TRANS.STAT
                                            'billMode'          => $this->returnEmpty($billMode),
                                            'OrigDedNCode'      => $this->returnEmpty($deduction),

                                            'updtDeDNCode'      => "",
                                            'term'              => "",
                                            'uptNri'            => $this->returnEmpty($nriVal),
                                            'Remarks'           => $billRemarks,
                                            'isStoppage'        => $this->returnEmpty($billRemarks) == "STOPPAGE" ? "YES" : "NO",
                                            'CONTROL'           => $this->returnEmpty($data->CONTROL), //ATM.CARD.NO.1
                                            'fullName2'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),
                                            'pensAcctNo'        => "",
                                            'payMtStats'        => "",
                                            'stopDedNCode'      => "",
                                            'incld_bil_ind'     => "YES",
                                            '1stLoadAmt'        => "",
                                            'amtCollect'        => $this->returnEmpty($collection->amount),
                                            'payPeriodCollect'  => $this->returnEmpty($collection->collection_pay_period));

                                            if($filter == 'NO ACCOUNT'){
                                                $ext = "_No Account";

                                                if ($b_AccountName == null || $b_AccountName == "") {
                                                        $newLine [] = $temp_newLine;

                                                        array_push($info,$temp_info);
                                                        fputcsv($fp, $temp_info );
                                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                                        fwrite($txt,$result);
                                                }
                                            } else if($filter == 'NORMAL') {
                                                $ext = "_Normal";
                                                if (!empty($b_AccountName)) {
                                                    $newLine [] = $temp_newLine;

                                                    array_push($info,$temp_info);
                                                    fputcsv($fp, $temp_info );
                                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                                    fwrite($txt,$result);
                                                }
                                            } else {
                                                $newLine [] = $temp_newLine;

                                                array_push($info,$temp_info);
                                                fputcsv($fp, $temp_info );
                                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                                fwrite($txt,$result);

                                            }

                                } else {
                                    $loanBillAmt = $data->MOA1;
                                }
                }

                if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {

                        $temp_info = array(

                            'up_company'        => 'PH0010002', //UPLOAD.COMPANY
                            'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                            'PayPeriod'         => $this->returnEmpty($payPayPeriod),
                            'PINAcctNo'         => $this->returnEmpty($data->PINAcctNo),
                            'full_name'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),
                            'T24MemberNo'       => $this->returnEmpty($data->T24MemberNo),
                            // 'loanType'  => $this->returnEmpty($data->product),
                            // 'MemberStat' => $this->returnEmpty($data->SapMemberNo),
                            'LastName'          => "",
                            'FirstName'         => "",
                            'MiddleName'        => "",
                            'QualifNam'         => "", //PH.QUAL.NAME
                            'SvcStat'           => $this->returnEmpty($data->SvcStat), //PH.SERVICE.STATUS
                            'loanBillAmt'       => $this->returnEmpty($loanBillAmtNew),
                            'capconBillAmt'     =>  "", //PH.CAPCON.BILL.AMT
                            'casa_bill_amount'  =>  "", //PH.CASA.BILL.AMT
                            'psa_bill_amount'   =>  "", //PH.PSA.BILL.AMT
                            'totalBillAmt'      => "",
                            'BillAmt'           => $this->returnEmpty($loanBillAmtNew), //LOANBillAmount
                            'lproNo'            => $this->returnEmpty($origId), //LproNo
                            'loanType'          => $this->returnEmpty($data->product),
                            'loanAppl'          => "",
                            'maturity'          => date('Ymd', strtotime($data->maturity)), //maturity
                            // 'dateGrant'         => $data->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate)) : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                            'dateGrant'         => date('Ymd', strtotime($lproDateGrant)),
                            'updContDate'       => "", //updContDate
                            'loanTerm'          => $this->returnEmpty($data->lnpTrate), //updContDate
                            'loanAmount'        => $this->returnEmpty($data->loanAmt),
                            'loanStat'          => trim($data->loanProc), //updContDate
                            'startAmrtDate'     => $this->returnEmpty($startDate1),
                            'billTransType'     => $this->returnEmpty($billTransType), //PH.BILL.TRANS.TYPE
                            'bilTransStat'      => "", //PH.BIL.TRANS.STAT
                            'billMode'          => $this->returnEmpty($billMode),
                            'OrigDedNCode'      => $this->returnEmpty($deduction),

                            'updtDeDNCode'      => "",
                            'term'              => "",
                            'uptNri'            => $this->returnEmpty($nriVal),
                            'Remarks'           => $billRemarks,
                            'isStoppage'        => $this->returnEmpty($billRemarks) == "STOPPAGE" ? "YES" : "NO",
                            'CONTROL'           => $this->returnEmpty($data->CONTROL), //ATM.CARD.NO.1
                            'fullName2'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),
                            'pensAcctNo'        => "",
                            'payMtStats'        => "",
                            'stopDedNCode'      => "",
                            'incld_bil_ind'     => "YES",
                            '1stLoadAmt'        => "",
                            'amtCollect'        => $this->returnEmpty($collection->amount),
                            'payPeriodCollect'  => $this->returnEmpty($collection->collection_pay_period)

                         );

                         if($filter == 'NO ACCOUNT'){
                             $ext = "_No Account";

                             if ($b_AccountName == null || $b_AccountName == "") {
                                     $info [] = $temp_info;

                                     array_push($info,$temp_info);
                                     fputcsv($fp, $temp_info );
                                     $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                     fwrite($txt,$result);
                             }
                         } else if($filter == 'NORMAL') {
                             $ext = "_Normal";

                             if (!empty($b_AccountName)) {
                                 $info [] = $temp_info;

                                 array_push($info,$temp_info);
                                 fputcsv($fp, $temp_info );
                                 $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                 fwrite($txt,$result);
                             }
                         } else {
                             $info [] = $temp_info;

                             array_push($info,$temp_info);
                             fputcsv($fp, $temp_info );
                             $result = utf8_encode(implode("|",$temp_info)."\r\n");
                             fwrite($txt,$result);

                         }


                         $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                         array_push($info,$temp_newLine);
                         fputcsv($fp, $temp_info );
                         $result = utf8_encode(implode("|",$temp_info)."\r\n");
                         fwrite($txt,$result);
                         $newLine = array();

                } else {


                    $temp_info = array(
                        'up_company'        => 'PH0010002', //UPLOAD.COMPANY
                        'BranchSvc'         => $this->returnEmpty($data->BranchSvc), //PH.BRANCH.SVC
                        'PayPeriod'         => $this->returnEmpty($payPayPeriod),
                        'PINAcctNo'         => $this->returnEmpty($data->PINAcctNo),
                        'full_name'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),
                        'T24MemberNo'       => $this->returnEmpty($data->T24MemberNo),
                        // 'loanType'  => $this->returnEmpty($data->product),
                        // 'MemberStat' => $this->returnEmpty($data->SapMemberNo),
                        'LastName'          => "",
                        'FirstName'         => "",
                        'MiddleName'        => "",
                        'QualifNam'         => "", //PH.QUAL.NAME
                        'SvcStat'           => $this->returnEmpty($data->SvcStat), //PH.SERVICE.STATUS
                        'loanBillAmt'       => $this->returnEmpty($loanBillAmtNew),
                        'capconBillAmt'     =>  "", //PH.CAPCON.BILL.AMT
                        'casa_bill_amount'  =>  "", //PH.CASA.BILL.AMT
                        'psa_bill_amount'   =>  "", //PH.PSA.BILL.AMT
                        'totalBillAmt'      => "",
                        'BillAmt'           => $this->returnEmpty($loanBillAmtNew), //LOANBillAmount
                        'lproNo'            => $this->returnEmpty($origId), //LproNo
                        'loanType'          => $this->returnEmpty($data->product),
                        'loanAppl'          => "",
                        'maturity'          => date('Ymd', strtotime($data->maturity)), //maturity
                        // 'dateGrant'         => $data->dateGrant == "" ? date("Ymd", strtotime($get1stdate[0]->initDate)) : date("Ymd", strtotime($data3->dateGrant)), //dateGrant
                        'dateGrant'         => date('Ymd', strtotime($lproDateGrant)),
                        'updContDate'       => "", //updContDate
                        'loanTerm'          => $this->returnEmpty($data->lnpTrate), //updContDate
                        'loanAmount'        => $this->returnEmpty($data->loanAmt),
                        'loanStat'          => trim($data->loanProc), //updContDate
                        'startAmrtDate'     => $this->returnEmpty($startDate1),
                        'billTransType'     => $this->returnEmpty($billTransType), //PH.BILL.TRANS.TYPE
                        'bilTransStat'      => "", //PH.BIL.TRANS.STAT
                        'billMode'          => $this->returnEmpty($billMode),
                        'OrigDedNCode'      => $this->returnEmpty($deduction),
                        'updtDeDNCode'      => "",
                        'term'              => "",
                        'uptNri'            => $this->returnEmpty($nriVal),
                        'Remarks'           => $billRemarks,
                        'isStoppage'        => $this->returnEmpty($billRemarks) == "STOPPAGE" ? "YES" : "NO",
                        'CONTROL'           => $this->returnEmpty($data->CONTROL), //ATM.CARD.NO.1
                        // 'fullName2'         => $data->LastName.' '.$data->FirstName.' '.$data->MiddleName.' '.$data->QualifNam,
                        'fullName2'         => $data->QualifNam == "" ? $this->returnEmpty($fullName2) : $this->returnEmpty($fullName1),
                        'pensAcctNo'        => "",
                        'payMtStats'        => "",
                        'stopDedNCode'      => "",
                        'incld_bil_ind'     => "YES",
                        '1stLoadAmt'        => "",
                        'amtCollect'        => $this->returnEmpty($collection->amount),
                        'payPeriodCollect'  => $this->returnEmpty($collection->collection_pay_period)

                      );

                      if($filter == 'NO ACCOUNT'){
                          $ext = "_No Account";

                          if ($b_AccountName == null || $b_AccountName == "") {
                                  $info [] = $temp_info;

                                  array_push($info,$temp_info);
                                  fputcsv($fp, $temp_info );
                                  $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                  fwrite($txt,$result);
                          }
                      } else if($filter == 'NORMAL') {
                          $ext = "_Normal";

                          if (!empty($b_AccountName)) {
                              $info [] = $temp_info;

                              array_push($info,$temp_info);
                              fputcsv($fp, $temp_info );
                              $result = utf8_encode(implode("|",$temp_info)."\r\n");
                              fwrite($txt,$result);
                          }
                      } else {
                          $info [] = $temp_info;

                          array_push($info,$temp_info);
                          fputcsv($fp, $temp_info );
                          $result = utf8_encode(implode("|",$temp_info)."\r\n");
                          fwrite($txt,$result);

                      }
               }
            }
               $global_newline_array = array();
               $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
               $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;
                // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                // fputcsv($fp, $temp_info );
                $ctr++;

            }
            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
            echo $fnlRes;
            exit;
        }
    }

    public function exportBillingPnpAcAction(){
          ini_set('memory_limit', '-1');

       $rootPath = "/var/www/html/psslai/";
    //    $rootPath = "/var/www/psslai/";
       $exportFileName = "export/BILLING_PNP-AC_ao";
       $logFileName = "export/fileLog_tempPNPAC.log";
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $counter = 0;
       $date = date("YmdHis");
       $path2 = $rootPath."public/".$logFileName;
       $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";
    //    $txt = fopen($pathtxt, "w") or die("Unable to open file!");
       $exportCsv1 = $exportFileName.$date.'.csv';
       $exportTxt2 = $exportFileName.$date.'.txt';

        defined('APP_PATH') || define('APP_PATH', realpath('.'));
         $date = date("YmdHis");


        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $c = "TblAtmListFile";
        $d = "TblLoanCsvFile"; //Loan Billing
        $e = "TblLoanAtmFile"; //Loan Atm
        $f = "TblDeductionCode"; //Collection
        $g = "TblBillMode";
        $h = "TblCollectionPnpAc";
        $ss = "TblServiceStatus";
        $tbl_branchSvc = "TblBos";
        $tbl_loanType = "TblLoanType";
        $billingType = "PnpAc";
        $ext = "";
        $aa = "";
        $bb = "";
        $dd = "";
        $cod = "";

        if($filter == 'FULLY PAID'){
            $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
            $ext = "_Fully Paid";
        } else if($filter == 'NO ACCOUNT'){
            $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
            $ext = "_No Account";
        } else if($filter == 'OPTIONAL'){
            $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $ext = "_Optional";
        } else if($filter == 'NORMAL') {
            $aa = " AND ($a.SvcStat != 'CO' AND $a.SvcStat != 'OP' AND $a.SvcStat != 'RD' AND $a.SvcStat != 'RE')";
            $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
            $ext = "_Normal";
        }

         $exportCsv  = 'export/BILLING_PNP-AC_ao'.$date.$ext.'.csv';
        $exportTxt  = 'export/BILLING_PNP-AC_ao'.$date.$ext.'.txt';
        $exportCsv1 = 'export/BILLING_PNP-AC_ao'.$date.$ext.'.csv';
        $exportTxt2 = 'export/BILLING_PNP-AC_ao'.$date.$ext.'.txt';
        $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

        $fp = fopen($rootPath.'public/export/BILLING_PNP-AC_ao'.$date.$ext.'.csv', 'w');
        $headerCSV = ['UPLOAD.COMPANY', //1
            'PH.BRANCH.SVC', //2
            'PH.PAY PERIOD', //3
            'PH.PIN NO', //4
            'PH.FULLNAME 1', //5
            'CUSTOMER CODE',//6
            'PH.LAST.NAME',//7
            'PH.FIRST.NAME',//8
            'PH.MIDDLE.NAME',//9
            'PH.QUAL.NAME',//10
            'PH.SERVICE STATUS',//11
            'PH.LOAN.BILL.AMT',//**** */12
            'PH.CAPCON.BILL.AMT',//****13
            'PH.CASA.BILL.AMT',//****
            'PH.PSA.BILL.AMT',//****
            'PH.TOTAL.BILL.AMT',//14
            'PH.BILL.AMT',//15 //****
            'PH.LOAN.ORIG.ID',//16
            'PH.LOAN.TYPE',//17
            'PH.APP.TYPE',//18
            'PH.MATURITY.DATE',//19
            'PH.ORIG.CONT.DATE',//20
            'PH.UPD.CONT.DATE',//21
            'PH.LOAN.TERM',//22
            'PH.ORIG.CONT.AMT',//23
            'PH.LOAN.STAT',//24
            'PH.START.AMRT.DATE',//25
            'PH.BILL.TRANS.TYPE',//26
            'PH.BILL.TRANS.STAT',//
            'PH.BILL.MODE',
            'PH.ORIG.DEDNCODE',
            'PH.UPD.DEDNCODE',
            'PH.ORIG.NRI',
            'PH.UPDATED.NRI',
            'PH.BILL.REMARKS',
            'PH.BILL.STOPPAGE',
            'PH.ATM.CARD.NO',
            'PH.FULLNAME2',
            'PH.PENS.ACCT.STAT',
            'PH.PAYMT.STAT',
            'PH.STOP.DEDN.CODE',
            'PH.INCLD.BIL.IND',
            'PH.1ST.LOAD.AMT',
            'AMOUNT.COLLECT',
            'PAY.PERIOD.COLLECT'];

        fputcsv($fp, $headerCSV );



        if($cod){
            $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
                ->where("$a.BranchSvc = 'PNP' $aa")
                ->groupBy("$a.id")
                ->execute();

        } else {

            $getQry = TblMemberInfoFile::query()
            ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
               ->where("$a.BranchSvc = 'PNP'")
               ->groupBy("$a.id")
               ->execute();
        }

        $getInitDate = TblLoanCsvFile::query()
            ->columns('initDate')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();
            $initDate = $getInitDate[0]->initDate;

        $getLastData = TblLoanCsvFile::query()
            ->columns('initDate, id_loan_csv_name')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();

        $initPayPeriod = $getLastData[0]->id_loan_csv_name;


        if($getQry){
            $nriVal                 = 0;
            $count                  = 0;
            $cnt                    = 0;
            $deduction_code         = "";
            $newRecordArray         = array();
            $info                   = array();
            $amount                 = 0;
            $collection_pay_period  = null;
            $deduCode               = "";
            $temp_info             = array();

            /* Data Declarations --------------------------*/
                $id                     = null ;
                $SapMemberNo            = null ;
                $full_name              = null ;
                $BranchSvc              = null ;
                $SvcStat                = null ;
                $LastName               = null ;
                $FirstName              = null ;
                $MiddleName             = null ;
                $QualifNam              = null ;
                $PINAcctNo              = null ;
                $T24MemberNo            = null ;
                $loanAmt                = null ;
                $MOA1                   = null ;
                $MOA2                   = null ;
                $lproNo                 = null ;
                $lnType                 = null ;
                $dateGrant              = null ;
                $maturity               = null ;
                $startDate1             = null ;
                $loanAppl               = null ;
                $PnPBillMod             = null ;
                $lnpTrate               = null ;
                $startDate2             = null ;
                $loanProc               = null ;
                $id_loan_csv_name       = null ;
                $TSAcctTy               = null ;
                $TSAccNo                = null ;
                $AccountName            = null ;
                $ID_Atm                 = null ;
                $PICOSNO                = null ;
                $REFERENCE              = null ;
                $CONTROL                = null ;
                $DATERCVD               = null ;
                $PIN                    = null ;
                $ATMCARDSTAT            = null ;
                $DATERELEASED           = null ;
                $PULLOUTREASON          = null ;
                $amount                 = null ;
                $collection_pay_period  = null ;
                $deduCode               = null ;

                foreach($getQry as $data){

                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $counter /count($getQry) * 100;
                    echo "PNP AC - Exporting file : ".number_format($percent,2)."%";

                /*Data declarations-----------------------------------------------------*/
                    $id             = $this->returnEmpty($data->id);
                    $SapMemberNo    = $this->returnEmpty($data->SapMemberNo);
                    $full_name      = $this->returnEmpty($data->full_name);
                    $BranchSvc      = $this->returnEmpty($data->BranchSvc);
                    $SvcStat        = $this->returnEmpty($data->SvcStat);
                    $LastName       = $this->returnEmpty($data->LastName);
                    $FirstName      = $this->returnEmpty($data->FirstName);
                    $MiddleName     = $this->returnEmpty($data->MiddleName);
                    $QualifNam      = $this->returnEmpty($data->QualifNam);
                    $PINAcctNo      = $this->returnEmpty($data->PINAcctNo);
                    $T24MemberNo    = $this->returnEmpty($data->T24MemberNo);


                    // echo $cnt." - 228849"."\n \r";
                    // echo $cnt."$T24MemberNo"."\n \r";
                //Retrieve data from Loan Billing
                    $getTblLoanCsvFile = TblLoanCsvFile::findFirst(array(
                        "columns"    => "loanAmt,MOA1,MOA2,lproNo,lnType,dateGrant,maturity,startDate1,loanAppl,
                                        PnPBillMod,lnpTrate,startDate2,loanProc,id_loan_csv_name",
                        "conditions" => "memberNo = '$SapMemberNo'",
                    ));

                    if ($getTblLoanCsvFile) {
                        $loanAmt        = $this->returnEmpty($getTblLoanCsvFile->loanAmt);
                        $MOA1           = number_format($getTblLoanCsvFile->MOA1, 2, '.', '');
                        $MOA2           = $this->returnEmpty($getTblLoanCsvFile->MOA2);
                        $lproNo         = str_replace("-", "", $getTblLoanCsvFile->lproNo);
                        $lnType         = $this->returnEmpty($getTblLoanCsvFile->lnType);
                        $dateGrant      = $this->returnEmpty($getTblLoanCsvFile->dateGrant);
                        $maturity       = $this->returnEmpty($getTblLoanCsvFile->maturity);
                        $startDate1     = $this->returnEmpty($getTblLoanCsvFile->startDate1);
                        $loanAppl       = $this->returnEmpty($getTblLoanCsvFile->loanAppl);
                        $PnPBillMod     = $this->returnEmpty($getTblLoanCsvFile->PnPBillMod);
                        $lnpTrate       = $this->returnEmpty($getTblLoanCsvFile->lnpTrate);
                        $startDate2     = $this->returnEmpty($getTblLoanCsvFile->startDate2);
                        $loanProc       = $this->returnEmpty($getTblLoanCsvFile->loanProc);
                        $id_loan_csv_name = $this->returnEmpty($getTblLoanCsvFile->id_loan_csv_name);
                    }

                  //Retrieve data from Member Account
                    $getTblMemberAccountFile = TblMemberAccountFile::findFirst(array(
                                "columns"    => "TSAcctTy,TSAccNo,AccountName",
                                "conditions" => "MemberNo = '".$SapMemberNo."'"));

                        if ($getTblMemberAccountFile) {
                            $TSAcctTy       = $this->returnEmpty($getTblMemberAccountFile->TSAcctTy);
                            $TSAccNo        = $this->returnEmpty($getTblMemberAccountFile->TSAccNo);
                            $AccountName    = $this->returnEmpty($getTblMemberAccountFile->AccountName);

                        }

                //Retrieve data from ATM List
                    $getTblAtmListFile = TblAtmListFile::findFirst(array(
                                "columns"    => "id,PICOSNO,REFERENCE,CONTROL,DATERCVD,PIN,ATMCARDSTAT,DATERELEASED,PULLOUTREASON",
                                "conditions" => "CLIENT LIKE '".$SapMemberNo."'"));
                    if($getTblAtmListFile) {
                        $ID_Atm         = $this->returnEmpty($getTblAtmListFile->id);
                        $PICOSNO        = $this->returnEmpty($getTblAtmListFile->PICOSNO);
                        $REFERENCE      = $this->returnEmpty($getTblAtmListFile->REFERENCE);
                        $CONTROL        = $this->returnEmpty($getTblAtmListFile->CONTROL);
                        $DATERCVD       = $this->returnEmpty($getTblAtmListFile->DATERCVD);
                        $PIN            = $this->returnEmpty($getTblAtmListFile->PIN);
                        $ATMCARDSTAT    = $this->returnEmpty($getTblAtmListFile->ATMCARDSTAT);
                        $DATERELEASED   = $this->returnEmpty($getTblAtmListFile->DATERELEASED);
                        $PULLOUTREASON  = $this->returnEmpty($getTblAtmListFile->PULLOUTREASON);
                    }

                //Retrieve data from Collection
                    $getTblCollectionPnpAc = TblCollectionPnpAc::findFirst(array(
                                "columns"    => "amount,collection_pay_period,deduCode,aging",
                                "conditions" => "pin_account_no = '$PINAcctNo'"));
                    if($getTblCollectionPnpAc) {
                        $amount                 = number_format($getTblCollectionPnpAc->amount, 2, '.', '');
                        $collection_pay_period  = $this->returnEmpty($getTblCollectionPnpAc->collection_pay_period);
                        $deduCode               = $this->returnEmpty($getTblCollectionPnpAc->deduCode);
                        $aging                  = $this->returnEmpty($getTblCollectionPnpAc->aging);
                    }


                $payPayPeriod = $this->getPayPeriod($id_loan_csv_name, trim($BranchSvc));
                        if($payPayPeriod != ""){
                            $GLOBALS['tempPayPeriod'] = $payPayPeriod;
                        }
                        else{
                            $payPayPeriod = $GLOBALS['tempPayPeriod'];
                        }

                $loanBillAmt = 0;
                $loanBillAmtNew = 0;
                $billAmnt = 0;
                $SvcStat = trim($SvcStat);

                $startDate = date('Y-m-d', strtotime($startDate1));
                $endDate = date('Y-m-d',  strtotime($startDate2));
                $dateGrant2 = date('Y-m-d', strtotime($dateGrant));
                $origContDate = $dateGrant == "" ? date('Ymd',strtotime($initDate)) : date("Ymd", strtotime($dateGrant));

                $amortLpro = (double)$MOA1;
                $amortCollection = (double)$amount;
                $group_service_status = $this->getGroupServiceStatus(trim($SvcStat));
                $group_loan_type = $this->getServiceStatusFromLnType(trim($lnType));
                $service_status_stat = $this->getStatusFromServiceStatus(trim($SvcStat));
                //check if new client
                $ifClientExistsLpro = TblLoanCsvFile::findFirst("lproNo = '$SapMemberNo'");
                if ($ifClientExistsLpro) {
                    $lproDateGrant = $ifClientExistsLpro->dateGrant;
                } else {
                    $lproDateGrant = null;
                }


                $ifClientExistsCollection = $h::findFirst("pin_account_no = '".trim($PINAcctNo)."'");
                if ($ifClientExistsCollection) {
                    $dedCodeCollection = $ifClientExistsCollection->deduCode;
                } else {
                    $dedCodeCollection = "";
                }

                //Check billmode, billremarks and billpage values
                $billParams = $this->getBillParams($PnPBillMod,$amortLpro,$lnpTrate);
                $billMode = $billParams["billMode"];
                $billRemarks = $billParams["billRemarks"];
                $billPage = $billParams["billPage"];
                $nriVal = $billParams["nriVal"];

                if($PnPBillMod === null || $PnPBillMod === ""){
                    $getBillMode = $this->getBillMode($group_service_status,$deduCode,$BranchSvc);
                    $PnPBillMod = $getBillMode["bill_mode_sap"];

                    $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                    $billMode = $newBillMode["bill_mode"];

                 }

                if($PnPBillMod == "PBM01" || $PnPBillMod == "PBM02") {
                    $billMode = "Bill to Loan";
                    if(($startDate > $dateGrant && $dateGrant < $endDate) && empty($ifClientExistsCollection)){
                        $billRemarks = "NEW LOAN";
                    }
                    $nriVal = $lnpTrate;
                } else if ($PnPBillMod == "PBM03") {
                    $billMode = "Bill to CAPCON";
                    $billRemarks = "CAPITAL.CONTRIBUTION";
                    $nriVal = 0;
                } else if ($PnPBillMod == "PBM04") {
                    $billMode = "Bill to Savings";
                    $billRemarks = "SAVINGS.CONTRIBUTION";
                    $nriVal = 0;
                } else if ($PnPBillMod == "PBM06") {
                    $billMode = "Bill to POS";
                    $nriVal = 0;
                } else if ($PnPBillMod == "PBM05" || $PnPBillMod == "PBM07" ) {
                    $billMode = "Bill to CASA";
                    $billRemarks = $PnPBillMod == "PBM05" ? "CASA.CONTRIBUTION" : "";
                    $nriVal = 0;
                }

                if($MOA1 || $amount){
                    $billRemarks = "STOPPAGE";
                }
                if(!empty($ifClientExistsLpro) && !empty($ifClientExistsCollection)){
                    $billRemarks = "EXISTING RENEWAL";
                }

                $dateGeneratedStr = explode('-', $dateGrant);
                $dateGeneratedStr[2] = '26';
                $dateGeneratedStr = implode('-', $dateGeneratedStr);
                $dateGenerated =  date('Y-m-d', strtotime($dateGeneratedStr));
                    if(($dateGrant <= $dateGenerated) && ($amortLpro >= $amortCollection)) {
                        $billRemarks = "EXISTING REBILL";
                    }
                    if(($dateGrant < $dateGenerated) && (empty($ifClientExistsCollection))) {
                        $billRemarks = "REBILL";
                    }
                    if(($dateGrant < $dateGenerated) && ($amortLpro <= $amortLpro )) {
                        $billRemarks = "EXISTING";
                    }

                 $amountCollect = $amount == "" ? 0 : $amount;

                 $newClient = $this->getReferenceSdlis($billingType,$group_service_status,trim($PINAcctNo)); //"20020302002"
                 $loanBillAmtNewSDLIS = $newClient["amortization"];
                 $deduction_codeSDLIS = $newClient["deductionCode"];
                 $dateGrantedSdlis = $newClient["dateGranted"];

                 $branchSvc = trim($BranchSvc);

                $deduction_code = $this->getDeductionCode($group_service_status,$PnPBillMod,$lnType,$branchSvc);
                 $loanBillAmtNew = $MOA1 == null ? $amount : $MOA1;
                $startDate1 = $startDate1 == null ? "" : date('Ymd', strtotime($startDate1));
                 //If record is not available in LPRO Billing - this condition applies.
                 //If record is not available in LPRO Billing - this condition applies.
                    if ($getTblLoanCsvFile == null) {
                         $payPayPeriod = $this->getPayPeriod(trim($initPayPeriod), trim($branchSvc));
                        //PH.LOAN.ORIG.ID
                            if ($billMode == "Bill to Loan") {
                                $lproNo = "";
                            } else if ($billMode == "Bill to CAPCON" || $billMode == "Bill to Savings" || $billMode == "Bill to CASA") {
                                $lproNo = str_replace("-", "", $AccountName);
                            }


                        //PH.ORIG.DEDNCODE
                            $deduction_code = $deduCode;

                        //PH.ORIG.NRI
                            $nriVal = $aging;

                    }


                    //Adding of New line (BILL AMOUNT PROCESS)
                    $newLine =  $this->processBillAmount(
                        $billingType,
                        $PnPBillMod,
                        $MOA1,
                        $amortCollection,
                        $amortLpro,
                        $SapMemberNo,
                        $TSAcctTy,
                        $BranchSvc,
                        $PINAcctNo,
                        $T24MemberNo,
                        $LastName,
                        $FirstName,
                        $MiddleName,
                        $QualifNam,
                        $SvcStat,
                        $billAmnt,
                        $lnType,
                        $dateGrant,
                        $billMode,
                        $dedCodeCollection,
                        $nriVal,
                        $billRemarks,
                        $billPage,
                        $amount,
                        $collection_pay_period,
                        $newRecordArray,
                        $full_name,
                        $loanBillAmtNewSDLIS,
                        $deduction_codeSDLIS,
                        $lproNo,
                        $payPayPeriod,
                        $loanProc,
                        $deduction_code,
                        $filter,
                        $AccountName,
                        $startDate1);


                  if((trim($lnType) != "BL") && (trim($lnType) != "NL")) {
                        if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {

                                $temp_info = array(
                                    'up_company' => 'PH0010002', //UPLOAD.COMPANY
                                    'BranchSvc' => $this->returnEmpty($BranchSvc), //PH.BRANCH.SVC
                                    'PayPeriod' => $this->returnEmpty($payPayPeriod), //PH.PAYPERIOD
                                    'PIN' => $this->returnEmpty($PINAcctNo),
                                    'FullName' => '',
                                    'CustomerCode' => $this->returnEmpty($T24MemberNo),
                                    'LastName' => $this->returnEmpty($LastName),
                                    'FirstName' => $this->returnEmpty($FirstName),
                                    'MiddleName' => $this->returnEmpty($MiddleName),
                                    'QualifNam'  => $this->returnEmpty($QualifNam), //PH.QUAL.NAME
                                    'MemberStat' => $this->returnEmpty($SvcStat), //PH.Service Status
                                    'BillLoanAmt'    => $this->returnEmpty($loanBillAmtNew),
                                    'CapconBillAmt' => "",
                                    'CasaBillAmt' => "",
                                    'PsaBillAmt' => "",
                                    'TotalBillAmt' => '',
                                    'LoanBillAmt' => $this->returnEmpty($loanBillAmtNew),
                                    'LoanOrigId'   => $this->returnEmpty($lproNo),
                                    'LoanType'      => '',//$this->returnEmpty($lnType),
                                    'AppType'       => '',
                                    'MaturityDate' => '',
                                    'OrigContDate' => $this->returnEmpty($origContDate),
                                    'UpdContDate' => '',
                                    'LoanTerm' => '',
                                    'OrigContAmt' => '',
                                    'LoanStat' => $this->returnEmpty($loanProc),
                                    'StartAmrtDate' => $this->returnEmpty($startDate1),
                                    'BillTransType' => '',
                                    'BillTransStat' => '',
                                    'BillMode'       => $this->returnEmpty($billMode),
                                    'OrigDedNCode' => $this->returnEmpty(trim($deduction_code)),
                                    'UpdDedNCode' => '',
                                    'OrigNri'   => $this->returnEmpty(trim($nriVal)),
                                    'UpdtNri'  => '',
                                    'Remarks'    => $this->returnEmpty($billRemarks),
                                    'StopPage' => $this->returnEmpty($billPage),
                                    'AtmCardNo' => '',
                                    'FullName2' => '',
                                    'PensAcctNo' => '',
                                    'PayMtStats' => '',
                                    'StopDedNCode' => '',
                                    'IncldBilInd' => "YES",
                                    '1stLoadAmt' => "",
                                    'CollectionAmount' => $this->returnEmpty(number_format($amountCollect, 2, '.', '')),
                                    'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                                );

                            /* Filter -------------------------------- */
                                if($filter == 'FULLY PAID'){
                                    $ext = "_Fully Paid";

                                    if (trim($group_loan_type) == "ACTIVE" && ($lproNo == null || $lproNo == "") AND ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                         $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NO ACCOUNT'){
                                    $ext = "_No Account";

                                    if (trim($group_loan_type) == "ACTIVE" && ($AccountName == null || $AccountName == "") AND
                                        ($PnPBillMod == "PBM03" OR $PnPBillMod == "PBM04" OR $PnPBillMod == "PBM05" OR $PnPBillMod == "PBM06" OR $PnPBillMod == "PBM07")) {
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                    }
                                } else if($filter == 'OPTIONAL'){
                                    $ext = "_Optional";

                                     if (trim($group_loan_type) == "ACTIVE" && $service_status_stat == "PENSION") {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NORMAL') {
                                    $ext = "_Normal";

                                   if  (trim($group_loan_type) == "ACTIVE" && ($service_status_stat == "ACTIVE") && ($lproNo != null || $lproNo != "") && ($AccountName != null || $AccountName != "")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                    if (trim($group_loan_type) == "ACTIVE") {
                                       $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                           /* end of filter --------------------------*/

                                    $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                                    foreach($global_newline_array as $newLine){
                                            array_push($info,$newLine);
                                            fputcsv($fp, $newLine);
                                            $result = html_entity_decode(implode("|",$newLine)."\r\n");
                                            fwrite($txt,$result);
                                    }

                            // array_push($info,$temp_info);
                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                            // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                            // fputcsv($fp, $temp_info );

                        } else {
                            $temp_info = array(
                                'up_company' => 'PH0010002', //UPLOAD.COMPANY
                                'BranchSvc' => $this->returnEmpty($BranchSvc), //PH.BRANCH.SVC
                                'PayPeriod' => $this->returnEmpty($payPayPeriod), //PH.PAYPERIOD
                                'PIN' => $this->returnEmpty($PINAcctNo),
                                'FullName' => '',
                                'CustomerCode' => $this->returnEmpty($T24MemberNo),
                                'LastName' => $this->returnEmpty($LastName),
                                'FirstName' => $this->returnEmpty($FirstName),
                                'MiddleName' => $this->returnEmpty($MiddleName),
                                'QualifNam'  => $this->returnEmpty($QualifNam), //PH.QUAL.NAME
                                'MemberStat' => $this->returnEmpty($SvcStat), //PH.Service Status
                                'BillLoanAmt'    => $this->returnEmpty($loanBillAmtNew),
                                'CapconBillAmt' => "",
                                'CasaBillAmt' => "",
                                'PsaBillAmt' => "",
                                'TotalBillAmt' => '',
                                'LoanBillAmt' => $this->returnEmpty($loanBillAmtNew),
                                'LoanOrigId'   => $this->returnEmpty($lproNo),
                                'LoanType'      => '',//$this->returnEmpty($lnType),
                                'AppType'       => '',
                                'MaturityDate' => '',
                                'OrigContDate' => $this->returnEmpty($origContDate),//date('Y-m-d', strtotime($data->dateGrant)),
                                'UpdContDate' => '',
                                'LoanTerm' => '',
                                'OrigContAmt' => '',
                                'LoanStat' => $this->returnEmpty($loanProc),
                                'StartAmrtDate' => $this->returnEmpty($startDate1),
                                'BillTransType' => '',
                                'BillTransStat' => '',
                                'BillMode'       => $this->returnEmpty($billMode),
                                'OrigDedNCode' => $this->returnEmpty(trim($deduction_code)),
                                'UpdDedNCode' => '',
                                'OrigNri'   => $this->returnEmpty(trim($nriVal)),
                                'UpdtNri'  => '',
                                'Remarks'    => $this->returnEmpty($billRemarks),
                                'StopPage' => $this->returnEmpty($billPage),
                                'AtmCardNo' => '',
                                'FullName2' => '',
                                'PensAcctNo' => '',
                                'PayMtStats' => '',
                                'StopDedNCode' => '',
                                'IncldBilInd' => "YES",
                                '1stLoadAmt' => "",
                                'CollectionAmount' => $this->returnEmpty(number_format($amountCollect, 2, '.', '')),
                                'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                            );

                            /* Filter -------------------------------- */
                                 if($filter == 'FULLY PAID'){
                                    $ext = "_Fully Paid";

                                    if (trim($group_loan_type) == "ACTIVE" && ($lproNo == null || $lproNo == "") AND ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NO ACCOUNT'){
                                    $ext = "_No Account";

                                    if (trim($group_loan_type) == "ACTIVE" && ($AccountName == null || $AccountName == "") AND
                                        ($PnPBillMod == "PBM03" OR $PnPBillMod == "PBM04" OR $PnPBillMod == "PBM05" OR $PnPBillMod == "PBM06" OR $PnPBillMod == "PBM07")) {
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                    }
                                } else if($filter == 'OPTIONAL'){
                                    $ext = "_Optional";

                                     if (trim($group_loan_type) == "ACTIVE" && $service_status_stat == "PENSION") {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NORMAL') {
                                    $ext = "_Normal";

                                   if  (trim($group_loan_type) == "ACTIVE" && ($service_status_stat == "ACTIVE") && ($lproNo != null || $lproNo != "") && ($AccountName != null || $AccountName != "")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                    if (trim($group_loan_type) == "ACTIVE") {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                              /* end of filter --------------------------*/


                                //   array_push($info,$temp_info);
                                //         $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                //         fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                //         fputcsv($fp, $temp_info );

                        }
                   }

                             $global_newline_array = array();
                             $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                             $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;
                             $counter++;

            }

            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
            echo $fnlRes;
            exit;
        }
    }

    public function exportBillingPnpReAction(){
       ini_set('memory_limit', '-1');

       $rootPath = "/var/www/html/psslai/";
    //    $rootPath = "/var/www/psslai/";
       $exportFileName = "export/BILLING_PNP-RE_ao";
       $logFileName = "export/fileLog_tempPNPRE.log";
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $counter =0;
       $date = date("YmdHis");

       $path2 = $rootPath."public/".$logFileName;
       $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";

       $exportCsv1 = $exportFileName.$date.'.csv';
       $exportTxt2 = $exportFileName.$date.'.txt';

            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $c = "TblAtmListFile";
            $d = "TblLoanCsvFile"; //Loan Billing
            $e = "TblLoanAtmFile"; //Loan Atm
            $f = "TblDeductionCode"; //Collection
            $g = "TblBillMode";
            $h = "TblCollectionPnpRe";
            $ss = "TblServiceStatus";
            $tbl_branchSvc = "TblBos";
            $tbl_loanType = "TblLoanType";
            $billingType = "PnpRe";
            $ext = "";
            $cod = "";

            if($filter == 'FULLY PAID'){
                $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
                $ext = "_Fully Paid";
            } else if($filter == 'NO ACCOUNT'){
                $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
                $ext = "_No Account";
            } else if($filter == 'OPTIONAL'){
                $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $ext = "_Optional";
            } else if($filter == 'NORMAL') {
                $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                    ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
                $ext = "_Normal";
            }

        defined('APP_PATH') || define('APP_PATH', realpath('.'));
        $date = date("YmdHis");
        $exportCsv = 'export/BILLING_PNP-RE_ao'.$date.$ext.'.csv';
        $exportTxt = 'export/BILLING_PNP-RE_ao'.$date.$ext.'.txt';
        $exportCsv1 = 'export/BILLING_PNP-RE_ao'.$date.$ext.'.csv';
        $exportTxt2 = 'export/BILLING_PNP-RE_ao'.$date.$ext.'.txt';
        $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

        $fp = fopen($rootPath.'public/export/BILLING_PNP-RE_ao'.$date.$ext.'.csv', 'w');
          $headerCSV = ['UPLOAD.COMPANY',
                'PH.BRANCH.SVC',
                'PH.PAY.PERIOD',
                'PH.PIN.NO',
                'PH.FULLNAME1',
                'CUSTOMER.CODE',
                'PH.LAST.NAME',
                'PH.FIRST.NAME',
                'PH.MIDDLE.NAME',
                'PH.QUAL.NAME',
                'PH.SERVICE.STATUS',
                'PH.LOAN.BILL.AMT',
                'PH.CAPCON.BILL.AMT',
                'PH.CASA.BILL.AMT',
                'PH.PSA.BILL.AMT',
                'PH.TOTAL.BILL.AMT',
                'PH.BILL.AMT',
                'PH.LOAN.ORIG.ID',
                'PH.LOAN.TYPE',
                'PH.APP.TYPE',
                'PH.MATURITY.DATE',
                'PH.ORIG.CONT.DATE',
                'PH.UPD.CONT.DATE',
                'PH.LOAN.TERM',
                'PH.ORIG.CONT.AMT',
                'PH.LOAN.STAT',
                'PH.START.AMRT.DATE',
                'PH.BILL.TRANS.TYPE',
                'PH.BIL.TRANS.STAT',
                'PH.BILL.MODE',
                'PH.ORIG.DEDNCODE',
                'PH.UPD.DEDNCODE',
                'PH.ORIG.NRI',
                'PH.UPDATED.NRI',
                'PH.BILL.REMARKS',
                'PH.BILL.STOPPAGE',
                'PH.ATM.CARD.NO',
                'PH.FULLNAME2',
                'PH.PENS.ACCT.NO',
                'PH.PAYMT.STAT',
                'PH.STOP.DEDN.CODE',
                'PH.INCLD.BIL.IND',
                'PH.1ST.LOAD.AMT',
                'AMOUNT.COLLECT',
                'PAY.PERIOD.COLLECT'];
                fputcsv($fp, $headerCSV );


            if($cod) {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
                ->where("$a.BranchSvc = 'PNP' $aa")
                ->groupBy("$a.id")
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                    $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
                   ->where("$a.BranchSvc = 'PNP'")
                //    ->limit($count,$offset)
                   ->groupBy("$a.id")
                   ->execute();
            }

            $getInitDate = TblLoanCsvFile::query()
            ->columns('initDate')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();
            $initDate = $getInitDate[0]->initDate;

            $getLastData = TblLoanCsvFile::query()
            ->columns('initDate, id_loan_csv_name')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();

            $initPayPeriod = $getLastData[0]->id_loan_csv_name;

            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info = array();
                $temp_info = array();
                // $temp_info              = array();

                /* Data Declarations ----------------------------------- */
                    $id                     = null;
                    $SapMemberNo            = null;
                    $full_name              = null;
                    $BranchSvc              = null;
                    $SvcStat                = null;
                    $LastName               = null;
                    $FirstName              = null;
                    $MiddleName             = null;
                    $QualifNam              = null;
                    $PINAcctNo              = null;
                    $T24MemberNo            = null;
                    $loanAmt                = null;
                    $MOA1                   = null;
                    $MOA2                   = null;
                    $lproNo                 = null;
                    $lnType                 = null;
                    $dateGrant              = null;
                    $maturity               = null;
                    $startDate1             = null;
                    $loanAppl               = null;
                    $PnPBillMod             = null;
                    $lnpTrate               = null;
                    $startDate2             = null;
                    $loanProc               = null;
                    $id_loan_csv_name       = null;
                    $TSAcctTy               = null;
                    $TSAccNo                = null;
                    $AccountName            = null;
                    $ID_Atm                 = null;
                    $PICOSNO                = null;
                    $REFERENCE              = null;
                    $CONTROL                = null;
                    $DATERCVD               = null;
                    $PIN                    = null;
                    $ATMCARDSTAT            = null;
                    $DATERELEASED           = null;
                    $PULLOUTREASON          = null;
                    $monthly_amort          = null;
                    $collection_pay_period  = null;
                    $deduction_code         = null;

                    $billAmnt = null;
                    $amount = null;


        foreach($getQry as $data){

                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $counter /count($getQry) * 100;
                    echo " PNP RE - Exporting file : ".number_format($percent,2)."%";

                    $id             = $data->id;
                    $SapMemberNo    = $data->SapMemberNo;
                    $full_name      = $data->full_name;
                    $BranchSvc      = $data->BranchSvc;
                    $SvcStat        = $data->SvcStat;
                    $LastName       = $data->LastName;
                    $FirstName      = $data->FirstName;
                    $MiddleName     = $data->MiddleName;
                    $QualifNam      = $data->QualifNam;
                    $PINAcctNo      = $data->PINAcctNo;
                    $T24MemberNo    = $data->T24MemberNo;

                    $getTblLoanCsvFile = TblLoanCsvFile::findFirst(array(
                                "columns"    => "loanAmt,MOA1,MOA2,lproNo,lnType,dateGrant,maturity,startDate1,loanAppl,
                                                 PnPBillMod,lnpTrate,startDate2,loanProc,id_loan_csv_name",
                                "conditions" => "memberNo = '".$SapMemberNo."'"));
                    if($getTblLoanCsvFile) {
                        $loanAmt     = $getTblLoanCsvFile->loanAmt;
                         $MOA1        = number_format($getTblLoanCsvFile->MOA1, 2, '.', '');
                        $MOA2        = $getTblLoanCsvFile->MOA2;
                        $lproNo      = str_replace("-", "", $getTblLoanCsvFile->lproNo);
                        $lnType      = $getTblLoanCsvFile->lnType;
                        $dateGrant   = $getTblLoanCsvFile->dateGrant;
                        $maturity    = $getTblLoanCsvFile->maturity;
                        $startDate1  = $getTblLoanCsvFile->startDate1;
                        $loanAppl    = $getTblLoanCsvFile->loanAppl;
                        $PnPBillMod  = $getTblLoanCsvFile->PnPBillMod;
                        $lnpTrate    = $getTblLoanCsvFile->lnpTrate;
                        $startDate2  = $getTblLoanCsvFile->startDate2;
                        $loanProc    = $getTblLoanCsvFile->loanProc;
                        $id_loan_csv_name = $getTblLoanCsvFile->id_loan_csv_name;
                    }

                    $getTblMemberAccountFile = TblMemberAccountFile::findFirst(array(
                                "columns"    => "TSAcctTy,TSAccNo,AccountName",
                                "conditions" => "MemberNo = '".$SapMemberNo."'"));
                    if($getTblMemberAccountFile) {
                        $TSAcctTy   = $getTblMemberAccountFile->TSAcctTy;
                        $TSAccNo    = $getTblMemberAccountFile->TSAccNo;
                        $AccountName        = $getTblMemberAccountFile->AccountName;
                    }

                    $getTblAtmListFile = TblAtmListFile::findFirst(array(
                                "columns"    => "id,PICOSNO,REFERENCE,CONTROL,DATERCVD,PIN,ATMCARDSTAT,DATERELEASED,PULLOUTREASON",
                                "conditions" => "CLIENT LIKE '".$SapMemberNo."'"));
                     if($getTblAtmListFile) {
                        $ID_Atm         = $getTblAtmListFile->id;
                        $PICOSNO        = $getTblAtmListFile->PICOSNO;
                        $REFERENCE      = $getTblAtmListFile->REFERENCE;
                        $CONTROL        = $getTblAtmListFile->CONTROL;
                        $DATERCVD       = $getTblAtmListFile->DATERCVD;
                        $PIN            = $getTblAtmListFile->PIN;
                        $ATMCARDSTAT    = $getTblAtmListFile->ATMCARDSTAT;
                        $DATERELEASED   = $getTblAtmListFile->DATERELEASED;
                        $PULLOUTREASON  = $getTblAtmListFile->PULLOUTREASON;
                     }

                    $getTblCollectionPnpRe = TblCollectionPnpRe::findFirst(array(
                                "columns"    => "monthly_amort,collection_pay_period,deduction_code,nri",
                                "conditions" => "pan_account_no = '$PINAcctNo'"));
                    if($getTblCollectionPnpRe) {
                        $monthly_amort          = number_format($getTblCollectionPnpRe->monthly_amort, 2, '.', '');
                        $collection_pay_period  = $getTblCollectionPnpRe->collection_pay_period;
                        $deduction_code         = $getTblCollectionPnpRe->deduction_code;
                        $nri                    = $getTblCollectionPnpRe->nri;
                    }


                    // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($id_loan_csv_name, trim($BranchSvc));
                     if($payPayPeriod != ""){
                            $GLOBALS['tempPayPeriod'] = $payPayPeriod;
                     }
                      else {
                            $payPayPeriod = $GLOBALS['tempPayPeriod'];
                     }



                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billAmnt == 0;
                    $SvcStat = trim($data->SvcStat);

                    $startDate = date('Y-m-d', strtotime($startDate1));
                    $endDate = date('Y-m-d',  strtotime($startDate2));
                    $dateGrant2 = date('Ymd', strtotime($dateGrant));
                    $origContDate = $dateGrant == "" ? date('Ymd',strtotime($initDate)) : date("Ymd", strtotime($dateGrant));

                    $amortLpro = (double)$MOA1;
                    $amortCollection = (double)$monthly_amort;
                    $dateGranted = $dateGrant;

                    $group_service_status = $this->getGroupServiceStatus($SvcStat);
                    $group_loan_type = $this->getServiceStatusFromLnType($lnType);
                    $service_status_stat = $this->getStatusFromServiceStatus(trim($SvcStat));


                    $ifClientExistsLpro = TblLoanCsvFile::findFirst("memberNo = '$SapMemberNo'");
                    $lproDateGrant = $ifClientExistsLpro == null ? null : $ifClientExistsLpro->dateGrant;

                    $ifClientExistsCollection = $h::findFirst("pan_account_no = '$PINAcctNo'");
                    $dedCodeCollection = $ifClientExistsCollection == null ? null : $ifClientExistsCollection->deduction_code;
                    $dedCodeCollection = explode(' ',trim($dedCodeCollection));
                    $dedCodeCollection = $dedCodeCollection[0];


                    //if record in loan billing does not exists.
                    if($PnPBillMod === null || $PnPBillMod === ""){
                        $getBillMode = $this->getBillMode($group_service_status,$deduCode,$BranchSvc);
                        $PnPBillMod = $getBillMode["bill_mode_sap"];

                        $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                        $billMode = $newBillMode["bill_mode"];
                    }

                    //FOR BILLMODE
                    $getBillParams = $this->getBillParams($PnPBillMod,$amortLpro,0);
                    $billMode = $getBillParams["billMode"];

                    $getBillRemarks = $this->getBillRemarks($PnPBillMod,$dateGranted,$startDate1,$startDate2,$ifClientExistsCollection,$ifClientExistsLpro,$amortLpro,$amortCollection);
                    $billRemarks = $getBillParams["billRemarks"];
                    $billPage = $getBillParams["billPage"];

                    $amountCollect = $monthly_amort == "" ? 0 : $monthly_amort;
                    $newClient = $this->getReferenceSdlis($billingType,$group_service_status,trim($PINAcctNo)); //"20020302002"
                    $loanBillAmtNewSDLIS = $newClient["amortization"];
                    $deduction_codeSDLIS = $newClient["deductionCode"];
                    $dateGranted = $newClient["dateGranted"];

                    $branchSvc = trim($BranchSvc);
                    $deduction_code = $this->getDeductionCode($group_service_status,$PnPBillMod,$lnType,$branchSvc);
                    $loanBillAmtNew = $MOA1 == null ? $monthly_amort : $MOA1;
                    $nriVal = "";
                    $startDate1 = $startDate1 == null ? "" : date('Ymd', strtotime($startDate1));

                  //If record is not available in LPRO Billing - this condition applies.
                    if ($getTblLoanCsvFile == null) {
                          $payPayPeriod = $this->getPayPeriod(trim($initPayPeriod), trim($branchSvc));
                          //PH.ORIG.DEDNCODE
                            if ($deduction_code == null || $deduction_code == "") {
                                $deduction_code = $dedCodeCollection;

                            }
                         //PH.BILL.MODE
                            $newBillMode = $this->getBillMode($group_service_status,trim($deduction_code),$branchSvc);
                            $billMode = $newBillMode["bill_mode"];

                        //PH.LOAN.ORIG.ID
                            if ($lproNo == null || $lproNo == "") {
                                    if ($billMode == "Bill to Loan") {
                                        $lproNo = "";
                                    } else if ($billMode == "Bill to CAPCON" || $billMode == "Bill to Savings" || $billMode == "Bill to CASA") {
                                        $lproNo = str_replace("-", "", $AccountName);
                                    }
                            }
                        //PH.ORIG.NRI
                            $nriVal = $nri;
                    }
                   
                    //Adding of New line (BILL AMOUNT PROCESS)
                    $newLine =  $this->processBillAmountGrpSDLIS( // PnpRe, BfpAc, BfpRe
                        "PNPRE",
                        $PnPBillMod,
                        $MOA1,
                        $amortCollection,
                        $amortLpro,
                        $SapMemberNo,
                        $TSAcctTy,
                        $BranchSvc,
                        $PINAcctNo,
                        $T24MemberNo,
                        $LastName,
                        $FirstName,
                        $MiddleName,
                        $QualifNam,
                        $SvcStat,
                        $billAmnt,
                        $lnType,
                        $dateGrant == "" ? "" : date("Ymd",strtotime($dateGrant)),
                        $billMode,
                        $dedCodeCollection,
                        $nriVal,
                        $billRemarks,
                        $billPage,
                        $amount,
                        $collection_pay_period,
                        $newRecordArray,
                        $full_name,
                        $newRecordArray,
                        $loanBillAmtNewSDLIS,
                        $deduction_codeSDLIS,
                        $lproNo,
                        $amountCollect,
                        $payPayPeriod,
                        $loanProc,
                        $deduction_code,
                        $filter,
                        $AccountName,
                        $startDate1);

              if((trim($lnType) != "BL") && (trim($lnType) != "NL")) {
                if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {
                   
                      $temp_info= array(
                        'up_company' =>'PH0010002',
                        'BranchSvc' =>$this->returnEmpty($BranchSvc),
                        'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                        'PIN' => $this->returnEmpty($PINAcctNo),
                        'full_name' =>'',
                        'MemberStat' => $this->returnEmpty($T24MemberNo),
                        'LastName' => $this->returnEmpty($LastName),
                        'FirstName' => $this->returnEmpty($FirstName),
                        'MiddleName' => $this->returnEmpty($MiddleName),
                        'QualifNam' => $this->returnEmpty($QualifNam),
                        'SvcStat' => $this->returnEmpty($SvcStat),
                        'loan_bill_amount' => $this->returnEmpty(number_format($MOA1, 2,".","")),
                        'capcon_bill_amount' =>"",
                        'casa_bill_amount' =>"",
                        'psa_bill_amount' =>"",
                        'total_bill_amnt' =>"",
                        'bill_amt' =>$this->returnEmpty(number_format($loanBillAmtNew, 2,".","")),
                        'lproNo' => $this->returnEmpty($lproNo),
                        'lnType' =>"",
                        'loanAppl' =>"",
                        'maturityDate' =>"",
                        'dateGrant' =>$this->returnEmpty($dateGrant == "" ? "" : date("Ymd",strtotime($dateGrant))),
                        'updContDate' =>"",
                        'loanTerm' =>"",
                        'origContDate' => $this->returnEmpty($origContDate),
                        'loanStat' =>$this->returnEmpty($loanProc),
                        'startAmrtDate' =>$this->returnEmpty($startDate1),
                        'billTransType' =>"",
                        'bilTransStat' =>"",
                        'billMode' =>$this->returnEmpty($billMode),
                        'orgDeDNCode' =>$this->returnEmpty(trim($deduction_code)),
                        'updtDeDNCode' =>"",
                        'lnpTrate' =>$this->returnEmpty(trim($nriVal)),
                        'uptNri' =>"",
                        'Remarks' =>$this->returnEmpty($billRemarks),
                        'stop_page' =>$this->returnEmpty($billPage),
                        'atmCardNo' =>"",
                        'fullName2' =>"",
                        'pensAcctNo' =>"",
                        'payMtStats' =>"",
                        'stopDedNCode' =>"",
                        'incldBilInd' =>"YES",
                        '1stLoadAmt' =>"",
                        'collection_amount' =>$this->returnEmpty(number_format($amountCollect, 2,".","")),
                        'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                        );


                            /* Filter -------------------------------- */
                                if($filter == 'FULLY PAID'){
                                    $ext = "_Fully Paid";

                                   if (trim($group_loan_type) == "PENSION" &&
                                        ($lproNo == null || $lproNo == "") && ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NO ACCOUNT'){
                                    $ext = "_No Account";

                                     if (trim($group_loan_type) == "PENSION" &&
                                        ($AccountName == null || $AccountName == "") &&
                                        ($PnPBillMod == "PBM03" || $PnPBillMod == "PBM04" || $PnPBillMod == "PBM05" || $PnPBillMod == "PBM06" || $PnPBillMod == "PBM07")) {
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                    }
                                }
                                // else if($filter == 'OPTIONAL'){
                                //     $ext = "_Optional";

                                //     if ($lnType != "PL" AND $lnType != "AP" AND $lnType != "OP") {
                                //         $info [] = $temp_info;
                                //         array_push($info,$temp_info);
                                //         $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                //         fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                //         fputcsv($fp, $temp_info );
                                //     }
                                // }
                                else if($filter == 'NORMAL') {
                                    $ext = "_Normal";

                                  if  (($service_status_stat == "PENSION") &&
                                        ($lproNo != null || $lproNo != "") && ($AccountName != null || $AccountName != "")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                      if (trim($group_loan_type) == "PENSION") {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                     }
                                }
                           /* end of filter --------------------------*/
                     if (trim($group_loan_type) == "PENSION") {
                        $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                        foreach($global_newline_array as $newLine){
                                array_push($info,$newLine);
                                fputcsv($fp, $newLine );
                                $result = html_entity_decode(implode("|",$newLine)."\r\n");
                                fwrite($txt,$result);
                        }
                     }
                        
                        // array_push($info,$temp_info);
                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                        // fputcsv($fp, $temp_info );

                 } else {
                     $temp_info =  array(
                        'up_company' =>'PH0010002',
                        'BranchSvc' =>$this->returnEmpty($BranchSvc),
                        'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                        'PIN' => $this->returnEmpty($PINAcctNo),
                        'full_name' =>'',
                        'MemberStat' => $this->returnEmpty($T24MemberNo),
                        'LastName' => $this->returnEmpty($LastName),
                        'FirstName' => $this->returnEmpty($FirstName),
                        'MiddleName' => $this->returnEmpty($MiddleName),
                        'QualifNam' => $this->returnEmpty($QualifNam),
                        'SvcStat' => $this->returnEmpty($SvcStat),
                        'loan_bill_amount' => $this->returnEmpty(number_format($MOA1, 2,".","")),
                        'capcon_bill_amount' =>"",
                        'casa_bill_amount' =>"",
                        'psa_bill_amount' =>"",
                        'total_bill_amnt' =>"",
                        'bill_amt' =>$this->returnEmpty(number_format($loanBillAmtNew, 2,".","")),
                        'lproNo' => $this->returnEmpty($lproNo),
                        'lnType' =>"",
                        'loanAppl' =>"",
                        'maturityDate' =>"",
                        'dateGrant' =>$this->returnEmpty($dateGrant == "" ? "" : date("Ymd",strtotime($dateGrant))),
                        'updContDate' =>"",
                        'loanTerm' =>"",
                        'origContDate' =>$this->returnEmpty($origContDate),
                        'loanStat' =>$this->returnEmpty($loanProc),
                        'startAmrtDate' =>$this->returnEmpty($startDate1),
                        'billTransType' =>"",
                        'bilTransStat' =>"",
                        'billMode' =>$this->returnEmpty($billMode),
                        'orgDeDNCode' =>$this->returnEmpty(trim($deduction_code)),
                        'updtDeDNCode' =>"",
                        'lnpTrate' =>$this->returnEmpty(trim($nriVal)),
                        'uptNri' =>"",
                        'Remarks' =>$this->returnEmpty($billRemarks),
                        'stop_page' =>$this->returnEmpty($billPage),
                        'atmCardNo' =>"",
                        'fullName2' =>"",
                        'pensAcctNo' =>"",
                        'payMtStats' =>"",
                        'stopDedNCode' =>"",
                        'incldBilInd' =>"YES",
                        '1stLoadAmt' =>"",
                        'collection_amount' =>$this->returnEmpty(number_format($amountCollect, 2,".","")),
                        'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                     );


                             /* Filter -------------------------------- */
                                if($filter == 'FULLY PAID'){
                                    $ext = "_Fully Paid";

                                   if (trim($group_loan_type) == "PENSION" &&
                                        ($lproNo == null || $lproNo == "") && ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else if($filter == 'NO ACCOUNT'){
                                    $ext = "_No Account";

                                     if (trim($group_loan_type) == "PENSION" &&
                                        ($AccountName == null || $AccountName == "") &&
                                        ($PnPBillMod == "PBM03" || $PnPBillMod == "PBM04" || $PnPBillMod == "PBM05" || $PnPBillMod == "PBM06" || $PnPBillMod == "PBM07")) {
                                            $info [] = $temp_info;
                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                    }
                                }
                                // else if($filter == 'OPTIONAL'){
                                //     $ext = "_Optional";

                                //     if ($lnType != "PL" AND $lnType != "AP" AND $lnType != "OP") {
                                //         $info [] = $temp_info;
                                //         array_push($info,$temp_info);
                                //         $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                //         fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                //         fputcsv($fp, $temp_info );
                                //     }
                                // }
                                else if($filter == 'NORMAL') {
                                    $ext = "_Normal";

                                  if  (($service_status_stat == "PENSION") &&
                                        ($lproNo != null || $lproNo != "") && ($AccountName != null || $AccountName != "")) {
                                         $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                } else {
                                      if (trim($group_loan_type) == "PENSION") {
                                           $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                     }
                                }
                           /* end of filter --------------------------*/
                        // array_push($info,$temp_info);
                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                        // fputcsv($fp, $temp_info );

                 }
                }

                    $counter++;
                    $global_newline_array = array();
                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;
                     $lproNo = null;


            }

            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
            echo $fnlRes;
            exit;

                // $myfile = fopen($exportTxt, "w") or die("Unable to open file!");
                // $tmpString = $this->toTextFile($info,'|',$removeText,'PNPRE','',$headerCSV,$exportCsv);
                // fclose($myfile);
                // file_put_contents($exportTxt, $tmpString, FILE_USE_INCLUDE_PATH);
                // echo json_encode(array(
                //     'statusCode'    => 200,
                //     'devMessage'    => "Success",
                //     'csv_path'      => $exportCsv1,
                //     'pipe_path'     => $exportTxt2,
                //     'filter'        => $filter
                // ));

            }

        }

     public function exportBillingBfpAcAction(){

          ini_set('memory_limit', '-1');

            $rootPath = "/var/www/html/psslai/";
            // $rootPath = "/var/www/psslai/";
            $exportFileName = "export/BILLING_BFP-AC_ao";
            $logFileName = "export/fileLog_tempBFPAC.log";
            $filter = file_get_contents($rootPath."public/params/params.txt");
            $counter = 0;
            $date = date("YmdHis");

            $path2 = $rootPath."public/".$logFileName;
            $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";

            $exportCsv1 = $exportFileName.$date.'.csv';
            $exportTxt2 = $exportFileName.$date.'.txt';

            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $c = "TblAtmListFile";
            $d = "TblLoanCsvFile"; //Loan Billing
            $e = "TblLoanAtmFile"; //Loan Atm
            $f = "TblDeductionCode"; //Collection
            $g = "TblBillMode";
            $h = "TblCollectionBfpAc";
            $ss = "TblServiceStatus";
            $tbl_branchSvc = "TblBos";
            $tbl_loanType = "TblLoanType";
            $billingType = "BfpAc";
            $ext = "";
            $cod = "";
            $ext = "";
            if ($filter == 'FULLY PAID') {
                $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
                $ext = "_Fully Paid";
            } else if($filter == 'NO ACCOUNT'){
                $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
                $ext = "_No Account";
            } else if($filter == 'OPTIONAL'){
                $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $ext = "_Optional";
            } else if($filter == 'NORMAL') {
                $aa = " AND ($a.SvcStat != 'CO' AND $a.SvcStat != 'OP' AND $a.SvcStat != 'RD' AND $a.SvcStat != 'RE')";
                $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                    ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
                $ext = "_Normal";
            }

            defined('APP_PATH') || define('APP_PATH', realpath('.'));
            $date = date("YmdHis");
            $exportCsv = 'export/BILLING_BFP-AC_ao'.$date.$ext.'.csv';
            $exportTxt = 'export/BILLING_BFP-AC_ao'.$date.$ext.'.txt';
            $exportCsv1 = 'export/BILLING_BFP-AC_ao'.$date.$ext.'.csv';
            $exportTxt2 = 'export/BILLING_BFP-AC_ao'.$date.$ext.'.txt';
            $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

                $fp = fopen($rootPath.'public/'.$exportFileName.$date.$ext.'.csv', 'w');
                $headerCSV = ['UPLOAD.COMPANY',
                'PH.BRANCH.SVC',
                'PH.PAY.PERIOD',
                'PH.PIN.NO',
                'PH.FULLNAME1',
                'CUSTOMER.CODE',
                'PH.LAST.NAME',
                'PH.FIRST.NAME',
                'PH.MIDDLE.NAME',
                'PH.QUAL.NAME',
                'PH.SERVICE.STATUS',
                'PH.LOAN.BILL.AMT',
                'PH.CAPCON.BILL.AMT',
                'PH.CASA.BILL.AMT',
                'PH.PSA.BILL.AMT',
                'PH.TOTAL.BILL.AMT',
                'PH.BILL.AMT',
                'PH.LOAN.ORIG.ID',
                'PH.LOAN.TYPE',
                'PH.APP.TYPE',
                'PH.MATURITY.DATE',
                'PH.ORIG.CONT.DATE',
                'PH.UPD.CONT.DATE',
                'PH.LOAN.TERM',
                'PH.ORIG.CONT.AMT',
                'PH.LOAN.STAT',
                'PH.START.AMRT.DATE',
                'PH.BILL.TRANS.TYPE',
                'PH.BIL.TRANS.STAT',
                'PH.BILL.MODE',
                'PH.ORIG.DEDNCODE',
                'PH.UPD.DEDNCODE',
                'PH.ORIG.NRI',
                'PH.UPDATED.NRI',
                'PH.BILL.REMARKS',
                'PH.BILL.STOPPAGE',
                'PH.ATM.CARD.NO',
                'PH.FULLNAME2',
                'PH.PENS.ACCT.NO',
                'PH.PAYMT.STAT',
                'PH.STOP.DEDN.CODE',
                'PH.INCLD.BIL.IND',
                'PH.1ST.LOAD.AMT',
                'AMOUNT.COLLECT',
                'PAY.PERIOD.COLLECT'];
                 fputcsv($fp, $headerCSV );

            if($cod) {

                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
                ->where("$a.BranchSvc = 'BFP' $aa")
                ->groupBy("$a.id")
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,$a.LastName,
                    $a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo")
               ->where("$a.BranchSvc = 'BFP'")
               ->groupBy("$a.id")
               ->execute();
            }

            $getInitDate = TblLoanCsvFile::query()
            ->columns('initDate')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();
            $initDate = $getInitDate == null ? null : $getInitDate[0]->initDate;

            $getLastData = TblLoanCsvFile::query()
            ->columns('initDate, id_loan_csv_name')
            ->limit(1)
            ->orderBy("id Desc")
            ->execute();

            $initPayPeriod = $getLastData == null ? null : $getLastData[0]->id_loan_csv_name;

            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info = array();

                $id                 = null;
                $SapMemberNo        = null;
                $full_name          = null;
                $BranchSvc          = null;
                $SvcStat            = null;
                $LastName           = null;
                $FirstName          = null;
                $MiddleName         = null;
                $QualifNam          = null;
                $PINAcctNo          = null;
                $T24MemberNo        = null;
                $loanAmt            = null;
                $MOA1               = null;
                $MOA2               = null;
                $lproNo             = null;
                $lnType             = null;
                $dateGrant          = null;
                $maturity           = null;
                $startDate1         = null;
                $loanAppl           = null;
                $PnpBillMod         = null;
                $lnpTrate           = null;
                $startDate2         = null;
                $loanProc           = null;
                $id_loan_csv_name   = null;
                $TSAcctTy           = null;
                $TSAccNo            = null;
                $AccountName        = null;
                $ID_Atm             = null;
                $PICOSNO            = null;
                $REFERENCE          = null;
                $CONTROL            = null;
                $DATERCVD           = null;
                $PIN                = null;
                $ATMCARDSTAT        = null;
                $DATERELEASED       = null;
                $PULLOUTREASON      = null;
                $actual_collection      = null;
                $collection_pay_period  = null;
                $deduction_code         = null;
                $payPayPeriod = null;
                $getInitDate = null;
                $bill_mod = null;
                $bill_mode_sap = null;
                $aging = null;

                foreach($getQry as $data){
                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $counter /count($getQry) * 100;
                    echo " BFP AC - Exporting file : ".number_format($percent,2)."%";

                    $id             = $data->id;
                    $SapMemberNo    = $data->SapMemberNo;
                    $full_name      = $data->full_name;
                    $BranchSvc      = $data->BranchSvc;
                    $SvcStat        = $data->SvcStat;
                    $LastName       = $data->LastName;
                    $FirstName      = $data->FirstName;
                    $MiddleName     = $data->MiddleName;
                    $QualifNam      = $data->QualifNam;
                    $PINAcctNo      = $data->PINAcctNo;
                    $T24MemberNo    = $data->T24MemberNo;

                $getTblLoanCsvFile = TblLoanCsvFile::findFirst(array(
                                "columns"    => "loanAmt,MOA1,MOA2,lproNo,lnType,dateGrant,maturity,startDate1,loanAppl,
                                                 PnPBillMod,lnpTrate,startDate2,loanProc,id_loan_csv_name",
                                "conditions" => "memberNo = '".$SapMemberNo."'"));
                    if($getTblLoanCsvFile) {
                        $loanAmt     = $getTblLoanCsvFile->loanAmt;
                        $MOA1        = number_format($getTblLoanCsvFile->MOA1, 2, '.', '');
                        $MOA2        = $getTblLoanCsvFile->MOA2;
                        $lproNo      = str_replace("-", "", $getTblLoanCsvFile->lproNo);
                        $lnType      = $getTblLoanCsvFile->lnType;
                        $dateGrant   = $getTblLoanCsvFile->dateGrant;
                        $maturity    = $getTblLoanCsvFile->maturity;
                        $startDate1  = $getTblLoanCsvFile->startDate1;
                        $loanAppl    = $getTblLoanCsvFile->loanAppl;
                        $PnPBillMod  = $getTblLoanCsvFile->PnPBillMod;
                        $lnpTrate    = $getTblLoanCsvFile->lnpTrate;
                        $startDate2  = $getTblLoanCsvFile->startDate2;
                        $loanProc    = $getTblLoanCsvFile->loanProc;
                        $id_loan_csv_name = $getTblLoanCsvFile->id_loan_csv_name;
                    } else {
                        $loanAmt     = null;
                        $MOA1        = null;
                        $MOA2        = null;
                        $lproNo      = null;
                        $lnType      = null;
                        $dateGrant   = null;
                        $maturity    = null;
                        $startDate1  = null;
                        $loanAppl    = null;
                        $PnPBillMod  = null;
                        $lnpTrate    = null;
                        $startDate2  =null;
                        $loanProc    = null;
                        $id_loan_csv_name = null;
                    }

                    $getTblMemberAccountFile = TblMemberAccountFile::findFirst(array(
                                "columns"    => "TSAcctTy,TSAccNo,AccountName",
                                "conditions" => "MemberNo = '".$SapMemberNo."'"));
                    if($getTblMemberAccountFile) {
                        $TSAcctTy   = $getTblMemberAccountFile->TSAcctTy;
                        $TSAccNo    = $getTblMemberAccountFile->TSAccNo;
                        $AccountName  = trim($getTblMemberAccountFile->AccountName);
                    }

                    $getTblAtmListFile = TblAtmListFile::findFirst(array(
                                "columns"    => "id,PICOSNO,REFERENCE,CONTROL,DATERCVD,PIN,ATMCARDSTAT,DATERELEASED,PULLOUTREASON",
                                "conditions" => "CLIENT LIKE '".$SapMemberNo."'"));
                     if($getTblAtmListFile) {
                        $ID_Atm         = $getTblAtmListFile->id;
                        $PICOSNO        = $getTblAtmListFile->PICOSNO;
                        $REFERENCE      = $getTblAtmListFile->REFERENCE;
                        $CONTROL        = $getTblAtmListFile->CONTROL;
                        $DATERCVD       = $getTblAtmListFile->DATERCVD;
                        $PIN            = $getTblAtmListFile->PIN;
                        $ATMCARDSTAT    = $getTblAtmListFile->ATMCARDSTAT;
                        $DATERELEASED   = $getTblAtmListFile->DATERELEASED;
                        $PULLOUTREASON  = $getTblAtmListFile->PULLOUTREASON;
                     }

             $getTblCollectionBfpAc = TblCollectionBfpAc::findFirst(array(
                                "columns"    => "collection_pay_period,actual_collection,deduction_code_desc,aging",
                                "conditions" => "pin_account_no = '$PINAcctNo'"));
                    if($getTblCollectionBfpAc) {
                          $actual_collection      = number_format($getTblCollectionBfpAc->actual_collection, 2, '.', '');
                            $collection_pay_period  = $getTblCollectionBfpAc->collection_pay_period;
                            $deduction_code         = $getTblCollectionBfpAc->deduction_code_desc;
                            $aging         = $getTblCollectionBfpAc->aging;
                    }
                    // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($id_loan_csv_name, trim($BranchSvc));
                      if($payPayPeriod != ""){
                            $GLOBALS['tempPayPeriod'] = $payPayPeriod;
                        }
                        else{
                            $payPayPeriod = $GLOBALS['tempPayPeriod'];
                        }

                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billAmnt = 0;
                    $SvcStat = trim($data->SvcStat);

                    $startDate = date('Y-m-d', strtotime($startDate1));
                    $endDate = date('Y-m-d',  strtotime($startDate2));
                    $dateGrant2 = date('Y-m-d', strtotime($dateGrant));
                    $origContDate = $dateGrant == "" ? date('Ymd',strtotime($initDate)) : date("Ymd", strtotime($dateGrant));

                    $amortLpro = (double)$MOA1;
                    $amortCollection = (double)$actual_collection;

                    //Get Group and Deduction Code
                    $group_service_status = $this->getGroupServiceStatus(trim($SvcStat));
                    $group_loan_type = $this->getServiceStatusFromLnType(trim($lnType));
                    $service_status_stat = $this->getStatusFromServiceStatus(trim($SvcStat));

                    //check if new client
                    $ifClientExistsLpro = TblLoanCsvFile::findFirst("memberNo = '$SapMemberNo'");
                    $lproDateGrant = $ifClientExistsLpro == null ? null : $ifClientExistsLpro->dateGrant;

                    $ifClientExistsCollection = TblCollectionBfpAc::findFirst("pin_account_no = '$PINAcctNo'");
                    $dedCodeCollection = $ifClientExistsCollection == null ? null : $ifClientExistsCollection->deduction_code_desc;
                    $dedCodeCollection = explode(' ',trim($dedCodeCollection));
                    $dedCodeCollection = $dedCodeCollection[0];


                    //Check billmode, billremarks and billpage values
                    $billParams = $this->getBillParams($PnPBillMod,$billAmnt,0);
                    $billMode = $billParams["billMode"];

                    $getBillRemarks = $this->getBillRemarks($PnPBillMod,$dateGrant,$startDate1,$startDate2,$ifClientExistsCollection,$ifClientExistsLpro,$amortLpro,$amortCollection);
                    $billRemarks = $getBillRemarks["billRemarks"];
                    $billPage = $getBillRemarks["billPage"];

                    $amountCollect = $actual_collection == "" ? 0 : $actual_collection;

                    $newClient = $this->getReferenceSdlis($billingType,$group_service_status,$PINAcctNo); //"20020302002"
                    $loanBillAmtNewSDLIS = $newClient["amortization"];
                    $deduction_codeSDLIS = $newClient["deductionCode"];
                    $dateGrantedSDLIS = $newClient["dateGranted"];

                    if($PnPBillMod == "" || $PnPBillMod == null){
                        $getBillMode = $this->getBillMode($group_service_status,$dedCodeCollection,$BranchSvc);
                        $PnPBillMod = $getBillMode["bill_mode_sap"];

                        $newBillMode = $this->getBillMode($group_service_status,$dedCodeCollection,$branchSvc);
                        $billMode = $newBillMode["bill_mode"];

                    }

                    $billRemarks = "";
                    if(($PnPBillMod == "PBM03" || $PnPBillMod == "PBM05") &&  ($loanBillAmtNewSDLIS > $actual_collection)) {
                        $billRemarks = "A";
                    } else if ($amortLpro == 0) {
                        $billRemarks = "S";
                    } else if ((($startDate >= $dateGrant) && ($dateGrant <= $endDate)) && (empty($ifClientExistsCollection))) {
                        $billRemarks = "N";
                    } else if(!empty($ifClientExistsLpro) && !empty($ifClientExistsCollection)) {
                        $billRemarks = "R";
                    }

                        $dateGranted = $dateGrant;
                        $branchSvc = trim($data->BranchSvc);
                        $deduction_code = $this->getDeductionCode($group_service_status,$PnPBillMod,$lnType,$branchSvc);
                       $loanBillAmtNew = $MOA1 == null ? $actual_collection : $MOA1;
                        $nriVal = $lnpTrate;
                    $startDate1 = $startDate1 == null ? "" : date('Ymd', strtotime($startDate1));
                        //If record is not available in LPRO Billing - this condition applies.
                            if ($getTblLoanCsvFile == null) {
                                 $payPayPeriod = $this->getPayPeriod(trim($initPayPeriod), trim($branchSvc));
                                //PH.LOAN.ORIG.ID
                                if ($lproNo == null || $lproNo == "") {
                                        if ($billMode == "Bill to Loan") {
                                            $lproNo = "";
                                        } else if ($billMode == "Bill to CAPCON" || $billMode == "Bill to Savings" || $billMode == "Bill to CASA") {
                                            $lproNo = str_replace("-", "", $AccountName);
                                        }
                                }
                                //PH.ORIG.DEDNCODE
                                if ($deduction_code == null || $deduction_code == "") {
                                    $deduction_code = $dedCodeCollection;
                                }

                                //PH.ORIG.NRI
                                $nriVal = $aging;

                            }
                            // echo "($LastName,$PnPBillMod,$amortCollection,$amortLpro)";
                            //Adding of New line (BILL AMOUNT PROCESS)
                        $newLine =  $this->processBillAmountGrpSDLIS(
                            "BFPAC",
                            $PnPBillMod,
                            $MOA1,
                            $amortCollection,
                            $amortLpro,
                            $SapMemberNo,
                            $TSAcctTy,
                            $BranchSvc,
                            $PINAcctNo,
                            $T24MemberNo,
                            $LastName,
                            $FirstName,
                            $MiddleName,
                            $QualifNam,
                            $SvcStat,
                            $billAmnt,
                            $lnType,
                            date('Ymd', strtotime($dateGranted)),
                            $billMode,
                            $dedCodeCollection,
                            $nriVal,
                            $billRemarks,
                            $billPage,
                            $actual_collection,
                            $collection_pay_period,
                            $newRecordArray,
                            $full_name,
                            $newRecordArray,
                            $loanBillAmtNewSDLIS,
                            $deduction_codeSDLIS,
                            $lproNo,
                            $amountCollect,
                            $payPayPeriod,
                            $loanProc,
                            $deduction_code,
                            $filter,
                            $AccountName);

             if((trim($lnType) != "BL") && (trim($lnType) != "NL")) {
                    if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {

                            $temp_info = array(
                                'up_company' =>'PH0010002',
                                'BranchSvc' =>$this->returnEmpty($BranchSvc),
                                'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                                'PIN' => $this->returnEmpty($PINAcctNo),
                                'full_name' =>'',
                                'MemberStat' => $this->returnEmpty($T24MemberNo),
                                'LastName' => $this->returnEmpty($LastName),
                                'FirstName' => $this->returnEmpty($FirstName),
                                'MiddleName' => $this->returnEmpty($MiddleName),
                                'QualifNam' => $this->returnEmpty($QualifNam),
                                'SvcStat' => $this->returnEmpty($SvcStat),
                                'loan_bill_amount' => $this->returnEmpty($loanBillAmtNew),
                                'capcon_bill_amount' =>"",
                                'casa_bill_amount' =>"",
                                'psa_bill_amount' =>"",
                                'total_bill_amnt' =>$this->returnEmpty($billAmnt),
                                'bill_amt' =>$this->returnEmpty($loanBillAmtNew),
                                'lproNo' => $this->returnEmpty($lproNo),
                                'lnType' =>"",
                                'loanAppl' =>"",
                                'maturityDate' =>"",
                                'dateGrant' => date('Ymd', strtotime($dateGranted)),
                                'updContDate' =>"",
                                'loanTerm' =>"",
                                'origContDate' =>"",
                                'loanStat' =>$this->returnEmpty($loanProc),
                                'startAmrtDate' =>$this->returnEmpty($startDate1),
                                'billTransType' =>"",
                                'bilTransStat' =>"",
                                'billMode' =>$this->returnEmpty($billMode),
                                'orgDeDNCode' =>$this->returnEmpty(trim($deduction_code)),
                                'updtDeDNCode' =>"",
                                'lnpTrate' =>"",
                                'uptNri' =>"",
                                'Remarks' =>$this->returnEmpty($billRemarks),
                                'stop_page' =>$this->returnEmpty($billPage),
                                'atmCardNo' =>"",
                                'fullName2' =>"",
                                'pensAcctNo' =>"",
                                'payMtStats' =>"",
                                'stopDedNCode' =>"",
                                'incldBilInd' =>"YES",
                                '1stLoadAmt' =>"",
                                'collection_amount' =>$this->returnEmpty($amountCollect),
                                'collection_pay_period' => $this->returnEmpty($collection_pay_period)
                            );

                            if($filter == 'FULLY PAID'){
                                $ext = "_Fully Paid";

                                if (trim($group_loan_type) == "ACTIVE" && ($lproNo == null || $lproNo == "") && ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                    $info [] = $temp_info;
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                            } else if($filter == 'NO ACCOUNT'){
                                $ext = "_No Account";

                                if (trim($group_loan_type) == "ACTIVE" && ($AccountName == null || $AccountName == "") &&
                                    ($PnPBillMod == "PBM03" || $PnPBillMod == "PBM04" || $PnPBillMod == "PBM05" || $PnPBillMod == "PBM06" OR $PnPBillMod == "PBM07")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                }
                            } else if($filter == 'OPTIONAL'){
                                $ext = "_Optional";

                                 if ($group_loan_type == "ACTIVE" && $service_status_stat == "PENSION") {
                                     $info [] = $temp_info;
                                     array_push($info,$temp_info);
                                     fputcsv($fp, $temp_info );
                                     $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                     fwrite($txt,$result);
                                }
                            } else if($filter == 'NORMAL') {
                                $ext = "_Normal";

                                if (trim($group_loan_type) == "ACTIVE" && $service_status_stat == "ACTIVE" &&
                                    ($AccountName != null) && ($lproNo != null)) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                }
                            } else {
                                if (trim($group_loan_type) == "ACTIVE") {
                                    $info [] = $temp_info;
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                            }


                            $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                            foreach($global_newline_array as $newLine){
                                    array_push($info,$newLine);
                                    fputcsv($fp, $newLine );
                                    $result = html_entity_decode(implode("|",$newLine)."\r\n");
                                    fwrite($txt,$result);
                            }


                     } else {

                         $temp_info = array(
                            'up_company' =>'PH0010002',
                            'BranchSvc' =>$this->returnEmpty($BranchSvc),
                            'PayPeriod' =>$this->returnEmpty($payPayPeriod),
                            'PIN' => $this->returnEmpty($PINAcctNo),
                            'full_name' =>'',
                            'MemberStat' => $this->returnEmpty($T24MemberNo),
                            'LastName' => $this->returnEmpty($LastName),
                            'FirstName' => $this->returnEmpty($FirstName),
                            'MiddleName' => $this->returnEmpty($MiddleName),
                            'QualifNam' => $this->returnEmpty($QualifNam),
                            'SvcStat' => $this->returnEmpty($SvcStat),
                            'loan_bill_amount' => $this->returnEmpty($loanBillAmtNew),
                            'capcon_bill_amount' =>"",
                            'casa_bill_amount' =>"",
                            'psa_bill_amount' =>"",
                            'total_bill_amnt' =>$this->returnEmpty($billAmnt),
                            'bill_amt' =>$this->returnEmpty($loanBillAmtNew),
                            'lproNo' => $this->returnEmpty($lproNo),
                            'lnType' =>"",
                            'loanAppl' =>"",
                            'maturityDate' =>"",
                            'dateGrant' => date('Ymd', strtotime($dateGranted)),
                            'updContDate' =>"",
                            'loanTerm' =>"",
                            'origContDate' =>"",
                            'loanStat' =>$this->returnEmpty($loanProc),
                            'startAmrtDate' =>$this->returnEmpty($startDate1),
                            'billTransType' =>"",
                            'bilTransStat' =>"",
                            'billMode' =>$this->returnEmpty($billMode),
                            'orgDeDNCode' =>$this->returnEmpty(trim($deduction_code)),
                            'updtDeDNCode' =>"",
                            'lnpTrate' =>"",
                            'uptNri' =>"",
                            'Remarks' =>$this->returnEmpty($billRemarks),
                            'stop_page' =>$this->returnEmpty($billPage),
                            'atmCardNo' =>"",
                            'fullName2' =>"",
                            'pensAcctNo' =>"",
                            'payMtStats' =>"",
                            'stopDedNCode' =>"",
                            'incldBilInd' =>"YES",
                            '1stLoadAmt' =>"",
                            'collection_amount' =>$this->returnEmpty($amountCollect),
                            'collection_pay_period' => $this->returnEmpty($collection_pay_period)

                            );

                            if($filter == 'FULLY PAID'){
                                $ext = "_Fully Paid";

                                if (trim($group_loan_type) == "ACTIVE" && ($lproNo == null || $lproNo == "") && ($PnPBillMod == "PBM00" || $PnPBillMod == "PBM01" || $PnPBillMod == "PBM02")) {
                                    $info [] = $temp_info;
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                            } else if($filter == 'NO ACCOUNT'){
                                $ext = "_No Account";

                                if (trim($group_loan_type) == "ACTIVE" && ($AccountName == null || $AccountName == "") &&
                                    ($PnPBillMod == "PBM03" || $PnPBillMod == "PBM04" || $PnPBillMod == "PBM05" || $PnPBillMod == "PBM06" OR $PnPBillMod == "PBM07")) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                }
                            } else if($filter == 'OPTIONAL'){
                                $ext = "_Optional";

                                 if ($group_loan_type == "ACTIVE" && $service_status_stat == "PENSION") {
                                    $info [] = $temp_info;
                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }
                            } else if($filter == 'NORMAL') {
                                $ext = "_Normal";

                                if (trim($group_loan_type) == "ACTIVE" && $service_status_stat == "ACTIVE" &&
                                    ($AccountName != null) && ($lproNo != null)) {
                                        $info [] = $temp_info;
                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                }
                            } else {
                                if (trim($group_loan_type) == "ACTIVE") {
                                     $info [] = $temp_info;
                                     array_push($info,$temp_info);
                                     fputcsv($fp, $temp_info );
                                     $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                     fwrite($txt,$result);
                                }
                            }

                     }
                }



                    $global_newline_array = array();
                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;
                    $counter++;
            }
            $myTextFileHandler = fopen($path2,"r+");
            $d = ftruncate($myTextFileHandler, 0);
            fclose($myTextFileHandler);
            $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
            echo $fnlRes;
            exit;
            }

    }

    public function exportBillingBfpReAction() {
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // $ctr =0;

        ini_set('memory_limit', '-1');
        // $date = date("YmdHis");
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBillingBfpRe.log";
        // // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
        // $pathtxt = "/var/www/html/psslai/public/export/BILLING_BFP-RE_ao".$date.".txt";
        // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
        // $exportCsv1 = 'export/BILLING_BFP-RE_ao'.$date.'.csv';
        // $exportTxt2 = 'export/BILLING_BFP-RE_ao'.$date.'.txt';

        // $ctr =0;
        // $info = array();
        // $payPayPeriod = "";
        // $fp = fopen('/var/www/html/psslai/public/export/BILLING_BFP-RE_ao'.$date.'.csv', 'w');

        $rootPath = "/var/www/html/psslai/";
    //    $rootPath = "/var/www/psslai/";
       $exportFileName = "export/BILLING_BFP-RE_ao";
       $logFileName = "export/fileLog_tempBillingBfpRe.log";
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $counter = 0;
       $date = date("YmdHis");
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $path2 = $rootPath."public/".$logFileName;
       $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";
       // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
       $exportCsv1 = $exportFileName.$date.'.csv';
       $exportTxt2 = $exportFileName.$date.'.txt';

        defined('APP_PATH') || define('APP_PATH', realpath('.'));
         $date = date("YmdHis");


        $ctr = 1;

            $a = "TblMemberInfoFile";
            $b = "TblMemberAccountFile";
            $c = "TblAtmListFile";
            $d = "TblLoanCsvFile"; //Loan Billing
            $e = "TblLoanAtmFile"; //Loan Atm
            $f = "TblDeductionCode"; //Collection
            $g = "TblBillMode";
            $h = "TblCollectionBfpRe";
            $ss = "TblServiceStatus";
            $tbl_branchSvc = "TblBos";
            $tbl_loanType = "TblLoanType";
            $billingType = "BfpRe";
            $ext = "";
            $cod = "";

            if($filter == 'FULLY PAID'){
                $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
                $ext = "_Fully Paid";
            } else if($filter == 'NO ACCOUNT'){
                $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
                $ext = "_No Account";
            } else if($filter == 'OPTIONAL'){
                $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $ext = "_Optional";
            } else if($filter == 'NORMAL') {
                $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
                $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                    ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
                $ext = "_Normal";
            }


        $exportCsv  = 'export/BILLING_BFP-RE_ao'.$date.$ext.'.csv';
        $exportTxt  = 'export/BILLING_BFP-RE_ao'.$date.$ext.'.txt';
        $exportCsv1 = 'export/BILLING_BFP-RE_ao'.$date.$ext.'.csv';
        $exportTxt2 = 'export/BILLING_BFP-RE_ao'.$date.$ext.'.txt';
        $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

        $fp = fopen($rootPath.'public/export/BILLING_BFP-RE_ao'.$date.$ext.'.csv', 'w');
        $headerCSV = ['UPLOAD.COMPANY',
        'PH.BRANCH.SVC',
        'PH.PAY.PERIOD',
        'PH.PIN.NO',
        'PH.FULLNAME1',
        'CUSTOMER.CODE',
        'PH.LAST.NAME',
        'PH.FIRST.NAME',
        'PH.MIDDLE.NAME',
        'PH.QUAL.NAME',
        'PH.SERVICE.STATUS',
        'PH.LOAN.BILL.AMT',
        'PH.CAPCON.BILL.AMT',
        'PH.CASA.BILL.AMT',
        'PH.PSA.BILL.AMT',
        'PH.TOTAL.BILL.AMT',
        'PH.BILL.AMT',
        'PH.LOAN.ORIG.ID',
        'PH.LOAN.TYPE',
        'PH.APP.TYPE',
        'PH.MATURITY.DATE',
        'PH.ORIG.CONT.DATE',
        'PH.UPD.CONT.DATE',
        'PH.LOAN.TERM',
        'PH.ORIG.CONT.AMT',
        'PH.LOAN.STAT',
        'PH.START.AMRT.DATE',
        'PH.BILL.TRANS.TYPE',
        'PH.BIL.TRANS.STAT',
        'PH.BILL.MODE',
        'PH.ORIG.DEDNCODE',
        'PH.UPD.DEDNCODE',
        'PH.ORIG.NRI',
        'PH.UPDATED.NRI',
        'PH.BILL.REMARKS',
        'PH.BILL.STOPPAGE',
        'PH.ATM.CARD.NO',
        'PH.FULLNAME2',
        'PH.PENS.ACCT.NO',
        'PH.PAYMT.STAT',
        'PH.STOP.DEDN.CODE',
        'PH.INCLD.BIL.IND',
        'PH.1ST.LOAD.AMT',
        'AMOUNT.COLLECT',
        'PAY.PERIOD.COLLECT'];

        fputcsv($fp, $headerCSV );

            if($cod) {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BFP' $aa")
                ->groupby("$a.id")
                // ->limit($count,$offset)
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BFP'")
                ->groupby("$a.id")
                // ->limit($count,$offset)
                ->execute();
            }

            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info = array();

                foreach($getQry as $data){
                    //create a file handler by opening the file
                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $ctr /count($getQry) * 100;
                    echo "BILLING BFP-RE - Exporting file : ".number_format($percent,2)."%";

                    $getQryMemberAccountFile = TblMemberAccountFile::findFirst(array(
                        "columns"    => "TSAcctTy, TSAccNo, AccountName",
                        "conditions" => "MemberNo = '$data->SapMemberNo'",
                    ));
                    if($getQryMemberAccountFile){
                        $b_TSAcctTy = $this->returnEmpty($getQryMemberAccountFile->TSAcctTy);
                        $b_TSAccNo  = $this->returnEmpty($getQryMemberAccountFile->TSAccNo);
                        $b_AccountName = $this->returnEmpty($getQryMemberAccountFile->AccountName);
                    }

                    $getQryAtmListFile = TblAtmListFile::findFirst(array(
                        "columns"    => "PICOSNO, REFERENCE, CONTROL, DATERCVD, PIN, ATMCARDSTAT, DATERELEASED, PULLOUTREASON",
                        "conditions" => "CLIENT = '$data->SapMemberNo'",
                    ));
                    if ($getQryAtmListFile) {
                        $c_PICOSNO          = $this->returnEmpty($getQryAtmListFile->PICOSNO);
                        $c_REFERENCE        = $this->returnEmpty($getQryAtmListFile->REFERENCE);
                        $c_CONTROL          = $this->returnEmpty($getQryAtmListFile->CONTROL);
                        $c_DATERCVD         = $this->returnEmpty($getQryAtmListFile->DATERCVD);
                        $c_PIN              = $this->returnEmpty($getQryAtmListFile->PIN);
                        $c_ATMCARDSTAT      = $this->returnEmpty($getQryAtmListFile->ATMCARDSTAT);
                        $c_DATERELEASED     = $this->returnEmpty($getQryAtmListFile->DATERELEASED);
                        $c_PULLOUTREASON    = $this->returnEmpty($getQryAtmListFile->PULLOUTREASON);
                    }

                    $getQryLoanBilling = TblLoanCsvFile::findFirst(array(
                        "columns" => "initDate, loanAmt, MOA1, MOA2, lproNo, lnType, dateGrant, maturity, startDate1, startDate2, loanAppl, PnPBillMod, loanProc, id_loan_csv_name",
                        "conditions" => "memberNo = '$data->SapMemberNo'",
                    ));
                    if ($getQryLoanBilling) {
                        $d_initDate     = $this->returnEmpty($getQryLoanBilling->initDate);
                        $d_loanAmt      = $this->returnEmpty($getQryLoanBilling->loanAmt);
                        $d_MOA1         = $this->returnEmpty($getQryLoanBilling->MOA1);
                        $d_MOA2         = $this->returnEmpty($getQryLoanBilling->MOA2);
                        $d_lproNo       = $this->returnEmpty(str_replace("-", "", $getQryLoanBilling->lproNo));
                        $d_lnType       = $this->returnEmpty($getQryLoanBilling->lnType);
                        $d_dateGrant    = $this->returnEmpty($getQryLoanBilling->dateGrant);
                        $d_maturity     = $this->returnEmpty($getQryLoanBilling->maturity);
                        $d_startDate1   = $this->returnEmpty($getQryLoanBilling->startDate1);
                        $d_startDate2   = $this->returnEmpty($getQryLoanBilling->startDate2);
                        $d_loanAppl     = $this->returnEmpty($getQryLoanBilling->loanAppl);
                        $d_PnPBillMod   = $this->returnEmpty($getQryLoanBilling->PnPBillMod);
                        $d_loanProc     = $this->returnEmpty($getQryLoanBilling->loanProc);
                        $d_id_loan_csv_name = $this->returnEmpty($getQryLoanBilling->id_loan_csv_name);
                    }

                    // $getQryCollection = TblCollectionBfpRe::findFirst("deduction_code = '$data->PINAcctNo'");
                    //     $h_monthlyAmort = $this->returnEmpty($getQryCollection->MonthlyAmort);
                    //     $h_collection_pay_period = $this->returnEmpty($getQryCollection->collection_pay_period);

                    // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($d_id_loan_csv_name, trim($data->BranchSvc));
                    // if($payPayPeriod != ""){
                    //     $GLOBALS['tempPayPeriod'] = $payPayPeriod;
                    // }
                    // else{
                    //     $payPayPeriod = $GLOBALS['tempPayPeriod'];
                    // }

                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billAmnt == 0;
                    $SvcStat = trim($data->SvcStat);

                    //Get Group and Deduction Code
                    $group_service_status = $this->getGroupServiceStatus($SvcStat);
                    $group_loan_type = $this->getServiceStatusFromLnType($d_lnType);
                    // $this->_respondError(array(
                    //     "group_loan_type" => $group_loan_type,
                    //     "d_lnType" => $d_lnType,
                    // ));

                    //check if new client
                    // $ifClientExistsLpro = TblLoanAtmFile::findFirst("memberNo = '$data->SapMemberNo'");
                    // $lproDateGrant = $ifClientExistsLpro->dateGrant;

                    $ifClientExistsCollection = TblCollectionBfpRe::findFirst("PANAccnt = '$data->PINAcctNo'"); //deduction_code must be PANAccnt (wrong alignment on the backend)
                    $h_NRI = $this->returnEmpty($ifClientExistsCollection->NRI);
                    // $deduction_code = $this->returnEmpty($ifClientExistsCollection->deduction_code);
                    $h_monthlyAmort = $this->returnEmpty($ifClientExistsCollection->MonthlyAmort);
                    $h_collection_pay_period = $this->returnEmpty($ifClientExistsCollection->collection_pay_period);
                    $h_colDateGranted = $this->returnEmpty($ifClientExistsCollection->DateGranted);
                    $dedCodeCollection = $ifClientExistsCollection->RetireeName; //RetireeName should be deduction_code - wrong alignment on the backend
                    $dedCodeCollection = explode(' ',trim($dedCodeCollection));
                    $dedCodeCollection = $dedCodeCollection[0];

                    // $this->_respondError($ifClientExistsCollection);
                    // print_r("($data->LastName,$data->PINAcctNo,$dedCodeCollection)");
                    //    print_r($dedCodeCollection);

                    //Check billmode, billremarks and billpage values
                    $billParams = $this->getBillParams($d_PnPBillMod,$billAmnt);
                    $billMode = $billParams["billMode"];
                    $billRemarks = $billParams["billRemarks"];
                    $billPage = $billParams["billPage"];

                    $amountCollect = $h_monthlyAmort == false ? 0 : $h_monthlyAmort;
                    $newClient = $this->getReferenceSdlis($billingType,$group_service_status,trim($data->PINAcctNo)); //"20020302002"
                    $loanBillAmtNewSDLIS = $newClient["amortization"];
                    $deduction_codeSDLIS = $newClient["deductionCode"];

                    $loanBillAmtNew = number_format($d_MOA1, 2, '.', '');

                    $branchSvc = trim($data->BranchSvc);
                    // $deduction_code = $this->getDeductionCode($group_service_status,$d_PnPBillMod,$d_lnType,$branchSvc);

                    // $this->_respondError($deduction_code);
                    // $this->_respondError("group_service_status: $group_service_status, data->PnPBillMod: $data->PnPBillMod, data->lnType: $data->lnType, branchSvc: $branchSvc");

                    //Adjust value of $billRemarks and $billPage according to BJP RE
                    if ($loanBillAmtNew == 0) {
                        $billRemarks = "S";
                        $billPage = "YES";
                    } else {
                        $billRemarks = "D";
                        $billPage = "NO";
                    }

                        $amortLpro = (double)$d_MOA1;
                        $amortCollection = (double)$h_monthlyAmort;

                        $dateGranted = $d_dateGrant;
                        $branchSvc = trim($data->BranchSvc);
                        $deduction_code = $this->getDeductionCode($group_service_status,$d_PnPBillMod,$d_lnType,$branchSvc);

                        // If no data in LPRO Billing
                        if (!$getQryLoanBilling) {
                            $getLastData = TblLoanCsvFile::query()
                            ->columns('initDate, id_loan_csv_name')
                            ->limit(1)
                            ->orderBy("id Desc")
                            ->execute();

                            // PH.PAY.PERIOD
                            $payPayPeriod = $this->getPayPeriod(trim($getLastData[0]->id_loan_csv_name), trim($data->BranchSvc));

                            // PH.ORIG.CONT.DATE
                            $dateGranted = $getLastData[0]->initDate;

                            // PH.ORIG.DEDNCODE
                            if ($deduction_code == null || $deduction_code == "") {
                                $deduction_code = $h_deduction_code;
                            }

                            // PH.BILL.MODE
                            $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                            $billMode = $newBillMode["bill_mode"];

                            // PH.LOAN.ORIG.ID
                            if($billMode == "Bill to Loan"){
                                $d_lproNo = "";
                            }
                            else if($billMode=="Bill to CAPCON" || $billMode=="Bill to CASA" || $billMode=="Bill to Savings"){
                                $d_lproNo = str_replace("-", "", $getQryMemberAccountFile->AccountName);
                            } else {
                                $d_lproNo = "";
                            }

                            // PH.LOAN.BILL.AMT
                            $loanBillAmtNew = number_format($h_monthlyAmort, 2, '.', '');

                        }


                        $nriVal = ($isAddLine == true ) ? "PENDING" : "";
                        $startDate1 = $d_startDate1 == null ? "" : date('Ymd', strtotime($d_startDate1));

                        //Adding of New line (BILL AMOUNT PROCESS)
                        $newLine =  $this->processBillAmountGrpSDLIS(
                            "BFPRE",
                            $d_PnPBillMod,
                            $d_MOA1,
                            $amortCollection,
                            $amortLpro,
                            $data->SapMemberNo,
                            $b_TSAcctTy,
                            $data->BranchSvc,
                            $data->PINAcctNo,
                            $data->T24MemberNo,
                            $data->LastName,
                            $data->FirstName,
                            $data->MiddleName,
                            $data->QualifNam,
                            $data->SvcStat,
                            $billAmnt,
                            $d_lnType,
                            date('Ymd', strtotime($dateGranted)),
                            $billMode,
                            $dedCodeCollection,
                            $nriVal,
                            $billRemarks,
                            $billPage,
                            $h_monthlyAmort,
                            $h_collection_pay_period,
                            $newRecordArray,
                            $data->full_name,
                            $newRecordArray,
                            $loanBillAmtNewSDLIS,
                            $deduction_codeSDLIS,
                            $d_lproNo,
                            $amountCollect,
                            $payPayPeriod,
                            $d_loanProc,
                            $deduction_code,
                            $filter,
                            $b_AccountName,
                            $startDate1);

                if((trim($lnType) != "BL") && (trim($lnType) != "NL")) {
                    if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {
                        $temp_info = array(
                            'up_company'            => 'PH0010002',
                            'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                            'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                            'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                            'full_name'             => '',
                            'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                            'LastName'              => $this->returnEmpty($data->LastName),
                            'FirstName'             => $this->returnEmpty($data->FirstName),
                            'MiddleName'            => $this->returnEmpty($data->MiddleName),
                            'QualifNam'             => $this->returnEmpty($data->QualifNam),
                            'SvcStat'               => $this->returnEmpty($data->SvcStat),
                            'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                            'capcon_bill_amount'    => "",
                            'casa_bill_amount'      => "",
                            'psa_bill_amount'       => "",
                            'total_bill_amnt'       => $this->returnEmpty($billAmnt),
                            'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                            'lproNo'                => $this->returnEmpty($d_lproNo),
                            'lnType'                => "",
                            'loanAppl'              => "",
                            'maturityDate'          => "",
                            'dateGrant'             => date('Ymd',strtotime($dateGranted)),
                            'updContDate'           => "",
                            'loanTerm'              => "",
                            'origContDate'          => "",
                            'loanStat'              => $this->returnEmpty($d_loanProc),
                            'startAmrtDate'         => $this->returnEmpty($startDate1),
                            'billTransType'         => "",
                            'bilTransStat'          => "",
                            'billMode'              => $this->returnEmpty($billMode),
                            'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                            'updtDeDNCode'          => "",
                            'lnpTrate'              => $this->returnEmpty($nriVal),
                            'uptNri'                => "",
                            'Remarks'               => $this->returnEmpty($billRemarks),
                            'stop_page'             => $this->returnEmpty($billPage),
                            'atmCardNo'             => "",
                            'fullName2'             => "",
                            'pensAcctNo'            => "",
                            'payMtStats'            => "",
                            'stopDedNCode'          => "",
                            'incldBilInd'           => "YES",
                            '1stLoadAmt'            => "",
                            'collection_amount'     =>$this->returnEmpty($h_monthlyAmort),
                            'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)
                            // 'collection_dateGranted'    => date('Ymd', strtotime($h_colDateGranted))
                        );

                        if($filter == 'FULLY PAID'){
                            $ext = "_Fully Paid";

                            if ($group_loan_type == "PENSION" AND
                                ($d_lproNo == null || $d_lproNo == "") AND ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02")) {
                                $info [] = $temp_info;

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }
                        } else if($filter == 'NO ACCOUNT'){
                            $ext = "_No Account";

                            if ($group_loan_type == "PENSION" AND
                                ($b_AccountName == null || $b_AccountName == "") AND
                                ($d_PnPBillMod == "PBM03" OR $d_PnPBillMod == "PBM04" OR $d_PnPBillMod == "PBM05" OR $d_PnPBillMod == "PBM06" OR $d_PnPBillMod == "PBM07")) {
                                    $info [] = $temp_info;

                                    // array_push($info,$temp_info);
                                    // fputcsv($fp, $temp_info );
                                    // $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                    // fwrite($txt,$result);

                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                            }
                        } else if($filter == 'OPTIONAL'){
                            $ext = "_Optional";

                            if ($group_loan_type == "PENSION" AND
                                ($d_lnType != "PL" AND $d_lnType != "AP" AND $d_lnType != "OP")) {
                                $info [] = $temp_info;

                                // array_push($info,$temp_info);
                                // fputcsv($fp, $temp_info );
                                // $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                // fwrite($txt,$result);

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }
                        } else if($filter == 'NORMAL') {
                            $ext = "_Normal";

                            if ($group_loan_type == "PENSION" AND
                                $b_AccountName != null AND
                                $d_lproNo != null AND ($d_lnType == "PL" OR $d_lnType == "AP" OR $d_lnType == "OP")) {
                                   $info [] = $temp_info;

                                   // array_push($info,$temp_info);
                                   // fputcsv($fp, $temp_info );
                                   // $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                   // fwrite($txt,$result);

                                   array_push($info,$temp_info);
                                   fputcsv($fp, $temp_info );
                                   $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                   fwrite($txt,$result);
                            }
                        } else {
                            if ($group_loan_type == "PENSION") {
                                $info [] = $temp_info;

                                // array_push($info,$temp_info);
                                // fputcsv($fp, $temp_info );
                                // $result = html_entity_decode(implode("|",$temp_info)."\r\n");
                                // fwrite($txt,$result);

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }

                        }
                        // array_push($info,$temp_info);
                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                        // fputcsv($fp, $temp_info );
                        $ctr++;

                    if (trim($group_loan_type) == "PENSION") {
                        $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                        foreach($global_newline_array as $newLine){
                            array_push($info,$newLine);
                            fputcsv($fp, $temp_info );
                            $result = utf8_encode(implode("|",$temp_info)."\r\n");
                            fwrite($txt,$result);
                        }
                    }

                     } else {
                         $temp_info  = array(
                            'up_company'            => 'PH0010002',
                            'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                            'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                            'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                            'full_name'             =>'',
                            'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                            'LastName'              => $this->returnEmpty($data->LastName),
                            'FirstName'             => $this->returnEmpty($data->FirstName),
                            'MiddleName'            => $this->returnEmpty($data->MiddleName),
                            'QualifNam'             => $this->returnEmpty($data->QualifNam),
                            'SvcStat'               => $this->returnEmpty($data->SvcStat),
                            'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                            'capcon_bill_amount'    => "",
                            'casa_bill_amount'      => "",
                            'psa_bill_amount'       => "",
                            'total_bill_amnt'       => "",
                            'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                            'lproNo'                => $this->returnEmpty($d_lproNo),
                            'lnType'                => "",
                            'loanAppl'              => "",
                            'maturityDate'          => "",
                            'dateGrant'             => date('Ymd', strtotime($dateGranted)),
                            'updContDate'           => "",
                            'loanTerm'              => "",
                            'origContDate'          => "",
                            'loanStat'              => $this->returnEmpty($d_loanProc),
                            'startAmrtDate'         => $this->returnEmpty($startDate1),
                            'billTransType'         => "",
                            'bilTransStat'          => "",
                            'billMode'              => $this->returnEmpty($billMode),
                            'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                            'updtDeDNCode'          => "",
                            'lnpTrate'              => $this->returnEmpty($nriVal),
                            'uptNri'                => "",
                            'Remarks'               => $this->returnEmpty($billRemarks),
                            'stop_page'             => $this->returnEmpty($billPage),
                            'atmCardNo'             => "",
                            'fullName2'             => "",
                            'pensAcctNo'            => "",
                            'payMtStats'            => "",
                            'stopDedNCode'          => "",
                            'incldBilInd'           => "YES",
                            '1stLoadAmt'            => "",
                            'collection_amount'     => $this->returnEmpty($h_monthlyAmort),
                            // 'collection_amount'     => $h_monthlyAmort == false ? 0 : $h_monthlyAmort,
                            'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)
                            // 'collection_dateGranted'    => date('Ymd', strtotime($h_colDateGranted))

                        );

                        if($filter == 'FULLY PAID'){
                            $ext = "_Fully Paid";

                            if ($group_loan_type == "PENSION" AND
                                ($d_lproNo == null || $d_lproNo == "") AND ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02")) {
                                $info [] = $temp_info;

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }
                        } else if($filter == 'NO ACCOUNT'){
                            $ext = "_No Account";

                            if ($group_loan_type == "PENSION" AND
                                ($b_AccountName == null || $b_AccountName == "") AND
                                ($d_PnPBillMod == "PBM03" OR $d_PnPBillMod == "PBM04" OR $d_PnPBillMod == "PBM05" OR $d_PnPBillMod == "PBM06" OR $d_PnPBillMod == "PBM07")) {
                                    $info [] = $temp_info;

                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                            }
                        } else if($filter == 'OPTIONAL'){
                            $ext = "_Optional";

                            if ($group_loan_type == "PENSION" AND
                                ($d_lnType != "PL" AND $d_lnType != "AP" AND $d_lnType != "OP")) {
                                $info [] = $temp_info;

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }
                        } else if($filter == 'NORMAL') {
                            $ext = "_Normal";

                            if ($group_loan_type == "PENSION" AND
                                $b_AccountName != null AND
                                $d_lproNo != null AND ($d_lnType == "PL" OR $d_lnType == "AP" OR $d_lnType == "OP")) {
                                   $info [] = $temp_info;

                                   array_push($info,$temp_info);
                                   fputcsv($fp, $temp_info );
                                   $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                   fwrite($txt,$result);
                            }
                        } else {
                            if ($group_loan_type == "PENSION") {
                                $info [] = $temp_info;

                                array_push($info,$temp_info);
                                fputcsv($fp, $temp_info );
                                $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                fwrite($txt,$result);
                            }

                        }

                        // array_push($info,$temp_info);
                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                        // fputcsv($fp, $temp_info );
                        $ctr++;

                     }
                }

                    $global_newline_array = array();
                    $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                    $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;


                }

                $myTextFileHandler = fopen($path2,"r+");
                $d = ftruncate($myTextFileHandler, 0);
                fclose($myTextFileHandler);
                $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
                echo $fnlRes;
                exit;

            }
    }

    public function exportBillingBjmpAcAction() {
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // $ctr =0;

        ini_set('memory_limit', '-1');
        // $date = date("YmdHis");
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBillingBjmpAc.log";
        // // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
        // $pathtxt = "/var/www/html/psslai/public/export/BILLING_BJMP-AC_ao".$date.".txt";
        // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
        // $exportCsv1 = 'export/BILLING_BJMP-AC_ao'.$date.'.csv';
        // $exportTxt2 = 'export/BILLING_BJMP-AC_ao'.$date.'.txt';
        //
        // $ctr =0;
        // $info = array();
        // $payPayPeriod = "";
        // $fp = fopen('/var/www/html/psslai/public/export/BILLING_BJMP-AC_ao'.$date.'.csv', 'w');


        $rootPath = "/var/www/html/psslai/";
    //    $rootPath = "/var/www/psslai/";
       $exportFileName = "export/BILLING_BJMP-AC_ao";
       $logFileName = "export/fileLog_tempBillingBjmpAc.log";
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $counter = 0;
       $date = date("YmdHis");
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $path2 = $rootPath."public/".$logFileName;
       $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";
       // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
       $exportCsv1 = $exportFileName.$date.'.csv';
       $exportTxt2 = $exportFileName.$date.'.txt';

        defined('APP_PATH') || define('APP_PATH', realpath('.'));
         $date = date("YmdHis");

        $ctr = 1;

        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $c = "TblAtmListFile";
        $d = "TblLoanCsvFile"; //Loan Billing
        $e = "TblLoanAtmFile"; //Loan Atm
        $f = "TblDeductionCode"; //Collection
        $g = "TblBillMode";
        $h = "TblCollectionBjmpAc";
        $ss = "TblServiceStatus";
        $tbl_branchSvc = "TblBos";
        $tbl_loanType = "TblLoanType";
        $ext = "";

        if($filter == 'FULLY PAID'){
            $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
            $ext = "_Fully Paid";
        } else if($filter == 'NO ACCOUNT'){
            $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
            $ext = "_No Account";
        } else if($filter == 'OPTIONAL'){
            $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $ext = "_Optional";
        } else if($filter == 'NORMAL') {
            $aa = " AND ($a.SvcStat != 'CO' AND $a.SvcStat != 'OP' AND $a.SvcStat != 'RD' AND $a.SvcStat != 'RE')";
            $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
            $ext = "_Normal";
        }

        $exportCsv  = 'export/BILLING_BJMP-AC_ao'.$date.$ext.'.csv';
        $exportTxt  = 'export/BILLING_BJMP-AC_ao'.$date.$ext.'.txt';
        $exportCsv1 = 'export/BILLING_BJMP-AC_ao'.$date.$ext.'.csv';
        $exportTxt2 = 'export/BILLING_BJMP-AC_ao'.$date.$ext.'.txt';
        $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

        $fp = fopen($rootPath.'public/export/BILLING_BJMP-AC_ao'.$date.$ext.'.csv', 'w');

        $headerCSV = ['UPLOAD.COMPANY',
        'PH.BRANCH.SVC',
        'PH.PAY.PERIOD',
        'PH.PIN.NO',
        'PH.FULLNAME1',
        'CUSTOMER.CODE',
        'PH.LAST.NAME',
        'PH.FIRST.NAME',
        'PH.MIDDLE.NAME',
        'PH.QUAL.NAME',
        'PH.SERVICE.STATUS',
        'PH.LOAN.BILL.AMT',
        'PH.CAPCON.BILL.AMT',
        'PH.CASA.BILL.AMT',
        'PH.PSA.BILL.AMT',
        'PH.TOTAL.BILL.AMT',
        'PH.BILL.AMT',
        'PH.LOAN.ORIG.ID',
        'PH.LOAN.TYPE',
        'PH.APP.TYPE',
        'PH.MATURITY.DATE',
        'PH.ORIG.CONT.DATE',
        'PH.UPD.CONT.DATE',
        'PH.LOAN.TERM',
        'PH.ORIG.CONT.AMT',
        'PH.LOAN.STAT',
        'PH.START.AMRT.DATE',
        'PH.BILL.TRANS.TYPE',
        'PH.BIL.TRANS.STAT',
        'PH.BILL.MODE',
        'PH.ORIG.DEDNCODE',
        'PH.UPD.DEDNCODE',
        'PH.ORIG.NRI',
        'PH.UPDATED.NRI',
        'PH.BILL.REMARKS',
        'PH.BILL.STOPPAGE',
        'PH.ATM.CARD.NO',
        'PH.FULLNAME2',
        'PH.PENS.ACCT.NO',
        'PH.PAYMT.STAT',
        'PH.STOP.DEDN.CODE',
        'PH.INCLD.BIL.IND',
        'PH.1ST.LOAD.AMT',
        'AMOUNT.COLLECT',
        'PAY.PERIOD.COLLECT'];

        fputcsv($fp, $headerCSV );

            if($cod) {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BJMP' $aa")
                ->groupby("$a.id")
                ->limit($count,$offset)
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BJMP'")
                ->groupby("$a.id")
                ->limit($count,$offset)
                ->execute();
                }


            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info [] = array();

                foreach($getQry as $data){
                    //create a file handler by opening the file
                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $ctr /count($getQry) * 100;
                    echo "BILLING BJMP-AC - Exporting file : ".number_format($percent,2)."%";
                    // $cnt++;

                    $getQryMemberAccountFile = TblMemberAccountFile::findFirst(array(
                        "columns"    => "TSAcctTy, TSAccNo, AccountName",
                        "conditions" => "MemberNo = '$data->SapMemberNo'",
                    ));
                    if($getQryMemberAccountFile){
                        $b_TSAcctTy = $this->returnEmpty($getQryMemberAccountFile->TSAcctTy);
                        $b_TSAccNo  = $this->returnEmpty($getQryMemberAccountFile->TSAccNo);
                        $b_AccountName = $this->returnEmpty($getQryMemberAccountFile->AccountName);
                    }

                    $getQryAtmListFile = TblAtmListFile::findFirst(array(
                        "columns"    => "PICOSNO, REFERENCE, CONTROL, DATERCVD, PIN, ATMCARDSTAT, DATERELEASED, PULLOUTREASON",
                        "conditions" => "CLIENT = '$data->SapMemberNo'",
                    ));
                    if ($getQryAtmListFile) {
                        $c_PICOSNO          = $this->returnEmpty($getQryAtmListFile->PICOSNO);
                        $c_REFERENCE        = $this->returnEmpty($getQryAtmListFile->REFERENCE);
                        $c_CONTROL          = $this->returnEmpty($getQryAtmListFile->CONTROL);
                        $c_DATERCVD         = $this->returnEmpty($getQryAtmListFile->DATERCVD);
                        $c_PIN              = $this->returnEmpty($getQryAtmListFile->PIN);
                        $c_ATMCARDSTAT      = $this->returnEmpty($getQryAtmListFile->ATMCARDSTAT);
                        $c_DATERELEASED     = $this->returnEmpty($getQryAtmListFile->DATERELEASED);
                        $c_PULLOUTREASON    = $this->returnEmpty($getQryAtmListFile->PULLOUTREASON);
                    }

                    $getQryLoanBilling = TblLoanCsvFile::findFirst(array(
                        "columns" => "initDate, loanAmt, MOA1, MOA2, lproNo, lnType, dateGrant, maturity, startDate1, startDate2, loanAppl, PnPBillMod, loanProc, id_loan_csv_name",
                        "conditions" => "memberNo = '$data->SapMemberNo'",
                    ));
                    if ($getQryLoanBilling) {
                        $d_initDate     = $this->returnEmpty($getQryLoanBilling->initDate);
                        $d_loanAmt      = $this->returnEmpty($getQryLoanBilling->loanAmt);
                        $d_MOA1         = $this->returnEmpty($getQryLoanBilling->MOA1);
                        $d_MOA2         = $this->returnEmpty($getQryLoanBilling->MOA2);
                        $d_lproNo       = $this->returnEmpty(str_replace("-", "", $getQryLoanBilling->lproNo));
                        $d_lnType       = $this->returnEmpty($getQryLoanBilling->lnType);
                        $d_dateGrant    = $this->returnEmpty($getQryLoanBilling->dateGrant);
                        $d_maturity     = $this->returnEmpty($getQryLoanBilling->maturity);
                        $d_startDate1   = $this->returnEmpty($getQryLoanBilling->startDate1);
                        $d_startDate2   = $this->returnEmpty($getQryLoanBilling->startDate2);
                        $d_loanAppl     = $this->returnEmpty($getQryLoanBilling->loanAppl);
                        $d_PnPBillMod   = $this->returnEmpty($getQryLoanBilling->PnPBillMod);
                        $d_loanProc     = $this->returnEmpty($getQryLoanBilling->loanProc);
                        $d_id_loan_csv_name = $this->returnEmpty($getQryLoanBilling->id_loan_csv_name);
                    }

                    // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($d_id_loan_csv_name, trim($data->BranchSvc));

                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billAmnt == 0;
                    $SvcStat = trim($data->SvcStat);

                    $startDate = date('Y-m-d', strtotime($d_startDate1));
                    $endDate = date('Y-m-d',  strtotime($d_startDate2));
                    $dateGrant = date('Y-m-d', strtotime($d_dateGrant));

                    $amortLpro = (double)$d_MOA1;
                    $amortCollection = (double)$h_payment;
                    $dateGranted = $d_dateGrant;

                    //Get Group and Deduction Code
                    $group_service_status = $this->getGroupServiceStatus($SvcStat);
                    $group_loan_type = $this->getServiceStatusFromLnType($d_lnType);

                    // $ifClientExistsLpro = TblLoanCsvFile::findFirst("memberNo = '$data->SapMemberNo'");
                    // $lproDateGrant = $ifClientExistsLpro->dateGrant;
                    $lproDateGrant = $d_dateGrant;

                    $ifClientExistsCollection = TblCollectionBjmpAc::findFirst("pin_account_number = '$data->PINAcctNo'");
                    $dedCodeCollection = $ifClientExistsCollection->deduction_code_desc;
                    $dedCodeCollection = explode(' ',trim($dedCodeCollection));
                    $dedCodeCollection = $dedCodeCollection[0];
                        $h_payment = $this->returnEmpty($ifClientExistsCollection->payment);
                        $h_collection_pay_period = $this->returnEmpty($ifClientExistsCollection->collection_pay_period);
                        $h_deduction_code = $this->returnEmpty($ifClientExistsCollection->deduction_code);

                    //FOR BILLMODE
                    $getBillParams = $this->getBillParams($d_PnPBillMod,$amortLpro);
                    $billMode = $getBillParams["billMode"];

                    $getBillRemarks = $this->getBillRemarks($d_PnPBillMod,$dateGranted,$d_startDate1,$d_startDate2,$ifClientExistsCollection,$ifClientExistsLpro,$amortLpro,$amortCollection);
                    $billRemarks = $getBillParams["billRemarks"];
                    $billPage = $getBillParams["billPage"];

                    $amountCollect = $h_payment == "" ? 0 : $h_payment;

                     $dateGranted = $d_dateGrant;
                     $branchSvc = trim($data->BranchSvc);
                     $deduction_code = $this->getDeductionCode($group_service_status,$d_PnPBillMod,$d_lnType,$branchSvc);
                     $loanBillAmtNew = number_format($d_MOA1, 2, '.', '');

                     // If no data in LPRO Billing
                     if (!$getQryLoanBilling) {
                         $getLastData = TblLoanCsvFile::query()
                         ->columns('initDate, id_loan_csv_name')
                         ->limit(1)
                         ->orderBy("id Desc")
                         ->execute();

                         // PH.PAY.PERIOD
                         $payPayPeriod = $this->getPayPeriod(trim($getLastData[0]->id_loan_csv_name), trim($data->BranchSvc));

                         // PH.ORIG.CONT.DATE
                         $dateGranted = $getLastData[0]->initDate;

                         // PH.ORIG.DEDNCODE
                         if ($deduction_code == null || $deduction_code == "") {
                             $deduction_code = $h_deduction_code;
                         }

                         // PH.BILL.MODE
                         $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                         $billMode = $newBillMode["bill_mode"];

                         // PH.LOAN.ORIG.ID
                         if($billMode == "Bill to Loan"){
                             $d_lproNo = "";
                         }
                         else if($billMode=="Bill to CAPCON" || $billMode=="Bill to CASA" || $billMode=="Bill to Savings"){
                             $d_lproNo = str_replace("-", "", $b_AccountName);
                         } else {
                             $d_lproNo = "";
                         }

                         // PH.LOAN.BILL.AMT
                         $loanBillAmtNew = number_format($h_payment, 2, '.', '');
                     }

                    //  $nriVal = ($isAddLine == true ) ? "PENDING" : "";

                     //Adjust value of $billRemarks and $billPage according to BMJP AC
                     $isDateGrantedExisting = TblCollectionBjmpAc::findFirst("collection_pay_period = '$dateGranted'");
                     if (!$isDateGrantedExisting) {
                         $billRemarks = "A";
                         $billPage = "NO";
                     } else if ($loanBillAmtNew == 0) {
                         $billRemarks = "C";
                         $billPage = "YES";
                     } else {
                         $billRemarks = "B";
                         $billPage = "NO";
                     }
                     $startDate1 = $d_startDate1 == null ? "" : date('Ymd', strtotime($d_startDate1));
                    //Adding of New line (BILL AMOUNT PROCESS)
                    $newLine =  $this->processBillAmountGrpSDLIS(
                        "BJMPAC",
                        $d_PnPBillMod,
                        $d_MOA1,
                        $amortCollection,
                        $amortLpro,
                        $data->SapMemberNo,
                        $b_TSAcctTy,
                        $data->BranchSvc,
                        $data->PINAcctNo,
                        $data->T24MemberNo,
                        $data->LastName,
                        $data->FirstName,
                        $data->MiddleName,
                        $data->QualifNam,
                        $data->SvcStat,
                        $billAmnt,
                        $data->lnType,
                        date('Ymd', strtotime($dateGranted)),
                        $billMode,
                        $dedCodeCollection,
                        $nriVal,
                        $billRemarks,
                        $billPage,
                        $h_payment,
                        $h_collection_pay_period,
                        $newRecordArray,
                        $data->full_name,
                        $newRecordArray,
                        $loanBillAmtNewSDLIS,
                        $deduction_codeSDLIS,
                        $d_lproNo,
                        $amountCollect,
                        $payPayPeriod,
                        $d_loanProc,
                        $deduction_code
                    );

                     if((trim($d_lnType) != "BL") && (trim($d_lnType) != "NL")) {
                        if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {

                            $temp_info = array(
                                'up_company'            => 'PH0010002',
                                'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                                'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                                'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                                'full_name'             =>'',
                                'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                                'LastName'              => $this->returnEmpty($data->LastName),
                                'FirstName'             => $this->returnEmpty($data->FirstName),
                                'MiddleName'            => $this->returnEmpty($data->MiddleName),
                                'QualifNam'             => $this->returnEmpty($data->QualifNam),
                                'SvcStat'               => $this->returnEmpty($data->SvcStat),
                                'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                                'capcon_bill_amount'    => "",
                                'casa_bill_amount'      => "",
                                'psa_bill_amount'       => "",
                                'total_bill_amnt'       => $this->returnEmpty($billAmnt),
                                'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                                'lproNo'                => $this->returnEmpty($d_lproNo),
                                'lnType'                => "",
                                'loanAppl'              => "",
                                'maturityDate'          => "",
                                'dateGrant'             => date('Ymd', strtotime($dateGranted)),
                                'updContDate'           => "",
                                'loanTerm'              => "",
                                'origContDate'          => "",
                                'loanStat'              => $this->returnEmpty($d_loanProc),
                                'startAmrtDate'         => $this->returnEmpty($startDate1),
                                'billTransType'         => "",
                                'bilTransStat'          => "",
                                'billMode'              => $this->returnEmpty($billMode),
                                'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                                'updtDeDNCode'          => "",
                                'lnpTrate'              => $this->returnEmpty($nriVal),
                                'uptNri'                => "",
                                'Remarks'               => $this->returnEmpty($billRemarks),
                                'stop_page'             => $this->returnEmpty($billPage),
                                'atmCardNo'             => "",
                                'fullName2'             => "",
                                'pensAcctNo'            => "",
                                'payMtStats'            => "",
                                'stopDedNCode'          => "",
                                'incldBilInd'           => "YES",
                                '1stLoadAmt'            => "",
                                'collection_amount'     => $this->returnEmpty($h_payment),
                                'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)

                            );

                            if($filter == 'FULLY PAID'){
                                $ext = "_Fully Paid";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($d_lproNo == null || $d_lproNo == "") {
                                        if ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02") {
                                            $info2 [] = $temp_info;

                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                        }
                                    }

                                }
                            } else if($filter == 'NO ACCOUNT'){
                                $ext = "_No Account";

                                if ($group_loan_type == "ACTIVE") {
                                    if (($b_AccountName == null || $b_AccountName == "") &&
                                    ($d_PnPBillMod == "PBM03" || $d_PnPBillMod == "PBM04" || $d_PnPBillMod == "PBM05" || $d_PnPBillMod == "PBM06" || $d_PnPBillMod == "PBM07")) {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                            } else if($filter == 'OPTIONAL'){
                                $ext = "_Optional";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($SvcStat == "CO" || $SvcStat == "OP" || $SvcStat == "RD" || $SvcStat == "RE") {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                }
                            } else if($filter == 'NORMAL') {
                                $ext = "_Normal";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($b_AccountName != null &&
                                    $d_lproNo != null && ($SvcStat != "CO" && $SvcStat != "OP" && $SvcStat != "RD" && $SvcStat != "RE")) {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                }
                            } else {
                                if ($group_loan_type == "ACTIVE") {
                                    $info [] = $temp_info;

                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }

                            }

                            // array_push($info,$temp_info);
                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                            // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                            // fputcsv($fp, $temp_info );
                            $ctr++;

                            $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                            foreach($global_newline_array as $newLine){
                                    array_push($info,$newLine);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                            }


                        } else {
                            $temp_info = array(
                                'up_company'            =>'PH0010002',
                                'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                                'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                                'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                                'full_name'             =>'',
                                'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                                'LastName'              => $this->returnEmpty($data->LastName),
                                'FirstName'             => $this->returnEmpty($data->FirstName),
                                'MiddleName'            => $this->returnEmpty($data->MiddleName),
                                'QualifNam'             => $this->returnEmpty($data->QualifNam),
                                'SvcStat'               => $this->returnEmpty($data->SvcStat),
                                'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                                'capcon_bill_amount'    => "",
                                'casa_bill_amount'      => "",
                                'psa_bill_amount'       => "",
                                'total_bill_amnt'       => "",
                                'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                                'lproNo'                => $this->returnEmpty($d_lproNo),
                                'lnType'                => "",
                                'loanAppl'              => "",
                                'maturityDate'          => "",
                                'dateGrant'             => date('Ymd', strtotime($dateGranted)),
                                'updContDate'           => "",
                                'loanTerm'              => "",
                                'origContDate'          => "",
                                'loanStat'              => $this->returnEmpty($d_loanProc),
                                'startAmrtDate'         => $this->returnEmpty($startDate1),
                                'billTransType'         => "",
                                'bilTransStat'          => "",
                                'billMode'              => $this->returnEmpty($billMode),
                                'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                                'updtDeDNCode'          => "",
                                'lnpTrate'              => $this->returnEmpty($nriVal),
                                'uptNri'                =>"",
                                'Remarks'               => $this->returnEmpty($billRemarks),
                                'stop_page'             => $this->returnEmpty($billPage),
                                'atmCardNo'             => "",
                                'fullName2'             => "",
                                'pensAcctNo'            => "",
                                'payMtStats'            => "",
                                'stopDedNCode'          => "",
                                'incldBilInd'           => "YES",
                                '1stLoadAmt'            => "",
                                'collection_amount'     => $this->returnEmpty($h_payment),
                                'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)
                                // 'collection_dateGranted'    => date('Ymd', strtotime($h_colDateGranted))
                            );

                            if($filter == 'FULLY PAID'){
                                $ext = "_Fully Paid";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($d_lproNo == null || $d_lproNo == "") {
                                        if ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02") {
                                            $info2 [] = $temp_info;

                                            array_push($info,$temp_info);
                                            fputcsv($fp, $temp_info );
                                            $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                            fwrite($txt,$result);
                                        }
                                    }

                                }
                            } else if($filter == 'NO ACCOUNT'){
                                $ext = "_No Account";

                                if ($group_loan_type == "ACTIVE") {
                                    if (($b_AccountName == null || $b_AccountName == "") &&
                                    ($d_PnPBillMod == "PBM03" || $d_PnPBillMod == "PBM04" || $d_PnPBillMod == "PBM05" || $d_PnPBillMod == "PBM06" || $d_PnPBillMod == "PBM07")) {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                            } else if($filter == 'OPTIONAL'){
                                $ext = "_Optional";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($SvcStat == "CO" || $SvcStat == "OP" || $SvcStat == "RD" || $SvcStat == "RE") {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                }
                            } else if($filter == 'NORMAL') {
                                $ext = "_Normal";

                                if ($group_loan_type == "ACTIVE") {
                                    if ($b_AccountName != null &&
                                    $d_lproNo != null && ($SvcStat != "CO" && $SvcStat != "OP" && $SvcStat != "RD" && $SvcStat != "RE")) {
                                        $info2 [] = $temp_info;

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                }
                            } else {
                                if ($group_loan_type == "ACTIVE") {
                                    $info [] = $temp_info;

                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }

                            }


                            // array_push($info,$temp_info);
                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                            // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                            // fputcsv($fp, $temp_info );
                            $ctr++;

                        }
                    }
                        $global_newline_array = array();
                        $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                        $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;

                }

                $myTextFileHandler = fopen($path2,"r+");
                $d = ftruncate($myTextFileHandler, 0);
                fclose($myTextFileHandler);
                $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
                echo $fnlRes;
                exit;
            }
    }

    public function exportBillingBjmpReAction() {
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // $ctr =0;

        ini_set('memory_limit', '-1');
        // $date = date("YmdHis");
        // $filter = file_get_contents("/var/www/html/psslai/public/params/params.txt");
        // // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBilling.log";
        // $path2 = "/var/www/html/psslai/public/export/fileLog_tempBillingBjmpRe.log";
        // $pathtxt = "/var/www/html/psslai/public/export/BILLING_BJMP-RE_ao".$date.".txt";
        // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
        // $exportCsv1 = 'export/BILLING_BJMP-RE_ao'.$date.'.csv';
        // $exportTxt2 = 'export/BILLING_BJMP-RE_ao'.$date.'.txt';
        //
        // $ctr =0;
        // $info = array();
        // $payPayPeriod = "";
        // $fp = fopen('/var/www/html/psslai/public/export/BILLING_BJMP-RE_ao'.$date.'.csv', 'w');


        $rootPath = "/var/www/html/psslai/";
    //    $rootPath = "/var/www/psslai/";
       $exportFileName = "export/BILLING_BJMP-RE_ao";
       $logFileName = "export/fileLog_tempBillingBjmpRe.log";
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $counter = 0;
       $date = date("YmdHis");
       $filter = file_get_contents($rootPath."public/params/params.txt");
       $path2 = $rootPath."public/".$logFileName;
       $pathtxt = $rootPath."public/".$exportFileName.$date.".txt";
       // $txt = fopen($pathtxt, "w") or die("Unable to open file!");
       $exportCsv1 = $exportFileName.$date.'.csv';
       $exportTxt2 = $exportFileName.$date.'.txt';

        defined('APP_PATH') || define('APP_PATH', realpath('.'));
         $date = date("YmdHis");


        $ctr = 1;

        $a = "TblMemberInfoFile";
        $b = "TblMemberAccountFile";
        $c = "TblAtmListFile";
        $d = "TblLoanCsvFile"; //Loan Billing
        $e = "TblLoanAtmFile"; //Loan Atm
        $f = "TblDeductionCode"; //Collection
        $g = "TblBillMode";
        $h = "TblCollectionBjmpRe";
        $ss = "TblServiceStatus";
        $tbl_branchSvc = "TblBos";
        $tbl_loanType = "TblLoanType";
        $billingType = "Bjmp";
        $ext = "";
        $aa = "";
        $bb = "";
        $dd = "";

        if($filter == 'FULLY PAID'){
            $cod = " ($d.lproNo IS null OR $d.lproNo = '') AND ($d.PnPBillMod = 'PBM00' OR $d.PnPBillMod = 'PBM01' OR $d.PnPBillMod = 'PBM02')";
            $ext = "_Fully Paid";
        } else if($filter == 'NO ACCOUNT'){
            $cod = " ($b.AccountName IS null OR $b.AccountName = '') AND ($d.PnPBillMod = 'PBM03' OR $d.PnPBillMod = 'PBM04' OR $d.PnPBillMod = 'PBM05' OR $d.PnPBillMod = 'PBM06' OR $d.PnPBillMod = 'PBM07')";
            $ext = "_No Account";
        } else if($filter == 'OPTIONAL'){
            $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $cod = " ($d.lnType != 'PL' AND $d.lnType != 'AP' AND $d.lnType != 'OP') AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $ext = "_Optional";
        } else if($filter == 'NORMAL') {
            $aa = " AND ($a.SvcStat = 'CO' OR $a.SvcStat = 'OP' OR $a.SvcStat = 'RD' OR $a.SvcStat = 'RE')";
            $cod = " $d.lproNo IS NOT null AND $b.AccountName IS NOT null AND
                ($d.lnType = 'PL' OR $d.lnType = 'AP' OR $d.lnType = 'OP') AND ($a.SvcStat != 'CO' OR $a.SvcStat != 'OP' OR $a.SvcStat != 'RD' OR $a.SvcStat != 'RE')";
            $ext = "_Normal";
        }

        $exportCsv  = 'export/BILLING_BJMP-RE_ao'.$date.$ext.'.csv';
        $exportTxt  = 'export/BILLING_BJMP-RE_ao'.$date.$ext.'.txt';
        $exportCsv1 = 'export/BILLING_BJMP-RE_ao'.$date.$ext.'.csv';
        $exportTxt2 = 'export/BILLING_BJMP-RE_ao'.$date.$ext.'.txt';
        $txt = fopen($exportTxt2, "w") or die("Unable to open file!");

        $fp = fopen($rootPath.'public/export/BILLING_BJMP-RE_ao'.$date.$ext.'.csv', 'w');

        $headerCSV = ['UPLOAD.COMPANY',
        'PH.BRANCH.SVC',
        'PH.PAY.PERIOD',
        'PH.PIN.NO',
        'PH.FULLNAME1',
        'CUSTOMER.CODE',
        'PH.LAST.NAME',
        'PH.FIRST.NAME',
        'PH.MIDDLE.NAME',
        'PH.QUAL.NAME',
        'PH.SERVICE.STATUS',
        'PH.LOAN.BILL.AMT',
        'PH.CAPCON.BILL.AMT',
        'PH.CASA.BILL.AMT',
        'PH.PSA.BILL.AMT',
        'PH.TOTAL.BILL.AMT',
        'PH.BILL.AMT',
        'PH.LOAN.ORIG.ID',
        'PH.LOAN.TYPE',
        'PH.APP.TYPE',
        'PH.MATURITY.DATE',
        'PH.ORIG.CONT.DATE',
        'PH.UPD.CONT.DATE',
        'PH.LOAN.TERM',
        'PH.ORIG.CONT.AMT',
        'PH.LOAN.STAT',
        'PH.START.AMRT.DATE',
        'PH.BILL.TRANS.TYPE',
        'PH.BIL.TRANS.STAT',
        'PH.BILL.MODE',
        'PH.ORIG.DEDNCODE',
        'PH.UPD.DEDNCODE',
        'PH.ORIG.NRI',
        'PH.UPDATED.NRI',
        'PH.BILL.REMARKS',
        'PH.BILL.STOPPAGE',
        'PH.ATM.CARD.NO',
        'PH.FULLNAME2',
        'PH.PENS.ACCT.NO',
        'PH.PAYMT.STAT',
        'PH.STOP.DEDN.CODE',
        'PH.INCLD.BIL.IND',
        'PH.1ST.LOAD.AMT',
        'AMOUNT.COLLECT',
        'PAY.PERIOD.COLLECT'];

        fputcsv($fp, $headerCSV );



            if($cod) {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BJMP' $aa")
                ->groupby("$a.id")
                ->limit($count,$offset)
                ->execute();
            } else {
                $getQry = TblMemberInfoFile::query()
                ->columns("$a.id,$a.SapMemberNo,CONCAT($a.LastName,', ',$a.FirstName,' ',$a.MiddleName) AS full_name,$a.BranchSvc,$a.SvcStat,
                $a.LastName,$a.FirstName,$a.MiddleName,$a.QualifNam,$a.PINAcctNo,$a.T24MemberNo
                ")
                ->where("$a.BranchSvc = 'BJMP'")
                ->groupby("$a.id")
                ->limit($count,$offset)
                ->execute();
            }

            if($getQry){
                $nriVal = 0;
                $count = 0;
                $cnt = 0;
                $deduction_code = "";
                $newRecordArray = array();
                $info [] = array();

                foreach($getQry as $data){
                    //create a file handler by opening the file
                    $myTextFileHandler = fopen($path2,"r+");
                    $d = ftruncate($myTextFileHandler, 0);
                    fclose($myTextFileHandler);
                    $percent = $ctr /count($getQry) * 100;
                    echo "BILLING BJMP-RE - Exporting file : ".number_format($percent,2)."%";

                    $getQryMemberAccountFile = TblMemberAccountFile::findFirst(array(
                        "columns"    => "TSAcctTy, TSAccNo, AccountName",
                        "conditions" => "MemberNo = '$data->SapMemberNo'",
                    ));
                    if($getQryMemberAccountFile){
                        $b_TSAcctTy = $this->returnEmpty($getQryMemberAccountFile->TSAcctTy);
                        $b_TSAccNo  = $this->returnEmpty($getQryMemberAccountFile->TSAccNo);
                        $b_AccountName = $this->returnEmpty($getQryMemberAccountFile->AccountName);
                    }

                    $getQryAtmListFile = TblAtmListFile::findFirst(array(
                        "columns"    => "PICOSNO, REFERENCE, CONTROL, DATERCVD, PIN, ATMCARDSTAT, DATERELEASED, PULLOUTREASON",
                        "conditions" => "CLIENT = '$data->SapMemberNo'",
                    ));
                    if ($getQryAtmListFile) {
                        $c_PICOSNO          = $this->returnEmpty($getQryAtmListFile->PICOSNO);
                        $c_REFERENCE        = $this->returnEmpty($getQryAtmListFile->REFERENCE);
                        $c_CONTROL          = $this->returnEmpty($getQryAtmListFile->CONTROL);
                        $c_DATERCVD         = $this->returnEmpty($getQryAtmListFile->DATERCVD);
                        $c_PIN              = $this->returnEmpty($getQryAtmListFile->PIN);
                        $c_ATMCARDSTAT      = $this->returnEmpty($getQryAtmListFile->ATMCARDSTAT);
                        $c_DATERELEASED     = $this->returnEmpty($getQryAtmListFile->DATERELEASED);
                        $c_PULLOUTREASON    = $this->returnEmpty($getQryAtmListFile->PULLOUTREASON);
                    }

                    $getQryLoanBilling = TblLoanCsvFile::findFirst(array(
                        "columns" => "initDate, loanAmt, MOA1, MOA2, lproNo, lnType, dateGrant, maturity, startDate1, startDate2, loanAppl, PnPBillMod, loanProc, id_loan_csv_name",
                        "conditions" => "memberNo = '$data->SapMemberNo'",
                    ));
                    if ($getQryLoanBilling) {
                        $d_initDate     = $this->returnEmpty($getQryLoanBilling->initDate);
                        $d_loanAmt      = $this->returnEmpty($getQryLoanBilling->loanAmt);
                        $d_MOA1         = $this->returnEmpty($getQryLoanBilling->MOA1);
                        $d_MOA2         = $this->returnEmpty($getQryLoanBilling->MOA2);
                        $d_lproNo       = $this->returnEmpty(str_replace("-", "", $getQryLoanBilling->lproNo));
                        $d_lnType       = $this->returnEmpty($getQryLoanBilling->lnType);
                        $d_dateGrant    = $this->returnEmpty($getQryLoanBilling->dateGrant);
                        $d_maturity     = $this->returnEmpty($getQryLoanBilling->maturity);
                        $d_startDate1   = $this->returnEmpty($getQryLoanBilling->startDate1);
                        $d_startDate2   = $this->returnEmpty($getQryLoanBilling->startDate2);
                        $d_loanAppl     = $this->returnEmpty($getQryLoanBilling->loanAppl);
                        $d_PnPBillMod   = $this->returnEmpty($getQryLoanBilling->PnPBillMod);
                        $d_loanProc     = $this->returnEmpty($getQryLoanBilling->loanProc);
                        $d_id_loan_csv_name = $this->returnEmpty($getQryLoanBilling->id_loan_csv_name);
                    }

                    $getQryCollection = TblCollectionBjmpRe::findFirst(array(
                        "columns" => "payment,collection_pay_period",
                        "conditions" => "pan_account_number = '$data->PINAcctNo'",
                    ));
                    if ($getQryCollection) {
                        $h_payment = $this->returnEmpty($getQryCollection->payment);
                        $h_collection_pay_period = $this->returnEmpty($getQryCollection->collection_pay_period);
                    }

                    $loanBillAmt = 0;
                    $loanBillAmtNew = 0;
                    $billAmnt == 0;
                    $SvcStat = trim($data->SvcStat);
                    //Get Group and Deduction Code
                    $group_service_status = $this->getGroupServiceStatus($SvcStat);
                    $group_loan_type = $this->getServiceStatusFromLnType($d_lnType);

                    //Check billmode, billremarks and billpage values
                    $billParams = $this->getBillParams($d_PnPBillMod,$billAmnt);
                    $billMode = $billParams["billMode"];
                    $billRemarks = $billParams["billRemarks"];
                    $billPage = $billParams["billPage"];

                    $amountCollect = $h_payment == false ? 0 : $h_payment;
                    $amortLpro = (double)$d_MOA1;
                    $amortCollection = (double)$h_payment;
                    // print_r("($data->LastName,$amortLpro,$amortCollection)");

                    $dateGranted = $d_dateGrant;
                    $branchSvc = trim($data->BranchSvc);
                    $deduction_code = $this->getDeductionCode($group_service_status,$d_PnPBillMod,$d_lnType,$branchSvc);
                    // print_r("SErvice Status: $group_service_status, PnPBillMod: $data->PnPBillMod, lnType: $data->lnType, branchSvc: $branchSvc \n");

                    $loanBillAmtNew = number_format($d_MOA1, 2, '.', '');
                    $nriVal = ($isAddLine == true ) ? "PENDING" : "";

                    //Adjust value of $billRemarks and $billPage according to BMJP RE
                    $isDateGrantedExisting = TblCollectionBjmpRe::findFirst("collection_pay_period = '$d_dateGrant'");
                    if (!$isDateGrantedExisting) {
                        $billRemarks = "A";
                        $billPage = "NO";
                    } else if ($loanBillAmtNew == 0) {
                        $billRemarks = "C";
                        $billPage = "YES";
                    } else {
                        $billRemarks = "B";
                        $billPage = "NO";
                    }

                    // Getting the Pay Period
                    $payPayPeriod = $this->getPayPeriod($d_id_loan_csv_name, trim($data->BranchSvc));

                    // If no data in LPRO Billing
                    if (!$getQryLoanBilling) {
                        $getLastData = TblLoanCsvFile::query()
                        ->columns('initDate, id_loan_csv_name')
                        ->limit(1)
                        ->orderBy("id Desc")
                        ->execute();

                        // PH.PAY.PERIOD
                        $payPayPeriod = $this->getPayPeriod(trim($getLastData[0]->id_loan_csv_name), trim($data->BranchSvc));

                        // PH.ORIG.CONT.DATE
                        $dateGranted = $getLastData[0]->initDate;

                        // PH.ORIG.DEDNCODE
                        if ($deduction_code == null || $deduction_code == "") {
                            $deduction_code = $h_deduction_code;
                        }

                        // PH.BILL.MODE
                        $newBillMode = $this->getBillMode($group_service_status,$deduction_code,$branchSvc);
                        $billMode = $newBillMode["bill_mode"];

                        // PH.LOAN.ORIG.ID
                        if($billMode == "Bill to Loan"){
                            $d_lproNo = "";
                        }
                        else if($billMode=="Bill to CAPCON" || $billMode=="Bill to CASA" || $billMode=="Bill to Savings"){
                            $d_lproNo = str_replace("-", "", $b_AccountName);
                        } else {
                            $d_lproNo = "";
                        }

                        // PH.LOAN.BILL.AMT
                        $loanBillAmtNew = number_format($h_payment, 2, '.', '');
                    }
                    $startDate1 = $d_startDate1 == null ? "" : date('Ymd', strtotime($d_startDate1));
                     //Adding of New line (BILL AMOUNT PROCESS)
                     $newLine =  $this->processBillAmountGrpSDLIS(
                        "BJMPRE",
                        $d_PnPBillMod,
                        $d_MOA1,
                        $amortCollection,
                        $amortLpro,
                        $data->SapMemberNo,
                        $b_TSAcctTy,
                        $data->BranchSvc,
                        $data->PINAcctNo,
                        $data->T24MemberNo,
                        $data->LastName,
                        $data->FirstName,
                        $data->MiddleName,
                        $data->QualifNam,
                        $data->SvcStat,
                        $billAmnt,
                        $d_lnType,
                        date('Ymd', strtotime($dateGranted)),
                        $billMode,
                        $deduction_code,
                        $nriVal,
                        $billRemarks,
                        $billPage,
                        $h_payment,
                        $h_collection_pay_period,
                        $newRecordArray,
                        $data->full_name,
                        $newRecordArray,
                        null,
                        null,
                        $d_lproNo,
                        $h_payment,
                        $payPayPeriod,
                        $d_loanProc,
                        $deduction_code,
                        $filter,
                        $b_AccountName,
                        $startDate1);


                        if($GLOBALS['GLOBAL_ISADDNEWLINE'] == true ) {
                            $temp_info = array(
                                'up_company'            => 'PH0010002',
                                'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                                'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                                'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                                'full_name'             => '',
                                'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                                'LastName'              => $this->returnEmpty($data->LastName),
                                'FirstName'             => $this->returnEmpty($data->FirstName),
                                'MiddleName'            => $this->returnEmpty($data->MiddleName),
                                'QualifNam'             => $this->returnEmpty($data->QualifNam),
                                'SvcStat'               => $this->returnEmpty($data->SvcStat),
                                'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                                'capcon_bill_amount'    => "",
                                'casa_bill_amount'      => "",
                                'psa_bill_amount'       => "",
                                'total_bill_amnt'       => $this->returnEmpty($billAmnt),
                                'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                                'lproNo'                => $this->returnEmpty($d_lproNo),
                                'lnType'                => "", //lnType,
                                'loanAppl'              => "",
                                'maturityDate'          => "",
                                'dateGrant'             => date('Ymd', strtotime($dateGranted)),
                                'updContDate'           => "",
                                'loanTerm'              => "",
                                'origContDate'          => "",
                                'loanStat'              => $this->returnEmpty($d_loanProc),
                                'startAmrtDate'         => $this->returnEmpty($startDate1),
                                'billTransType'         => "",
                                'bilTransStat'          => "",
                                'billMode'              => $this->returnEmpty($billMode),
                                'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                                'updtDeDNCode'          => "",
                                'lnpTrate'              => $this->returnEmpty($nriVal),
                                'uptNri'                => "",
                                'Remarks'               => $this->returnEmpty($billRemarks),
                                'stop_page'             => $this->returnEmpty($billPage),
                                'atmCardNo'             => "",
                                'fullName2'             => "",
                                'pensAcctNo'            => "",
                                'payMtStats'            => "",
                                'stopDedNCode'          => "",
                                'incldBilInd'           => "YES",
                                '1stLoadAmt'            => "",
                                'collection_amount'     => $this->returnEmpty($amountCollect),
                                'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)

                            );

                            if($filter == 'FULLY PAID'){
                                $ext = "_Fully Paid";

                                if ($group_loan_type == "PENSION") {
                                    if (($d_lproNo == null || $d_lproNo == "") && ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02")) {
                                        $info2 [] = $temp_info;

                                        // array_push($info,$temp_info);
                                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                        // fputcsv($fp, $temp_info );

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                            } else if($filter == 'NO ACCOUNT'){
                                $ext = "_No Account";

                                if ($group_loan_type == "PENSION") {
                                    if (($b_AccountName == null || $b_AccountName == "") &&
                                    ($d_PnPBillMod == "PBM03" || $d_PnPBillMod == "PBM04" || $d_PnPBillMod == "PBM05" || $d_PnPBillMod == "PBM06" || $d_PnPBillMod == "PBM07")) {
                                        $info2 [] = $temp_info;

                                        // array_push($info,$temp_info);
                                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                        // fputcsv($fp, $temp_info );

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }

                                }
                            // } else if($filter == 'OPTIONAL'){
                            //     $ext = "_Optional";
                            //
                            //     if ($d_lnType != "PL" AND $d_lnType != "AP" AND $d_lnType != "OP") {
                            //         $info [] = $temp_info;
                            //
                            //         array_push($info,$temp_info);
                            //         $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                            //         fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                            //         fputcsv($fp, $temp_info );
                            //     }
                            } else if($filter == 'NORMAL') {
                                $ext = "_Normal";

                                if ($group_loan_type == "PENSION") {
                                    if ($b_AccountName != null &&
                                    $d_lproNo != null && ($SvcStat == "CO" || $SvcStat == "OP" || $SvcStat == "RD" || $SvcStat == "RE")) {
                                        $info2 [] = $temp_info;

                                        // array_push($info,$temp_info);
                                        // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                        // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                        // fputcsv($fp, $temp_info );

                                        array_push($info,$temp_info);
                                        fputcsv($fp, $temp_info );
                                        $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                        fwrite($txt,$result);
                                    }
                                }
                            } else {
                                if ($group_loan_type == "PENSION") {
                                    $info [] = $temp_info;

                                    // array_push($info,$temp_info);
                                    // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                    // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                    // fputcsv($fp, $temp_info );

                                    array_push($info,$temp_info);
                                    fputcsv($fp, $temp_info );
                                    $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                    fwrite($txt,$result);
                                }

                            }


                            // array_push($info,$temp_info);
                            // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                            // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                            // fputcsv($fp, $temp_info );
                            $ctr++;

                        if ($group_loan_type == "PENSION") {  
                            $global_newline_array = $GLOBALS['GLOBAL_NEWLINE_ARRAY'];
                            foreach($global_newline_array as $newLine){
                                    array_push($info,$newLine);
                                    fputcsv($fp, $newLine );
                                    $result = utf8_encode(implode("|",$newLine)."\r\n");
                                    fwrite($txt,$result);
                            }
                        }

                         } else {
                             $temp_info = array(
                                 'up_company'            =>'PH0010002',
                                 'BranchSvc'             => $this->returnEmpty($data->BranchSvc),
                                 'PayPeriod'             => $this->returnEmpty($payPayPeriod),
                                 'PIN'                   => $this->returnEmpty($data->PINAcctNo),
                                 'full_name'             => '',
                                 'MemberStat'            => $this->returnEmpty($data->T24MemberNo),
                                 'LastName'              => $this->returnEmpty($data->LastName),
                                 'FirstName'             => $this->returnEmpty($data->FirstName),
                                 'MiddleName'            => $this->returnEmpty($data->MiddleName),
                                 'QualifNam'             => $this->returnEmpty($data->QualifNam),
                                 'SvcStat'               => $this->returnEmpty($data->SvcStat),
                                 'loan_bill_amount'      => $this->returnEmpty($loanBillAmtNew),
                                 'capcon_bill_amount'    => "",
                                 'casa_bill_amount'      => "",
                                 'psa_bill_amount'       => "",
                                 'total_bill_amnt'       => "",
                                 'bill_amt'              => $this->returnEmpty($loanBillAmtNew),
                                 'lproNo'                => $this->returnEmpty($d_lproNo),
                                 'lnType'                => "", //lnType,
                                 'loanAppl'              => "",
                                 'maturityDate'          => "",
                                 'dateGrant'             => date('Ymd', strtotime($dateGranted)),
                                 'updContDate'           => "",
                                 'loanTerm'              => "",
                                 'origContDate'          => "",
                                 'loanStat'              => $this->returnEmpty($d_loanProc),
                                 'startAmrtDate'         => $this->returnEmpty($startDate1),
                                 'billTransType'         =>"",
                                 'bilTransStat'          => "",
                                 'billMode'              => $this->returnEmpty($billMode),
                                 'orgDeDNCode'           => $this->returnEmpty($deduction_code),
                                 'updtDeDNCode'          => "",
                                 'lnpTrate'              => $this->returnEmpty($nriVal),
                                 'uptNri'                => "",
                                 'Remarks'               => $this->returnEmpty($billRemarks),
                                 'stop_page'             => $this->returnEmpty($billPage),
                                 'atmCardNo'             => "",
                                 'fullName2'             => "",
                                 'pensAcctNo'            => "",
                                 'payMtStats'            => "",
                                 'stopDedNCode'          => "",
                                 'incldBilInd'           => "YES",
                                 '1stLoadAmt'            => "",
                                 'collection_amount'     => $this->returnEmpty($amountCollect),
                                 'collection_pay_period' => $this->returnEmpty($h_collection_pay_period)
                             );

                             if($filter == 'FULLY PAID'){
                                 $ext = "_Fully Paid";

                                 if ($group_loan_type == "PENSION") {
                                     if (($d_lproNo == null || $d_lproNo == "") && ($d_PnPBillMod == "PBM00" || $d_PnPBillMod == "PBM01" || $d_PnPBillMod == "PBM02")) {
                                         $info2 [] = $temp_info;

                                         // array_push($info,$temp_info);
                                         // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                         // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                         // fputcsv($fp, $temp_info );

                                         array_push($info,$temp_info);
                                         fputcsv($fp, $temp_info );
                                         $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                         fwrite($txt,$result);
                                     }

                                 }
                             } else if($filter == 'NO ACCOUNT'){
                                 $ext = "_No Account";

                                 if ($group_loan_type == "PENSION") {
                                     if (($b_AccountName == null || $b_AccountName == "") &&
                                     ($d_PnPBillMod == "PBM03" || $d_PnPBillMod == "PBM04" || $d_PnPBillMod == "PBM05" || $d_PnPBillMod == "PBM06" || $d_PnPBillMod == "PBM07")) {
                                         $info2 [] = $temp_info;

                                         // array_push($info,$temp_info);
                                         // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                         // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                         // fputcsv($fp, $temp_info );

                                         array_push($info,$temp_info);
                                         fputcsv($fp, $temp_info );
                                         $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                         fwrite($txt,$result);
                                     }

                                 }
                             // } else if($filter == 'OPTIONAL'){
                             //     $ext = "_Optional";
                             //
                             //     if ($d_lnType != "PL" AND $d_lnType != "AP" AND $d_lnType != "OP") {
                             //         $info [] = $temp_info;
                             //
                             //         array_push($info,$temp_info);
                             //         $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                             //         fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                             //         fputcsv($fp, $temp_info );
                             //     }
                             } else if($filter == 'NORMAL') {
                                 $ext = "_Normal";

                                 if ($group_loan_type == "PENSION") {
                                     if ($b_AccountName != null &&
                                     $d_lproNo != null && ($SvcStat == "CO" || $SvcStat == "OP" || $SvcStat == "RD" || $SvcStat == "RE")) {
                                         $info2 [] = $temp_info;

                                         // array_push($info,$temp_info);
                                         // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                         // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                         // fputcsv($fp, $temp_info );

                                         array_push($info,$temp_info);
                                         fputcsv($fp, $temp_info );
                                         $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                         fwrite($txt,$result);
                                     }
                                 }
                             } else {
                                 if ($group_loan_type == "PENSION") {
                                     $info [] = $temp_info;

                                     // array_push($info,$temp_info);
                                     // $finalOutput = str_replace('"','',str_replace(',',"|",str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($temp_info))))));
                                     // fwrite($txt, str_replace($removeText,"",$finalOutput). "\r\n");
                                     // fputcsv($fp, $temp_info );

                                     array_push($info,$temp_info);
                                     fputcsv($fp, $temp_info );
                                     $result = utf8_encode(implode("|",$temp_info)."\r\n");
                                     fwrite($txt,$result);
                                 }

                             }


                            $ctr++;
                         }



                         $global_newline_array = array();
                         $GLOBALS['GLOBAL_NEWLINE_ARRAY'] = array();
                         $GLOBALS['GLOBAL_ISADDNEWLINE'] = false;

                }



                $myTextFileHandler = fopen($path2,"r+");
                $d = ftruncate($myTextFileHandler, 0);
                fclose($myTextFileHandler);
                $fnlRes = "success,".$exportCsv1.",".$exportTxt2;
                echo $fnlRes;
                exit;
            }

    }

    protected function toTextFile($rawData, $delimiter, $objTitle, $module, $filter,$headerCSV,$exportCsv){
            // header('Content-Type: text/plain; charset=utf-8');
        $string = "";
        if($module == "PNPAC"){
            $fp = fopen($exportCsv, 'w');
        } else if($module == "PNPRE"){
            $fp = fopen($exportCsv, 'w');
        } else if($module == "BFPAC"){
            $fp = fopen($exportCsv, 'w');
        } else if($module == "BFPRE"){
            $fp = fopen($exportCsv, 'w');
        } else if($module == "BJMPAC"){
            $fp = fopen($exportCsv, 'w');
        } else if($module == "BJMPRE"){
            $fp = fopen($exportCsv, 'w');
        } else if ($module == "ATMRECON") {
            $fp = fopen($exportCsv, 'w');
        } else if ($module == "ATMINVENTORY") {
            $fp = fopen($exportCsv, 'w');
        } else if ($module == "PSSLAI") {
            $fp = fopen($exportCsv, 'w');
        } else if ($module == "ATM"){
            $fp = fopen($exportCsv, 'w');
        } else if ($module == "NAPOLCOM"){
            $fp = fopen($exportCsv, 'w');
        }
        fputcsv($fp, $headerCSV);
        foreach($rawData as $data){
            // $result = mb_convert_encoding(json_encode($data), 'UTF-16LE', 'UTF-8');
            // // print_r($result);
            // $line = array_map("utf8_decode", $data);
            fputcsv($fp, $data  );
            $stringData = str_replace('"','',str_replace(',',$delimiter,str_replace('}','',str_replace('{','',str_replace('\\', '', json_encode($data))))));
            foreach($objTitle as $title){
                $stringData = str_replace("$title:",'',$stringData);
            }
            // $string     = $string . html_entity_decode($stringData) . "\r\n";

            $string     = $string . html_entity_decode(preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $stringData)) . "\r\n";
        }

        return $string;
    }

    protected function _cache(){
        $frontCache = new \Phalcon\Cache\Frontend\Data(array(
      			'lifetime' => 7200
      	));

      	$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
      			'cacheDir' => $this->config->application->cacheDir
      	));
      	return $cache;
    }


}
