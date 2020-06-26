<?php

namespace laky64\database;

use Amp\Deferred;
use Amp\Loop;
use Exception;
use Throwable;

class JDB {
    protected string $upload;
    protected string $password;
    protected int $timeout;
    protected bool $init_async;

    /**
     * Database constructor.
     * Async Function Available
     * @param string|null $password Make password for your database
     * @param string|null $database_location For example /var/www/html
     * @param int|null $timeout_seconds Time in seconds
     * @param bool $async To init in async or not
     * @throws Exception
     */
    function __construct($password = null, $database_location = null, $timeout_seconds = 1, $async = false){
        $this -> init_async = $async;
        if($async){
            Loop::delay(0, function () use ($password, $database_location, $timeout_seconds) {
                $this -> init_db($password, $database_location, $timeout_seconds);
            });
        }else{
            $this -> init_db($password, $database_location, $timeout_seconds);
        }
    }

    /**
     * @param string $password
     * @param string $database_location
     * @param int $timeout_seconds
     * @throws Exception
     */
    protected function init_db($password, $database_location, $timeout_seconds){
        if($password != null){
            if($database_location != null){
                $this -> upload = $database_location . '/database/';
                $this -> password = $password;
                $this -> timeout = $timeout_seconds * 5;
                if(!file_exists($this->upload)){
                    mkdir($this->upload);
                }
            }else{
                throw new Exception('Need folder to specifty where save Database with www-data permission');
            }
        }else{
            throw new Exception( 'Need login with password');
        }
    }


    //TODO Make Row
    /**
     * Make row.
     * Async Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param array $values Values to set
     * @param callable $callback Async return result of "make_row"
     * @throws Exception
     */
    public function make_row_async($table, $index, $values, $callback){
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index, $values) {
            if(!file_exists($this->upload.$table)){
                mkdir($this->upload.$table);
            }
            $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if(strlen($value_list) == 0 & strlen($index) > 0 & strlen($table) > 0){
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($values, JSON_PRETTY_PRINT), $this -> timeout);
                $defer -> resolve(['result' => $this -> str_to_bool(true), 'message' => 'Maked Correctly Table and Row in Async Mode!']);
            }else{
                $defer -> resolve(['result' => $this -> str_to_bool(false), 'message' => 'Already Exist this Table and Row']);
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw new Exception('Error on executing AMPPHP Async');
            }
        });
    }

    /**
     * Make row.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param array $values Values to set
     * @return array Result of "make_row"
     * @throws Exception
     */
    public function make_row($table, $index, $values){
        if(!$this -> init_async){
            if(!file_exists($this->upload.$table)){
                mkdir($this->upload.$table);
            }
            $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if(strlen($value_list) == 0 & strlen($index) > 0 & strlen($table) > 0){
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($values, JSON_PRETTY_PRINT), $this -> timeout);
                return ['result' => $this -> str_to_bool(true), 'message' => 'Maked Correctly Table and Row'];
            }else{
                return ['result' => $this -> str_to_bool(false), 'message' => 'Already Exist this Table and Row'];
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Drop Row
    /**
     * Drop Row.
     * Async Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param callable $callback Async return result of "make_row"
     * @throws Exception
     */
    public function drop_row_async($table, $index, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index) {
            $result = @unlink($this->upload.$table.'/'.$index.'.jdb');
            $result = $result == null ? false:$result;
            $message = $result ? 'Dropped correctly row':'Not found this row';
            $defer -> resolve(['result' => $this -> str_to_bool($result), 'message' => $message]);
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Drop Row.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "drop_row"
     * @throws Exception
     */
    public function drop_row($table, $index) {
        if(!$this -> init_async){
            $result = @unlink($this->upload.$table.'/'.$index.'.jdb');
            $result = $result == null ? false:$result;
            $message = $result ? 'Dropped correctly row':'Not found this row';
            return ['result' => $this -> str_to_bool($result), 'message' => $message];
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Drop Index
    /**
     * Drop Alternative Index.
     * Async Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param callable $callback Async return result of "drop_index"
     * @throws Exception
     */
    public function drop_index_async($table, $index, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index) {
            $result = @unlink($this->upload.$table.'/index/'.$index.'.index');
            $result = $result == null ? false:$result;
            $message = $result ? 'Dropped correctly index':'Not found this index';
            $defer ->resolve(['result' => $this -> str_to_bool($result), 'message' => $message]);
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Drop Alternative Index.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "drop_index"
     * @throws Exception
     */
    public function drop_index($table, $index) {
        if(!$this -> init_async){
            $result = @unlink($this->upload.$table.'/index/'.$index.'.index');
            $result = $result == null ? false:$result;
            $message = $result ? 'Dropped correctly index':'Not found this index';
            return ['result' => $this -> str_to_bool($result), 'message' => $message];
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Make Index
    /**
     * Make Alternative Index.
     * Async Function
     * @param string $table Table Name
     * @param string $index_of Row Name
     * @param string $index Alternative Row Name
     * @param callable $callback Async return result of "make_index"
     * @throws Exception
     */
    public function make_alternative_index_async($table, $index_of, $index, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index_of, $index) {
            if(file_exists($this->upload.$table.'/'.$index_of)){
                if(!file_exists($this->upload.$table.'/index/')){
                    mkdir($this->upload.$table.'/index/');
                }
                $value_list = $this->anti_crash_get($this->upload.$table.'/index/'.$index_of.'.index', $this -> timeout);
                if(strlen($value_list) == 0){
                    $this->anti_crash_put($this->upload.$table.'/index/'.$index_of.'.index', $index, $this -> timeout);
                    $defer -> resolve(['result' => $this -> str_to_bool(true), 'message' => 'Maked correctly alternative index']);
                }else{
                    $defer -> fail(new Exception( 'Error when creating alternative index "' . $index_of. '"'));
                }
            }else{
                $defer -> fail(new Exception( 'Not found row or table'));
            }

        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });

    }

    /**
     * Make Alternative Index.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index_of Row Name
     * @param string $new_index Alternative Row Name
     * @return array Result of "make_index"
     * @throws Exception
     */
    public function make_alternative_index($table, $index_of, $new_index) {
        if(!$this -> init_async){
            if(file_exists($this->upload.$table.'/'.$index_of)){
                if(!file_exists($this->upload.$table.'/index/')){
                    mkdir($this->upload.$table.'/index/');
                }
                $value_list = $this->anti_crash_get($this->upload.$table.'/index/'.$index_of.'.index', $this -> timeout);
                if(strlen($value_list) == 0){
                    $this->anti_crash_put($this->upload.$table.'/index/'.$index_of.'.index', $new_index, $this -> timeout);
                    return ['result' => $this -> str_to_bool(true), 'message' => 'Maked correctly alternative index'];
                }else{
                    return ['result' => $this -> str_to_bool(false), 'message' => 'Not found row "' . $index_of. '"'];
                }
            }else{
                throw new Exception( 'Not found row or table');
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Set Value
    /**
     * Set Value.
     * Async Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param string $key Column Name
     * @param string $values Value of Column
     * @param callable $callback Async return result of "set_value"
     * @throws Exception
     */
    public function set_value_async($table, $index, $key, $values, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index, $key, $values) {
            $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
            $index = strlen($test_index) > 0 ? $test_index:$index;
            $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if(strlen($value_list) > 0){
                $value_list = json_decode($value_list, true);
                $value_list[$key] = $values;
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($value_list, JSON_PRETTY_PRINT), $this -> timeout);
                $defer ->resolve(['result' => $this -> str_to_bool(true), 'message' => 'Setted correctly value of colunm "' . $key . '"']);
            }else{
                $defer -> fail(new Exception( 'Not found table "' . $table . '"'));
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Set Value.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param string $key Column Name
     * @param string $values Value of Column
     * @return array Result of "set_value"
     * @throws Exception
     */
    public function set_value($table, $index, $key, $values) {
        if(!$this -> init_async){
            $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
            $index = strlen($test_index) > 0 ? $test_index:$index;
            $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if(strlen($value_list) > 0){
                $value_list = json_decode($value_list, true);
                $value_list[$key] = $values;
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($value_list, JSON_PRETTY_PRINT), $this -> timeout);
                return ['result' => $this -> str_to_bool(true), 'message' => 'Setted correctly value of colunm "' . $key . '"'];
            }else{
                return ['result' => $this -> str_to_bool(true), 'message' => 'Not found table "' . $table . '"'];
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Get Values
    /**
     * Get Row Values.
     * Async Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param callable $callback Async return result of Result of "get_values"
     * @throws Exception
     */
    public function get_values_async($table, $index, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table, $index) {
            $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
            $index = strlen($test_index) > 0 ? $test_index:$index;
            $return_get = @$this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if($return_get){
                $defer -> resolve(json_decode($return_get, true));
            }else{
                $defer -> fail(new Exception( 'Not found row or table'));
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Get Row Values.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "get_values"
     * @throws Exception
     */
    public function get_values($table, $index) {
        if(!$this -> init_async){
            $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
            $index = strlen($test_index) > 0 ? $test_index:$index;
            $return_get = @$this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
            if($return_get){
                return json_decode($return_get, true);
            }else{
                throw new Exception( 'Not found row or table');
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Get Auto increment index
    /**
     * Get Auto increment index of Row.
     * Async Function
     * @param string $table Table Name
     * @param callable $callback Async return result of Result of "get_auto_increment"
     * @throws Exception
     */
    public function get_auto_increment_async($table, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table) {
            if(file_exists($this->upload.$table)){
                $list_dir = scandir($this->upload.$table);
                $iterator_increment = 0;
                foreach($list_dir as $f_name){
                    if($f_name != '.' && $f_name != '..' && $f_name != 'index'){
                        $iterator_increment++;
                    }
                }
                $defer -> resolve($iterator_increment);
            }else{
                $defer -> fail(new Exception( 'Not found table named "' . $table . '"'));
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }
    
    /**
     * Get Auto increment index of Row.
     * Sequential Function
     * @param string $table Table Name
     * @return int Result of "get_auto_increment"
     * @throws Exception
     */
    public function get_auto_increment($table) {
        if(!$this -> init_async){
            if(file_exists($this->upload.$table)){
                $list_dir = scandir($this->upload.$table);
                $iterator_increment = 0;
                foreach($list_dir as $f_name){
                    if($f_name != '.' && $f_name != '..' && $f_name != 'index'){
                        $iterator_increment++;
                    }
                }
                return $iterator_increment;
            }else{
                throw new Exception( 'Not found table named "' . $table . '"');
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }


    //TODO Get Rows Name
    /**
     * Get Rows Names
     * Async Function
     * @param string $table Table Name
     * @param callable $callback Async return result of Result of "get_rows_name"
     * @throws Exception
     */
    public function get_rows_name_async($table, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table) {
            if(file_exists($this->upload.$table)){
                $dir_list = scandir($this->upload.$table);
                $list_table = [];
                foreach($dir_list as $table){
                    if($table != ".." && $table != "." && $table != "index"){
                        $list_table[] = str_replace('.jdb', '', $table);
                    }
                }
                $defer -> resolve($list_table);
            }else{
                $defer -> fail(new Exception( 'Not found table named "' . $table . '"'));
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Get Rows Names
     * Sequential Function
     * @param string $table Table Name
     * @return array Result of "get_rows_name"
     * @throws Exception
     */
    public function get_rows_name($table) {
        if(!$this -> init_async){
            if(file_exists($this->upload.$table)){
                $dir_list = scandir($this->upload.$table);
                $list_table = [];
                foreach($dir_list as $table){
                    if($table != ".." && $table != "." && $table != "index"){
                        $list_table[] = str_replace('.jdb', '', $table);
                    }
                }
                return $list_table;
            }else{
                throw new Exception( 'Not found table named "' . $table . '"');
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }

    //TODO Get Alternative Rows Name
    /**
     * Get Alternative Rows Names
     * Async Function
     * @param string $table Table Name
     * @param callable $callback Async return result of Result of "get_rows_name"
     * @throws Exception
     */
    public function get_rows_alternative_name_async($table, $callback) {
        $defer = new Deferred;
        Loop::delay(0, function () use ($defer, $table) {
            if(file_exists($this->upload.$table.'/index')){
                $dir_list = scandir($this->upload.$table);
                $list_table = [];
                foreach($dir_list as $table){
                    if($table != ".." && $table != "."){
                        $list_table[] = str_replace('.index', '', $table);
                    }
                }
                $defer -> resolve($list_table);
            }else{
                $defer -> fail(new Exception( 'Not found table named "' . $table . '"'));
            }
        });
        $promises = $defer->promise();
        $promises -> onResolve(function (Throwable $error = null, $result = null) use($callback){
            if (!$error) {
                $callback($result);
            }else{
                throw $error;
            }
        });
    }

    /**
     * Get Alternative Rows Names
     * Sequential Function
     * @param string $table Table Name
     * @return array Result of "get_rows_name"
     * @throws Exception
     */
    public function get_rows_alternative_name($table) {
        if(!$this -> init_async){
            if(file_exists($this->upload.$table)){
                $dir_list = scandir($this->upload.$table.'/index');
                $list_table = [];
                foreach($dir_list as $table){
                    if($table != ".." && $table != "."){
                        $list_table[] = str_replace('.index', '', $table);
                    }
                }
                return $list_table;
            }else{
                throw new Exception( 'Not found table named "' . $table . '"');
            }
        }else{
            throw new Exception( 'JDB is maked in async and can\'t return in sequential mode!');
        }
    }

    /**
     * @param $path
     * @param int $state
     * @return string
     */
    protected function anti_crash_get($path, $state = 5) : string {
        $fh = @fopen($path, 'r');
        if($state > 0){
            if($fh != false){
                if(flock($fh, LOCK_EX)) {
                    $lines = '';
                    while(!feof($fh)) {
                        $lines.= trim(fgets($fh))."\n";
                    }
                    $lines = substr($lines, 0, strlen($lines)-1);
                    flock($fh, LOCK_UN);
                    fclose($fh);
                    $return_data = $this->decrypt($lines, $this->password);
                    if(strlen($return_data) == 0 && @count(json_decode($return_data, true)) == 0){
                        usleep(200*1000);
                        return $this->anti_crash_get($path, $state - 1);
                    }else{
                        return $return_data;
                    }
                }else{
                    fclose($fh);
                    usleep(200*1000);
                    return $this->anti_crash_get($path, $state - 1);
                }
            }else{
                return '';
            }
        }else{
            return '';
        }
    }

    /**
     * @param $path
     * @param $values
     * @param int $state
     * @return bool
     */
    protected function anti_crash_put($path, $values, $state = 5) : bool {
        $fh = fopen($path, 'w');
        if($state > 0){
            if($fh != null){
                if(flock($fh, LOCK_EX)){
                    fwrite($fh, $this->encrypt($values, $this->password));
                    flock($fh, LOCK_UN);
                    fclose($fh);
                    return true;
                }else{
                    fclose($fh);
                    usleep(200*1000);
                    return $this->anti_crash_put($path, $values, $state - 1);
                }
            }else{
                $fh = fopen($path, 'w');
                fwrite($fh, $values);
                fclose($fh);
                return true;
            }
        }else{
            fclose($fh);
            return false;
        }
    }

    /**
     * @param $pure_string
     * @param $encryption_key
     * @return false|string
     */
    protected function encrypt($pure_string, $encryption_key) {
        return openssl_encrypt($pure_string,"AES-128-ECB",$encryption_key);
    }

    /**
     * @param $encrypted_string
     * @param $encryption_key
     * @return false|string
     */
    protected function decrypt($encrypted_string, $encryption_key) {
        return openssl_decrypt($encrypted_string,"AES-128-ECB",$encryption_key);
    }
    /**
     * @param bool $value The Boolean to convert
     * @return string
     */
    protected function str_to_bool($value): string{
        return $value ? 'true' : 'false';
    }
}
