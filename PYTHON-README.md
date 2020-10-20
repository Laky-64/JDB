# JDBI Client (Python)
Front-end to take request to JDB Core

## Requirements:
- **PHP > 7.4**
- **PHP Memcache**
- **Python > 3.9**
- **JDB Core Running**
- **Memcached Server Running**

## Introduction
For first initialize the **JDB Sock Connection** to **Memcached Server**
``` python
from vendor.laky64.jdb.src.JDBI import JDBI
JDBI_CLASS = JDBI('ip_memcached_server', 'port_memcached_server')
```
#### Show Processlist
Show processlist, return array on success and None on failure
``` python
...
JDBI_CLASS.query('SHOW PROCESSLIST;')
...
```
#### Make Database
For make database, return true on success and false on failure
``` python
...
JDBI_CLASS.query('CREATE DATABASE `database_name` PASSWORD `database_password`;')
...
```
#### Drop Database
For drop database, return true on success and false on failure
``` python
...
JDBI_CLASS.query('DROP DATABASE `database_name`;')
...
```
#### Connect to Database
For connect to database, return true on success and false on failure
``` python
...
JDBI_CLASS.connect('database_name','database_password')
...
```
#### Make Table
For make table **(Need connection to database)**, return true on success and false on failure
##### With Auto-Increment primary key
``` python
...
JDBI_CLASS.query('CREATE TABLE `table_name` (`column1`, `column2`, `column3`) AS PRIMARY `column1` TYPE `AUTO_INCREMENT`;')
...
```
##### With Defined primary key
``` python
...
JDBI_CLASS.query('CREATE TABLE `table_name` (`column1`, `column2`, `column3`) AS PRIMARY `column1` TYPE `DEFINED`;')
...
```
#### Drop Table
For drop table **(Need connection to database)**, return true on success and false on failure
``` python
...
JDBI_CLASS.query('DROP TABLE `table_name`;')
...
```
#### Show all Table
Show all tables **(Need connection to database)**, return array on success and None on failure
``` python
...
JDBI_CLASS.query('SHOW TABLES;')
...
```
#### Dump Table
Dump Row **(Need connection to database)**, return array on success and None on failure
``` python
...
JDBI_CLASS.query('SELECT * FROM `table_name`;')
...
```
#### Add Column
Add Column **(Need connection to database)**, return true on success and false on failure
``` python
...
JDBI_CLASS.query('ALTER TABLE `table_name` ADD COLUMN (`column1`, `column2`);')
...
```
#### Drop Column
Add Column **(Need connection to database)**, return true on success and false on failure
``` python
...
JDBI_CLASS.query('ALTER TABLE `table_name` DROP COLUMN (`column1`, `column2`);')
...
```
#### Insert Row
Insert Row **(Need connection to database)**, return true on success and false on failure
``` python
...
JDBI_CLASS.query('INSERT INTO `table_name` (`column1`, `column2`, `column3`) VALUES (`value1`, `value2`, `value3`);')
...
```
#### Update Row
Insert Row **(Need connection to database)**, return true on success and false on failure
``` python
...
$JDBI->query('UPDATE `table_name` SET (`column1`, `column2`) VALUES (`value1`, `value2`) WHERE `column` IS `value`;');
...
```
#### Delete Row
Delete Row **(Need connection to database)**, return true on success and false on failure
``` python
...
JDBI_CLASS.query('DELETE FROM `table_name` WHERE `column` IS `value`;')
...
```
#### Dump Row
Dump Row **(Need connection to database)**, return array on success and None on failure
``` python
...
JDBI_CLASS.query('SELECT * FROM `table_name` WHERE `column` IS `value`;')
...
```