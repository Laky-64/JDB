<?php

use laky64\database\JDBI;

include 'JDBI.php';
include 'CustomMemcache.php';
if(isset($argc)){
    $DATA = json_decode(urldecode($argv[1]),true);
    if($DATA['ACTION'] == 'EXECUTE_QUERY') {
        $SOCK_DATA = $DATA['SOCK_CONNECTION'];
        try {
            $JDBI = new JDBI($SOCK_DATA['IP'], $SOCK_DATA['PORT'], $SOCK_DATA['TIMEOUT']);
            if(isset($DATA['DATA']['DB_NAME']) && isset($DATA['DATA']['DB_PASSWORD'])){
                $JDBI->connect($DATA['DATA']['DB_NAME'], $DATA['DATA']['DB_PASSWORD']);
            }
            echo json_encode([
                'TYPE_RESULT' => 'MESSAGE',
                'RESULT' => $JDBI->query($DATA['DATA']['QUERY'])
            ], true);
            $JDBI->close();
        } catch (Exception $e) {
            echo json_encode([
                'TYPE_RESULT' => 'ERROR',
                'RESULT' => $e->getMessage()
            ], true);
        }
    }else if($DATA['ACTION'] == 'CONNECT'){
        $SOCK_DATA = $DATA['SOCK_CONNECTION'];
        try {
            $JDBI = new JDBI($SOCK_DATA['IP'], $SOCK_DATA['PORT'], $SOCK_DATA['TIMEOUT']);
            echo json_encode([
                'TYPE_RESULT' => 'MESSAGE',
                'RESULT' => $JDBI->connect($DATA['DATA']['DB_NAME'], $DATA['DATA']['DB_PASSWORD'])
            ], true);
            $JDBI->close();
        } catch (Exception $e) {
            echo json_encode([
                'TYPE_RESULT' => 'ERROR',
                'RESULT' => $e->getMessage()
            ], true);
        }
    }else{
        echo json_encode([
            'TYPE_RESULT' => 'ERROR',
            'RESULT' => 'Not recognized action'
        ],true);
    }
}
