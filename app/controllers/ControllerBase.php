<?php

use Phalcon\Mvc\Controller;

use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Syslog as SyslogAdapter;
use \Phalcon\Logger\Adapter;
use \Phalcon\Logger\AdapterInterface;
use Phalcon\Filter;

class ControllerBase extends Controller
{

    protected function _cache(){
        $frontCache = new \Phalcon\Cache\Frontend\Data(array(
      			'lifetime' => 7200
      	));

      	$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
      			'cacheDir' => $this->config->application->cacheDir
      	));
      	return $cache;
    }
    protected function _log($type){
        if($type == 'E'){
            //$logger = new SyslogAdapter("plesk-api-error");
            $logger =new \Phalcon\Logger\Adapter\File(__DIR__."/../logs/error.log");
        }
        else{
            //$logger = new SyslogAdapter("plesk-api-info");
            $logger =new \Phalcon\Logger\Adapter\File(__DIR__."/../logs/info.log");
        }
        return $logger;
    }

    protected function _respondError($error_code,$msg = NULL){
    $this->response->setStatusCode (500, 'Invalid Access' )->sendHeaders ();
        if(is_null($msg)){
             echo json_encode(array(
                  'error_code'   => $error_code
              ));
        }else{
             echo json_encode(array(
                  'error_code'   => $error_code,
                  'devMessage'	 => $msg
              ));
        }
         exit;
    }


    protected function _respondError2($error_code,$msg = NULL){
        $this->response->setStatusCode ($error_code, 'Invalid Access' )->sendHeaders ();
            if(is_null($msg)){
                 echo json_encode(array(
                      'error_code'   => $error_code
                  ));
            }else{
                 echo json_encode(array(
                      'error_code'   => $error_code,
                      'devMessage'	 => $msg
                  ));
            }
             exit;
        }

    protected function _respond($msg){
        if(is_null($msg)){
            echo json_encode(array(
                'error_code'   => 0
            ));
        }else{
            echo json_encode(array(
                'error_code'   => 0,
                'devMessage'   => $msg
            ));
        }

        exit;
    }
    protected function respond($devMessage){
        $this->response->setStatusCode(200, "Ok")->sendHeaders();
        echo json_encode($devMessage);
    }
    protected function _getDateTime(){
        return date('Y-m-d H:i:s');
    }

    protected function _respondInvalid($errorCode){
        $this->response->setStatusCode (200, 'Invalid Access' )->sendHeaders ();
        echo json_encode(array(
            'error_code'	 => $errorCode,
            'msg'            => $this->config->error_code[$errorCode]
        ));
        exit;
    }

    /**
     * Generates token per session
     */
    protected function _generateToken($type)
    {
        $this->session->set('sessionToken' . $type, [
            'token_key' => $this->security->getTokenKey(),
            'token_value' => $this->security->getToken()
        ]);
    }

    /**
     * Checks token given values against session values
     *
     * @param $tokenKey
     * @param $tokenValue
     * @return bool
     */
    protected function _checkToken($type, $tokenKey, $tokenValue)
    {
        echo($type);
        echo($tokenKey);
        echo($tokenValue);
        exit();
        if ($this->session->has('sessionToken' . $type)) {
            $token = $this->session->get('sessionToken' . $type);
            if ($token['token_key'] == $tokenKey && $token['token_value'] == $tokenValue) {
                return true;
            }
            $this->_respondInvalid(4);
        }
        $this->_respondInvalid(4);
    }


    /**
     * Checks if user have token or not
     *
     * @return bool
     */
    protected function _doesUserHaveToken($type)
    {
        if ($this->session->has('sessionToken' . $type)) {
            return true;
        }
        return false;
    }
    /**
     * Gets token values from session
     *
     * @return array|bool
     */
    protected function _getToken($type)
    {
        if ($this->session->has('sessionToken' . $type)) {
            $token = $this->session->get('sessionToken' . $type);
            return [
                'token_key' => $token['token_key'],
                'token_value' => $token['token_value']
            ];
        }
        return false;
    }
    /**
    * @param    int $roLimit    required
    * @param    int $page   required
    * @param    int $total  required
    * @return   paginated array
    */
    protected function paginateDisplay($rowlimit,$page,$total){
        $mid_range      =   7;
        $itemPerPage    =   $rowlimit;
        $noOfPage       =   ceil($total/$itemPerPage);
        $end_range      =   0;
        $start_range    =   0;
        $current_page   =   (int) $page;
        if($current_page < 1 Or !is_numeric($current_page)) $current_page = 1;
        if($current_page > $noOfPage) $current_page = $noOfPage;
        $prev_page = $current_page-1;
        $next_page = $current_page+1;
        if($noOfPage > 10){
            $start_range = $current_page - floor($mid_range/2);
            $end_range = $current_page + floor($mid_range/2);

            if($start_range <= 0){
                $end_range += abs($start_range)+1;
                $start_range = 1;
            }
            if($end_range > $noOfPage){
                $start_range -= $end_range-$noOfPage;
                $end_range = $noOfPage;
            }
        }
        $range = array($start_range, $end_range,$mid_range);
        return array(
            'noOfPage'             => $noOfPage,
            'currentPage'         => $current_page,
            'nextPage'            => $next_page,
            'prevPage'            => $prev_page,
            'range'                => $range
        );
    }
    /**
    * @param    int $key    required
    * @param    alnum   $crypt  required
    * @return   boolean
    */
    protected function crackDepend($key,$crypt){
        $explodCeypt    =   explode('_',$crypt);
        if(count($explodCeypt) == 2){
            $incrypt    =   $explodCeypt[1];
            $sha1       =   $explodCeypt[0];
            $cryptKey   =   sha1(md5($key."GodIsGoodAllTheTime".$incrypt));

            if(md5($cryptKey) === md5($sha1)){return true;}
            else{ return false; }
        }
        else{
            return false;
        }
    }
    /**
    * @param    int $key    required
    * @return   alphanumeric
    */
    protected function crackDependMaker($key){
        $incrypt    =   bin2hex(openssl_random_pseudo_bytes(32));
        return sha1(md5($key."GodIsGoodAllTheTime".$incrypt)).'_'.$incrypt;
    }
    protected function log($msg,$type = 'E'){
        $year = date('Y');
        $month = date('F');
        $dayCount = date('z');

        $this->createFolder($this->config->application->logsDir.$year);
        $this->createFolder($this->config->application->logsDir.$year."/".$month);
        $logger = new FileAdapter($this->config->application->logsDir.$year.'/'.$month.'/'.$dayCount.'.log');
        if($type=='E'){
            $logger->log($msg, \Phalcon\Logger::ERROR);
        }
        else{
            $logger->log($msg, \Phalcon\Logger::DEBUG);
        }
        $logger->close();
    }
    /**
    * @param    string folder required folder Name
    */
    protected function createFolder($folder) {
        if(!file_exists($folder)){
            mkdir($folder, 0777);
        }
    }

    protected function formatSizeUnits($bytes){
        if ($bytes >= 1099511627776){ $bytes = number_format($bytes / 1099511627776, 2) . ' TB'; }
        else if ($bytes >= 1073741824){ $bytes = number_format($bytes / 1073741824, 2) . ' GB'; }
        else if ($bytes >= 1048576){ $bytes = number_format($bytes / 1048576, 2) . ' MB'; }
        else if ($bytes >= 1024){ $bytes = number_format($bytes / 1024, 2) . ' KB'; }
        else if ($bytes > 1){ $bytes = $bytes . ' B'; }
        else if ($bytes == 1){ $bytes = $bytes . ' B'; }
        else{ $bytes = '0 B'; }
        return $bytes;
    }
    protected function ftp_searchdir($conn_id, $dir){
            if( !$this->ftp_is_dir( $conn_id, $dir ) ) {
                die( 'No such directory on the ftp-server' );
            }
            if( strrchr( $dir, '/' ) != '/' ) {
                $dir = $dir.'/';
            }
            $dirlist[0] = $dir;
            $list = ftp_nlist( $conn_id, $dir );
            foreach( $list as $path ) {
                $path = './'.$path;
                if( $path != $dir.'.' && $path != $dir.'..') {
                    if( $this->ftp_is_dir( $conn_id, $path ) ) {
                        $temp = $this->ftp_searchdir( $conn_id, ($path), 1 );
                        $dirlist = array_merge( $dirlist, $temp );
                    }
                    else {
                        $dirlist[] = $path;
                    }
                }
            }
            ftp_chdir( $conn_id, '/../' );
            return $dirlist;
        }
    protected function ftp_is_dir( $conn_id,  $dir ){
        if( @ftp_chdir( $conn_id, $dir ) ) {
            ftp_chdir( $conn_id, '/../' );
            return true;
        }
        else {
            return false;
        }
    }
    protected function ftp_get_filelist($con, $path){
        $files = array();
        $contents = ftp_rawlist ($con, $path);
        return $contents;
        exit;
        $a = 0;

        if(count($contents)){
            foreach($contents as $line){

                preg_match("#([drwx\-]+)([\s]+)([0-9]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s ]+)([0-9]+)([\s]+)([0-9]+):([0-9]+)([\s]+)([a-zA-Z0-9\.\-\_ ]+)#si", $line, $out);

                if($out[3] != 1 && ($out[18] == "." || $out[18] == "..")){
                    // do nothing
                } else {
                    $a++;
                    $files[$a]['rights'] = $out[1];
                    $files[$a]['type'] = $out[3] == 1 ? "file":"folder";
                    $files[$a]['owner_id'] = $out[5];
                    $files[$a]['owner'] = $out[7];
                    $files[$a]['date_modified'] = $out[11]." ".$out[13] . " ".$out[13].":".$out[16]."";
                    $files[$a]['name'] = $out[18];
                }
            }
        }
        return $files;
    }

    protected function createZip(){
        $zip        =   new ZipArchive();
        $zipName    =   "$folder.zip";
        if($zip->open($zipName, ZIPARCHIVE::CREATE)===TRUE){
            foreach($dirFile as $file){
                $zip->addFile('some-file.pdf', 'subdir/filename.pdf');
                $zip->addFile('another-file.xlxs', 'filename.xlxs');
                $zip->addFile("localfile/$folder/$file");
            }
        }
    }
    protected function SplitStr($string){
        $delimeter = "|%^&|";
        return explode($delimeter, $string);
    }
    protected function hexToStr($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }

    protected function toTextFile($rawData, $delimiter, $objTitle, $module, $filter,$headerCSV,$exportCsv){
        header('Content-Type: text/plain; charset=utf-8');

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
        }
        fputcsv($fp, $headerCSV);
        foreach($rawData as $data){
            // $result = mb_convert_encoding(json_encode($data), 'UTF-16LE', 'UTF-8');
            // // print_r($result);
            $line = array_map("utf8_decode", $data);
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

    protected function toTextFileJampz($rawDataTxt, $delimiter, $objTitle, $fileExt){
        // print_r($_SERVER['DOCUMENT_ROOT']);
        $string = "";
        $path = $_SERVER['DOCUMENT_ROOT'] . "/files/";
        $folderName = date("YmdHis")."_".$objTitle;
        print_r($path);
        if(is_writable($path)){
            mkdir($path.$folderName);

            foreach($rawData as $data){
                $stringData = str_replace('"','',str_replace(',',$delimiter,str_replace('}','',str_replace('{','',str_replace('\\', '', str_replace('[','',str_replace(']','',json_encode($data))))))));
                foreach($objTitle as $title){
                    $stringData = str_replace("$title:",'',$stringData);
                }
                $string     = $string.$stringData."\n\r";
            }
            $fp = fopen($path.$folderName."/".$objTitle.".".$fileExt,"w");
            fwrite($fp,$string);
            fclose($fp);

            return  $path.$folderName."/".$objTitle.".".$fileExt;

        }
        else{
            return false;
        }

    }

    protected function wew(){
        $zip = new ZipArchive();
        $filename = "./test112.zip";

        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$filename>\n");
        }

        $zip->addFromString("testfilephp.txt" . time(), "#1 This is a test string added as testfilephp.txt.\n");
        $zip->addFromString("testfilephp2.txt" . time(), "#2 This is a test string added as testfilephp2.txt.\n");
        $zip->addFile($thisdir . "/too.php","/testfromfile.php");
        echo "numfiles: " . $zip->numFiles . "\n";
        echo "status:" . $zip->status . "\n";
        $zip->close();
    }

    protected function returnEmpty($data){
        if($data == null){
            return "";
        } else {
            $trimData = str_replace('  ', '',($data == '0' ? "" : $data));
            return $trimData;
        }
    }

    public function _getUserInfo($key){
        try{
            $info   = [];
            $name   = "";
            $getQry = TblUser::findFirst("id=$key");
            // }

            return $getQry->complete_name;
        }
        catch(Exception $e){

        }
    }

    public function _getCategoryById($key){
        try{
            $info   = [];
            $name   = "";
            $getQry = TblCategory::findFirst("id=$key");
            // }

            return $getQry->category_name;
        }
        catch(Exception $e){

        }
    }

    public function _getBrandById($key){
        try{
            $info   = [];
            $name   = "";
            $getQry = TblBrand::findFirst("id=$key");
            // }

            return $getQry->brand_name;
        }
        catch(Exception $e){

        }
    }

    public function _getUnitById($key){
        try{
            $info   = [];
            $name   = "";
            $getQry = TblUnit::findFirst("id=$key");
            // }

            return $getQry->unit_name;
        }
        catch(Exception $e){

        }
    }


    public function _deductInInventory($key,$qty){
        try{
            $info   = [];
            $name   = "";
            $getQry = TblInventory::findFirst("id=$key");
            $getQry->qty  = $getQry->qty  - $qty;
            $getQry->update();
            // }

            return true;
        }
        catch(Exception $e){

        }
    }
}
