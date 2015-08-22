<?php
// S3 Upload demo form
// Using AWS SDK for PHP V3

require(dirname(__FILE__).'/include/aws/aws-autoloader.php');

//use Aws\Common\Aws;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Check if anything has been posted
$posted = $_POST ? 1 : 0;

//Initialization of initializing initial variables, initially.
$fileUL = isset($_POST['fileUL']) ? $_POST['fileUL'] : '';
$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
$s3Bucket = isset($_POST['s3Bucket']) ? $_POST['s3Bucket'] : '';
//$s3Bucket='files.hartenergy.com';
$prefix=$fileName=$fileType='';

//Create AWS connection and s3client
//Must provide your own s3credentials.php file
$s3client=Aws::factory(dirname(__FILE__).'/s3credentials.php')->get('s3');


//Testing S3 read access            //DEBUG
$o_iter=$s3client->getIterator('ListObjects', array(
  'Bucket' => $s3Bucket
));
foreach ($o_iter as $o) {
  echo "{$o['Key']}   {$o['Size']}   {$o['LastModified']}<br/>";
}

// Check if the form has been posted
if($_POST['submit']=='Upload' && isset($s3Bucket) && is_uploaded_file($_FILES['fileUL']['tmp_name'])) {
  $finfo=finfo_open(FILEINFO_MIME_TYPE);
  $fileType=finfo_file($finfo, $_FILES['fileUL']['tmp_name']);
  $fileName=$_FILE['fileUL']['name'];
  if($fileType=="image/jpeg" || $fileType=="image/gif" || $fileType=="image/png") {
    //Put uploaded image in S3
    try {
      $s3client->PutObject(array(
      'Bucket'      =>  $s3Bucket,
      'Key'         =>  $prefix.'/'.$fileName,
      'SourceFile'  =>  '/tmp/'.$_FILES['fileUL']['tmp_name'],
      'ContentType' =>  $fileType
    ));
    } catch (Aws\Exception\S3Exception $e) {
        echo "<h1>There was an error uploading the file.</h1><br/>";
    }
    if($prefix=='QSA') {
      $objectURL=$s3client->getObjectUrl($s3Bucket,$prefix.'/'.$fileName,'30 minutes');
    } elseif($prefix=='public') {
      $objectURL="http://$s3Bucket/$prefix/$fileName";
    } else {
      $objectURL="http://$s3Bucket/$fileName";
    }
    echo "<img src='$objectURL'/><br/>";
  } else {
    echo "<h1>Unsupported File Type</h1>";
  }
} else {
  echo "<h1>No file or bucket selected</h1>";
}

//$testURL=$s3client->getObjectUrl($s3Bucket,'Parent/1135.jpg','30 minutes');

?>
<html>
  <form enctype='multipart/form-data' name='fileULForm' method='post' action='index.php' >
    <input type='hidden' name='s3Bucket' value='files.hartenergy.com' />
    <input type='hidden' name='prefix' value='public' />
    <input type='file' name='fileUL' id='fileUL' accept='image/gif|image/jpeg|image/png' />
    <input type='submit' value='Upload' />
  </form>
</html>
