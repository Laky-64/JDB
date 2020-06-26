# JDB(v. 2.0-stable)
A simple database based on json text plain

### Now Available in Async!

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

➖ `composer require laky64/jdb`

## Documentation
Read this **documentation** carefully so as not to cause malfunctions with **JDB**

### Getting Started
To start you must specify the **folder** in which to save the files, for example `/var/www/html`, specifying the **password** for the database

``` php
require_once 'vendor/autoload.php';
use laky64\database\JDB;
$DB_CLASS = new JDB(PASSWORD(string), FOLDER(string), TIMEOUT(int), false);
```

or async (Required AMP-PHP)

``` php
require_once 'vendor/autoload.php';
use laky64\database\JDB;
use Amp\Loop;
@Loop::run(function (){
    ...
    Your code Here
    ...
    $DB_CLASS = new JDB(PASSWORD(string), FOLDER(string), TIMEOUT(int), true);
});
```


### Making Table
Make Table return false if already exist, you need to add default value in **Array** format
``` php
...
$DB_CLASS->make_row(TABLE_NAME(string), ROW_NAME(string), VALUES(array));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->make_row_async(TABLE_NAME(string), ROW_NAME(string), VALUES(array), function ($result){
        ...
    });
});
```

### Make Alternative Index
Make **alternative** index row name's, you need set {TABLE NAME, NEW NAME, OLD NAME}
``` php
...
$DB_CLASS->make_alternative_index(TABLE_NAME(string), NEW_ROW_NAME(string), ROW_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->make_alternative_index_async(TABLE_NAME(string), NEW_ROW_NAME(string), ROW_NAME(string), function ($result){
        ...
    });
});
```

### Reading Row
You can read from **alternative** index if setted or from **default** index, this return Array
``` php
...
$DB_CLASS->get_values(TABLE_NAME(string), ROW_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->get_values_async(TABLE_NAME(string), ROW_NAME(string), function ($result){
        ...
    });
});
```

### Writing Value
You can write value from **alternative** index if setted or from **default** index, this return Boolean
``` php
...
$DB_CLASS->set_value(TABLE_NAME(string), ROW_NAME(string), COLUMN_NAME(string), VALUE(string or array));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->set_value_async(TABLE_NAME(string), ROW_NAME(string), COLUMN_NAME(string), VALUE(string or array), function ($result){
        ...
    });
});
```

### Drop Row
For **delete row**, return true if is deleted
``` php
...
$DB_CLASS->drop_row(TABLE_NAME(string), ROW_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->drop_row_async(TABLE_NAME(string), ROW_NAME(string), function ($result){
        ...
    });
});
```

### Drop Alternative Index
For **delete index**, return true if is deleted
``` php
...
$DB_CLASS->drop_index(TABLE_NAME(string), ALTERNATIVE_INDEX_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->drop_index_async(TABLE_NAME(string), ALTERNATIVE_INDEX_NAME(string), function ($result){
        ...
    });
});
```

### Getting Auto-increment index
This is for **getting auto-increment**(Based on row numbers), this return int
``` php
...
$DB_CLASS->get_auto_increment(TABLE_NAME);
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->get_auto_increment_async(TABLE_NAME(string), function ($result){
        ...
    });
});
```

### Getting all row name from table
This function return **all row name** as **array**
``` php
...
$DB_CLASS->get_rows_name(TABLE_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->get_rows_name_async(TABLE_NAME(string), function ($result){
        ...
    });
});
```

### Getting all alternative row name from table
This function return **all row name** as **array**
``` php
...
$DB_CLASS->get_rows_alternative_name(TABLE_NAME(string));
```

or async (Required AMP-PHP)

``` php
...
@Loop::run(function (){
    ...
    $DB_CLASS->get_rows_alternative_name_async(TABLE_NAME(string), function ($result){
        ...
    });
});
```
