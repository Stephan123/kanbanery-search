<?php
include_once('config.php');
include_once('sparrow.php');

/** @var $sparrow Sparrow */
$sparrow = new Sparrow();
$sparrow->setDb($connectDb);
$sparrow->from('kanbanery');
$sparrow->sortDesc('created_at');
$sparrow->select(array('title','created_at','id','ort'));
// $query = $sparrow->sql();

$result = $sparrow->many();

// erstellen der Task URL

for($i=0; $i < count($result); $i++){
    if($result[$i]['ort'] == 'board')
        $result[$i]['taskUrl'] = "https://".$kanbaneryAccount."/projects/".$projektId."/board/tasks/".$result[$i]['id'];
    else
        $result[$i]['taskUrl'] = "https://".$kanbaneryAccount."/projects/".$projektId."/archive/tasks/".$result[$i]['id'];
}

echo json_encode($result);