<?php
// S3 Upload demo form
// Using AWS SDK for PHP V3

require(dirname(__FILE__).'/include/aws/aws-autoloader.php');
require(dirname(__FILE__).'/s3credentials.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\CommandInterface;

// Initialize s3credentials
// $key and $secret provided by s3credentials.php
$credentials = new Aws\Credentials\Credentials($key,$secret);

//Initialization of initializing initial variables, initially.
$fileUL = isset($_POST['fileUL']) ? $_POST['fileUL'] : '';
$prefix = isset($_POST['prefix']) ? $_POST['prefix'].'/' : '';
$s3Bucket = isset($_POST['s3Bucket']) ? $_POST['s3Bucket'] : $defaultBucket;
$fileKey = isset($_POST['fileKey']) ? $_POST['fileKey'] : '';
$submit = isset($_POST['submit']) ? $_POST['submit'] : '';
$fileName=$fileType='';

//Create AWS connection and s3client
//Must provide your own s3credentials.php file
$s3client = new Aws\S3\S3Client([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'credentials' => $credentials
]);

echo "<html>\r\n";
echo " <h1>Bucket:: $s3Bucket</h1>\r\n";

// Check if DEL form submited
if($submit=='Delete' && isset($fileKey)) {
  $s3client->deleteObject(array(
        'Bucket' => $s3Bucket,
        'Key'    => $fileKey
      ));
}

// Display files currently uploaded in S3Bucket
$o_iter=$s3client->getIterator('ListObjects', array(
  'Bucket' => $s3Bucket
));
foreach ($o_iter as $o) {
  if($o['Size']!='0') {     // Because we don't want to list folders
    echo "<form enctype='multipart/form-data' name='fileDELForm' method='post' action='index.php' style='border:1px;' >";
    $prefixKey=explode("/",$o['Key']);
    if($prefixKey[0]=='public') {
      echo "<a href='http://".$s3Bucket."/".$o['Key']."'>";
      echo "<img src='http://$s3Bucket/".$o['Key']."' style='width:100px;height:100px;'/></a>";
      echo "<input type='hidden' name='fileKey' id='fileKey' value='".$o['Key']."' />";
      echo "{$o['Key']}";
      echo "<input type='submit' name='submit' id='submit' value='Delete' />";
    }
    if($prefixKey[0]=='QSA') {
      $objectPre = $s3client->getCommand('GetObject', [
        'Bucket' => $s3Bucket,
        'Key'    => $o['Key']
      ]);
      $presignedRequest=$s3client->createPresignedRequest($objectPre,'+30 minutes');
      $objectURL = (string) $presignedRequest->getUri();
      echo "<a href='$objectURL' >";
      echo "<img src='$objectURL' style='width:100px;height:100px;' /></a>";
      echo "<input type='hidden' name='fileKey' id='fileKey' value='".$o['Key']."' />";
      echo "{$o['Key']}";
      echo "<input type='submit' name='submit' id='submit' value='Delete' />";
    }
    echo "</form>";
  }
}

// Check if the UL form has been posted
if($submit=='Upload') {
  if(isset($s3Bucket) && is_uploaded_file($_FILES['fileUL']['tmp_name'])) {
    $finfo=finfo_open(FILEINFO_MIME_TYPE);
    $fileType=finfo_file($finfo, $_FILES['fileUL']['tmp_name']);
    $fileName=$_FILES['fileUL']['name'];
    if($fileType=="image/jpeg" || $fileType=="image/gif" || $fileType=="image/png") {
      //Put uploaded image in S3
      try {
        $s3client->PutObject(array(
        'Bucket'      =>  $s3Bucket,
        'Key'         =>  $prefix.$fileName,
        'SourceFile'  =>  $_FILES['fileUL']['tmp_name'],
        'ContentType' =>  $fileType
      ));
      } catch (Aws\Exception\S3Exception $e) {
          echo "<h1>There was an error uploading the file.</h1><br/>";
      }
      if($prefix=='QSA/') {       // Generate QSA signed URL for protected files
        $objectPre = $s3client->getCommand('GetObject', [
          'Bucket' => $s3Bucket,
          'Key'    => $prefix.$fileName
        ]);
        $presignedRequest=$s3client->createPresignedRequest($objectPre,'+30 minutes');
        $objectURL = (string) $presignedRequest->getUri();
      } elseif($prefix=='public/') {    // Generate regular unsigned URL
        $objectURL="http://$s3Bucket/".$prefix.$fileName;
      } else {      // I dunno, because, maybe?
        $objectURL="http://$s3Bucket/$fileName";
      }
      echo "<img src='$objectURL'/><br/>";
    } else {
      echo "<h1>Unsupported File Type</h1>";
    }
  } else {
    echo "<h1>No file or bucket selected</h1>";
  }
}

?>
  <form enctype='multipart/form-data' name='fileULForm' method='post' action='index.php' >
    <input type='text' name='s3Bucket' id='s3Bucket' value='<?php echo $s3Bucket; ?>' />
    <select name='prefix' id='prefix'>
      <option value='public'>public</option>
      <option value='QSA'>QSA</option>
    </select>
    <input type='file' name='fileUL' id='fileUL' accept='image/gif|image/jpeg|image/png' />
    <input type='submit' name='submit' id='submit' value='Upload' />
  </form>
</html>
