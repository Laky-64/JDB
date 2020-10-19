# JDB(v. 3.0-beta)
A very fast database based on json text plain with encryptation

`JDB 2.3-stable it's still available but deprecated`

### Now Available in Async Multi-Thread and Windows Environment!

First create the database folder, then create in the database folder with the name of the table to which you want to attribute, in which you want to insert the fields

_Library maked by [Laky64](https://t.me/Laky64)_

## Installation with composer
With **json file** (`Composer`):
``` json
{
    ...
    "require": {
        "laky64/jdb": "*"
    }
}
```
With **command line** (`Composer`):

**-** `composer require laky64/jdb`

## Documentation
Read this **documentation** carefully so as not to cause malfunctions with **JDB**

### JDB CORE

#### Requirements:
- **PHP > 7.4**
- **PHP OpenSSL**
- **PHP Memcache**
- **Memcached Server**
- **AmpPHP**


#### Type of Threads Available
- **Two Thread**
1 I/O Thread, 1 Controller Unit and 2 Operation Thread (Very fast but not optimized for multiple connections)
- **Four Thread**
1 I/O Thread, 1 Controller Unit and 4 Operation Thread (Fast and optimized for mid level of multiple connections)
- **Eight Thread**
1 I/O Thread, 1 Controller Unit and 8 Operation Thread (Slow but optimized for high level of multiple connections)
- **Twelve Thread**
1 I/O Thread, 1 Controller Unit and 12 Operation Thread (Very slow but optimized for very high level of multiple connections)

#### Make runner for local JDB Server
First you need to run a **Memcached Server** without **stopping**, then create a php file to run the **JDB Core** without **stopping** _(Suggested one for server)_
``` php
header('Content-Type: text/plain'); //For make readable JDBC Core results
ini_set('memory_limit', '2048M'); //For avoid a memory overload
use laky64\database\JDB_CORE;
include 'vendor/autoload.php';
new JDB_CORE(THREAD_INSTANCE(JDB_CORE::NUM_THREAD), DATABASE_FOLDER(String), IP_MEMCACHED_SERVER(String), PORT_MEMCACHED_SERVER(String));
```

### JDBI Client
Front-end to take request to JDB Core

#### Requirements:
- **PHP > 7.4**
- **PHP Memcache**
- **JDB Core Running**
- **Memcached Server Running**

#### Introduction
For first initialize the **JDB Sock Connection** to **Memcached Server**
``` php
use laky64\database\JDBI;
include 'vendor/autoload.php';
$JDBI = new JDBI(IP_MEMCACHED_SERVER(String), PORT_MEMCACHED_SERVER(String)); //Start Connection
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