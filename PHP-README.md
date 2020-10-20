# JDBI Client (PHP)
Front-end to take request to JDB Core

## Requirements:
- **PHP > 7.4**
- **PHP Memcache**
- **JDB Core Running**
- **Memcached Server Running**

## Introduction
For first initialize the **JDB Sock Connection** to **Memcached Server**
``` php
use laky64\database\JDBI;
include 'vendor/autoload.php';
$JDBI = new JDBI('ip_memcached_server', 'port_memcached_server'); //Start Connection
...
$JDBI -> close(); //Close connection
```
#### Show Processlist
Show processlist, return array on success and null on failure
``` php
...
$JDBI->query('SHOW PROCESSLIST;');
...
```
#### Make Database
For make database, return true on success and false on failure
``` php
...
$JDBI->query('CREATE DATABASE `database_name` PASSWORD `database_password`;');
...
```
#### Drop Database
For drop database, return true on success and false on failure
``` php
...
$JDBI->query('DROP DATABASE `database_name`;');
...
```
#### Connect to Database
For connect to database, return true on success and false on failure
``` php
...
$JDBI -> connect('database_name','database_password');
...
```
#### Make Table
For make table **(Need connection to database)**, return true on success and false on failure
##### With Auto-Increment primary key
``` php
...
$JDBI->query('CREATE TABLE `table_name` (`column1`, `column2`, `column3`) AS PRIMARY `column1` TYPE `AUTO_INCREMENT`;');
...
```
##### With Defined primary key
``` php
...
$JDBI->query('CREATE TABLE `table_name` (`column1`, `column2`, `column3`) AS PRIMARY `column1` TYPE `DEFINED`;');
...
```
#### Drop Table
For drop table **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('DROP TABLE `table_name`;');
...
```
#### Show all Table
Show all tables **(Need connection to database)**, return array on success and null on failure
``` php
...
$JDBI->query('SHOW TABLES;');
...
```
#### Dump Table
Dump Row **(Need connection to database)**, return array on success and null on failure
``` php
...
$JDBI->query('SELECT * FROM `table_name`;');
...
```
#### Add Column
Add Column **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('ALTER TABLE `table_name` ADD COLUMN (`column1`, `column2`);');
...
```
#### Drop Column
Add Column **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('ALTER TABLE `table_name` DROP COLUMN (`column1`, `column2`);');
...
```
#### Insert Row
Insert Row **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('INSERT INTO `table_name` (`column1`, `column2`, `column3`) VALUES (`value1`, `value2`, `value3`);');
...
```
#### Update Row
Insert Row **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('UPDATE `table_name` SET (`column1`, `column2`) VALUES (`value1`, `value2`) WHERE `column` IS `value`;');
...
```
#### Delete Row
Delete Row **(Need connection to database)**, return true on success and false on failure
``` php
...
$JDBI->query('DELETE FROM `table_name` WHERE `column` IS `value`;');
...
```
#### Dump Row
Dump Row **(Need connection to database)**, return array on success and null on failure
``` php
...
$JDBI->query('SELECT * FROM `table_name` WHERE `column` IS `value`;');
...
```