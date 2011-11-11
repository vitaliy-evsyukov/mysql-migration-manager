<?php

class initController extends AbstractController
{

  public function runStrategy()
  {
    $fname = Helper::get('savedir').'/schema.php';
    if(!file_exists($fname))
    {
      echo "File: {$fname} does not exist!\n";
      exit;
    }
    $this->askForRewriteInformation();
    require_once $fname;
    $sc = new Schema();
    $sc->load(Helper::getDbObject());
    
  }

  public function askForRewriteInformation()
  {
    $c='';
    do{
      if($c!="\n") echo "Can I rewrite tables in database (all data will be lost) [y/n]? ";
      $c = fread(STDIN, 1);

      if($c ==='Y' or $c==='y' ){
        return;
      }
      if($c ==='N' or $c==='n' ){
        echo "\nExit without changing shema\n";
        exit;
      }

    }while(true);
  }
}
