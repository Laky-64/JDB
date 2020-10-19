<?php

use laky64\database\JDBI;
include 'src/JDBI.php';

$JDBI = new JDBI('127.0.0.1','9000');
try {
    //$start_time = time();
    //var_dump($JDBI->query('CREATE DATABASE `test00001` PASSWORD `nPQ555iaTCfJMHK8S7yPHrUcC0Xk2We7`;'));
    //var_dump($JDBI->query('DROP DATABASE `test00001`;'));
    $JDBI -> connect('test00001','nPQ555iaTCfJMHK8S7yPHrUcC0Xk2We7');
    //var_dump($JDBI->query('CREATE TABLE `Dati4` (`Nome`,`Cognome`,`Età`) AS PRIMARY `Nome` TYPE `DEFINED`;'));
    //var_dump($JDBI->query('DROP TABLE `Dati4`;'));
    //print_r($JDBI->query('SHOW TABLES;'));
    //var_dump($JDBI->query('INSERT INTO `Dati` (`Nome`, `Cognome`, `Età`) VALUES (`Mario3`, `Rossi`, `25`);'));
    //var_dump($JDBI->query('UPDATE `Dati` SET (`Cognome`) VALUES (`Rossi`) WHERE `Cognome` IS `Verdi`;'));
    //var_dump($JDBI->query('DELETE FROM `Dati` WHERE `Cognome` IS `Rossi`;'));
    //print_r($JDBI->query('SELECT * FROM `Dati` WHERE `Cognome` IS `Rossi`;'));
    //print_r($JDBI->query('SELECT * FROM `Dati`;'));
    //print_r($JDBI->query('SHOW PROCESSLIST;'));
    //var_dump($JDBI->query('ALTER TABLE `Dati` ADD COLUMN (`Genere`, `Email`);'));
    //var_dump($JDBI->query('ALTER TABLE `Dati` DROP COLUMN (`Genere`, `Email`, `Email_Secondary`);'));

    //echo (time() - $start_time) . 's' .PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}


//
//
$JDBI -> close();