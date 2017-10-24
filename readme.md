PHPFaxTo is a PHP class for easy use of the fax.to Fax API.

How to use
-

#### Set up
```
$fax = new Fax('<your api code here>');
```


#### Get account balance
```
echo $fax->getCashBalance(); // e.g. 3.7
```

#### Get fax cost
```
echo $fax->getFaxCost(<fax number>, <id of the document you want to send>); // e.g. 0.1
```

#### Get fax status
```
$status = $fax->getFaxStatus(<job id>); // associative array
```

#### Get fax history
```
$history = $fax->getFaxHistory(); // associative array
```

#### Send fax
Either document_id OR file must be set, not both.
```
$sent = sendFax($fax_number, $document_id = null, $tsi_number = null, $file = null, $delete_file = null); // associative array
```

#### Get files
```
$files = $fax->getFiles(); // associative array
```

#### Upload file
set is_remote to true if you need to supply a remote file path, e.g. http://domain.com/file.ext
```
$file = $fax->uploadFile(<file path>, $is_remote = false); // associative array
```

#### Delete file
```
$deleted = $fax->deleteFile(<file id>); // associative array
```

When sending a fax (and if it didn't fail immediately), status = executed and a fax_job_id will be returned. The 
fax_job_id will also be supplied in the callback in order to identify a fax sending process.