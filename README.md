# JDB(v. 3.3-stable)
JDB is very fast parallel database based on json text plain with encryptation

`JDB 2.3-stable it's still available but deprecated`

_Library maked by [Laky64](https://t.me/Laky64)_

## JDB Bechmark
| | PHP | Python | Mode | Thread Mode |
| --- | --- | --- | --- | --- |
| **One Thread** | 15ms | 37ms | Sequential | Single Thread |
| **Two Thread** | 47ms | 85ms | Async | Multi Thread |
| **Four Thread** | 79ms | 149ms | Async | Multi Thread |
| **Eight Thread** | 143ms | 133ms | Async | Multi Thread |
| **Twelve Thread** | 207ms | 402ms | Async | Multi Thread |

## JDB Structure
Below is how the JDB database is structured

##### JDB Core Structure
| DATABASE | PASSWORD |
| --- | --- |
| database1 | password1 |
| database2 | password2 |
| database3 | password3 |

##### Database Structure
| TABLE | PRIMARY KEY | PRIMARY KEY TYPE |
| --- | --- | --- |
| table1 | primary_key1 | primary_key_type1 |
| table2 | primary_key2 | primary_key_type2 |
| table3 | primary_key3 | primary_key_type3 |

##### Table Structure
|  | COLUMN1 | COLUMN2 | COLUMN3 |
| --- | --- | --- | --- |
| **ROW1** | value1 | value2 | value3 |
| **ROW2** | value1 | value2 | value3 |
| **ROW3** | value1 | value2 | value3 |

## JDB Core Threads
In the JDB core there are different types of threads each with different jobs, here it is:

- **Controller Unit Thread**
It divides the workload on the various Operation Threads according to the freest one
- **I/O Thread**
It performs backups to disk every 500ms from RAM Database
- **Operation Thread**
It performs computation or query operations, usually more than one of these threads are started

## Getting Started
### Now Available in Async Multi-Thread, supported Windows Environment and Python Client!

First create the database folder, then create in the database folder with the name of the table to which you want to attribute, in which you want to insert the fields

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

## Warning!
Read this **documentation** carefully so as not to cause malfunctions with **JDB**

## JDB CORE

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
new JDB_CORE(JDB_CORE::NUM_THREAD, 'database_folder', 'ip_memcached_server', 'port_memcached_server');
```

## Available Clients
- [**PHP Client**](https://github.com/Laky-64/JDB/blob/master/PHP-README.md)
- [**Python Client**](https://github.com/Laky-64/JDB/blob/master/PYTHON-README.md)

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)