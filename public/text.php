<?php
/*header("Content-Description: File Transfer"); 
header("Content-Type: application/octet-stream"); 
header("Content-Disposition: attachment; filename=".basename('localfile/localfile.tar')); 
readfile ('localfile/localfile.tar'); 
unlink('localfile/localfile.tar');*/
echo $password = hash('sha256',$_GET['password']);
