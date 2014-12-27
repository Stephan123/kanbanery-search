<?php
/**
 * Empfangen von Informationen von Kanbanery Board
 *
    CREATE TABLE `kanbanery` (
    `id` int(10) DEFAULT NULL COMMENT 'Ticket ID',
    `ort` varchar(10) DEFAULT NULL COMMENT 'Ort des Task',
    `task_type_id` int(10) NOT NULL,
    `created_at` datetime DEFAULT NULL COMMENT 'angelegt am',
    `updated_at` datetime DEFAULT NULL COMMENT 'verändert am',
    `moved_at` datetime DEFAULT NULL COMMENT 'Zuordnung zu einer neuen Spalte',
    `title` text COMMENT 'Feature des Baustein',
    `description` text COMMENT 'Beschreibung der Aufgabe',
    `global_in_context_url` text,
    `position` int(10) DEFAULT NULL COMMENT 'In welcher Spalte',
    `weight` tinyint(3) DEFAULT NULL,
    `ready_to_pull` tinyint(1) DEFAULT NULL COMMENT 'fertig zum abholen',
    `creator_id` int(10) DEFAULT NULL COMMENT 'Wer hat Task angelegt ?',
    `owner_id` int(10) DEFAULT NULL COMMENT 'Wer bearbeitet den Task momentan ?',
    `priority` tinyint(3) DEFAULT NULL COMMENT 'Priorität'
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8
 *
 *
 *
 *
 * @author Stephan Krauss , info@stephankrauss.de
 * @since 27.12.2014 19:56
 */

include_once('config.php');
include_once('sparrow.php');

/** @var $sparrow Sparrow */
$sparrow = new Sparrow();
$sparrow->setDb($connectDb);

$url = array(
    "https://voicecity.kanbanery.com/api/v1/projects/".$projektId."/archive/tasks.json?api_token=".$api_token,
    "https://voicecity.kanbanery.com/api/v1/projects/".$projektId."/tasks.json?api_token=".$api_token
);

function abfragenTasks($url)
{
    $curlHandler = curl_init();

    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curlHandler,CURLOPT_URL, $url);
    curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curlHandler, CURLOPT_HTTPGET, true);
    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($curlHandler, CURLPROTO_HTTPS, true);

    //execute
    $response = curl_exec($curlHandler);
    // close connection
    curl_close($curlHandler);

    $inhalt = json_decode($response);

    return $inhalt;
}

function datumZerlegen($datum){

    $datumTeile = explode('T', $datum);

    $zeitTeile = explode('+',$datumTeile[1]);

    $datumZeit = $datumTeile[0]." ".$zeitTeile[0];

    return $datumZeit;
}

// Tabelle leeren
$sql = "TRUNCATE TABLE kanbanery";
$sparrow->sql($sql)->execute();

foreach($url as $key => $aufrufUrl)
{
    $inhalt = abfragenTasks($aufrufUrl);

    for($i=0; $i < count($inhalt); $i++){

        if($i % 10 == 0)
            echo 'Task '.$i."<br>";

        $insert = (array) $inhalt[$i];

        unset($insert['sync_updated_at']);
        unset($insert['sync_created_at']);
        unset($insert['estimate_id']);
        unset($insert['column_id']);
        unset($insert['priority']);
        unset($insert['deadline']);
        unset($insert['blocked']);
        unset($insert['type']);

        $insert['created_at'] = datumZerlegen($insert['created_at']);
        $insert['updated_at'] = datumZerlegen($insert['updated_at']);
        $insert['moved_at'] = datumZerlegen($insert['moved_at']);

        $insert['title'] = $sparrow->quote($insert['title']);
        $insert['description'] = $sparrow->quote($insert['description']);

        if($key == 0)
            $insert['ort'] = 'archive';
        else
            $insert['ort'] = 'board';

        if($insert['ready_to_pull'] == false)
            $insert['ready_to_pull'] = 0;
        else
            $insert['ready_to_pull'] = 1;

        $sql = $sparrow
            ->from('kanbanery')
            ->insert($insert)
            ->execute();
    }
}