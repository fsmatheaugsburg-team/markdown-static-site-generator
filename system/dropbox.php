<?php
  require_once(__DIR__.'/vendor/autoload.php');

  use Kunnu\Dropbox\Dropbox;
  use Kunnu\Dropbox\DropboxApp;
  use Kunnu\Dropbox\Models\FileMetadata;
  use Kunnu\Dropbox\Models\FolderMetadata;

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

    $items = $dropbox
      ->listFolder($sourceFolder)
      ->getItems();
                     
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
