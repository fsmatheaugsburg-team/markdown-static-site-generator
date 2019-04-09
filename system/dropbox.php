<?php

class Dropbox {
  private $clientId;
  private $clientSecret;
  private $accessToken;

  private $bearerToken;

  // https://www.dropbox.com/developers/documentation/http/documentation
  private function authorize() {
    $ch = curl_init();
    curl_setopt_array(
      $ch,
      array(
        CURLOPT_URL => "https://api.dropbox.com/oauth2/token",
        CURLOPT_USERPWD => $this->clientId.":".$this->clientSecret,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query(array("code" => $this->accessToken, "grant_type" => "authorization_code")),
        CURLOPT_RETURNTRANSFER => true 
      )
    );
    $result = curl_exec($ch);
    // TODO: Interpret Value
    $this->bearerToken = "";

    print_r($result);
    curl_close($ch);
  }

  public function __construct($clientId, $clientSecret, $accessToken) {
    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
    $this->accessToken = $accessToken

    $this->authorize();
  }

  // https://www.dropbox.com/developers/documentation/http/documentation#files-download
  public function download($sourceFile, $targetFile) {
    $fp = fopen($targetFile, "w+");
    $ch = curl_init();

    $body = array(
      path => $sourceFile
    );

    curl_setopt_array(
      $ch,
      array(
        CURLOPT_URL => "https://content.dropboxapi.com/2/files/download",
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => array(
          "Authorization" => $bearerToken,
          "Dropbox-API-Arg" => json_encode($body);
        ),
        CURLOPT_TIMEOUT => 50,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true
      )
    );

    curl_exec($ch); 
    curl_close($ch);
    fclose($fp);
  }

  // https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
  public function listFolder($folder) {
    $ch = curl_init();

    $body = array(
      path: $folder,
      recursive: false,
      include_media_info: false,
      include_deleted: false,
      include_has_explicit_shared_members => false,
      include_mounted_folders => true
    );
    curl_setopt_array(
      $ch,
      array(
        CURLOPT_URL => "https://api.dropboxapi.com/2/files/list_folder",
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($data_string),
        CURLOPT_HTTPHEADER => array(
          "Authorization" => $bearerToken,
          "Content-Type" => "application/json";
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true
      )
    );

    $result = curl_exec($ch);
    // TODO: Handle data
    // TODO: Handle pagination
    $fileList = array()

    print_r($result);
    curl_close($ch);

    return $fileList;
  }
}

function ensureDir($targetFolder) {
  if(!file_exists($targetFolder)) {
    mkdir($targetFolder);
    return;
  }
  if(!is_dir($targetFolder)) {
    throw new Exception($targetFolder." is not a folder!");
  }
}

function syncDropboxFile($dropbox, $sourceFile, $targetRootFolder, $targetFile, $item, $oldRevisions, &$newRevisions) {
  $currentRevision = $item->getId()."|".$item->getRev();

  if(
    !file_exists($targetRootFolder.$targetFile)
    || !array_key_exists($targetFile, $oldRevisions)
    || $oldRevisions[$targetFile] != $currentRevision
  ) {
    $dropbox->download($sourceFile, $targetRootFolder.$targetFile);
  }
  $newRevisions[$targetFile] = $currentRevision;   
}

function syncDropboxFolder($dropbox, $sourceFolder, $targetRootFolder, $targetFolder, $oldRevisions, &$newRevisions) {
  ensureDir($targetRootFolder.$targetFolder);

  $items = $dropbox->listFolder($sourceFolder);
                   
  $items->each(
    function ($item, $key) use ($dropbox, $sourceFolder, $targetRootFolder, $targetFolder, $oldRevisions, &$newRevisions) {
      if ($item instanceof FolderMetadata) {
        syncDropboxFolder(
          $dropbox,
          $sourceFolder.$item->getName()."/",
          $targetRootFolder,
          $targetFolder.$item->getName()."/",
          $oldRevisions,
          $newRevisions
        );
      } elseif ($item instanceof FileMetadata) {
        syncDropboxFile(
          $dropbox,
          $sourceFolder.$item->getName(),
          $targetRootFolder,
          $targetFolder.$item->getName(),
          $item,
          $oldRevisions,
          $newRevisions
        );
      }
    }
  );
}

function deleteRemovedFiles($targetRootFolder, $oldRevisions, $newRevisions) {
  $toBeDeleted = array_diff(array_keys($oldRevisions), array_keys($newRevisions));

  array_map(
    function ($filepath) use ($targetRootFolder) {
        unlink($targetRootFolder.$filepath);
    },
    $toBeDeleted
  );
}

function syncFromDropbox($clientId, $clientSecret, $accessToken, $sourceFolder, $targetRootFolder) {
  $app = new DropboxApp($clientId, $clientSecret, $accessToken);
  $dropbox = new Dropbox($app);

  ensureDir($targetRootFolder);
  
  $revisionsFile = $targetRootFolder.".rev.json";
  if(file_exists($revisionsFile)) {
    $oldRevisions = json_decode(file_get_contents($revisionsFile), true);
  } else {
    $oldRevisions = array();
  }
  $newRevisions = array();

  syncDropboxFolder($dropbox, $sourceFolder, $targetRootFolder, "", $oldRevisions, $newRevisions);
  deleteRemovedFiles($targetRootFolder, $oldRevisions, $newRevisions);

  file_put_contents($revisionsFile, json_encode($newRevisions));
}
?>
