<?php

namespace JDB\sys\database;

class Database {
    //INSERT PATH HERE
    var $upload = '';
    var $password = '';
    function __construct($password = null, $database_location = null){
        if($password != null){
            if($database_location != null){
                $this -> $upload = $database_location . '/database/';
                if(!file_exists($this->upload)){
                    mkdir($this->upload);
                }
            }else{
                echo 'Need folder to specifty where save Database with www-data permission';
            }
        }else{
            echo 'Need login with password';
        }
    }
    public function make_table($table, $index, $values){
        if(!file_exists($this->upload.$table)){
            mkdir($this->upload.$table);
        }
        $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb');
        if(strlen($value_list) == 0 & strlen($index) > 0 & strlen($table) > 0){
            $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($values, JSON_PRETTY_PRINT));
            return true;
        }else{
            return false;
        }
    }
    public function drop_table($table, $index){
        return @unlink($this->upload.$table.'/'.$index.'.jdb');
    }
    public function drop_index($table, $index){
        return @unlink($this->upload.$table.'/index/'.$index.'.index');
    }
    public function make_index($table, $index_of, $index){
        if(!file_exists($this->upload.$table.'/index/')){
            mkdir($this->upload.$table.'/index/');
        }
        $value_list = $this->anti_crash_get($this->upload.$table.'/index/'.$index_of.'.index');
        if(strlen($value_list) == 0){
            $this->anti_crash_put($this->upload.$table.'/index/'.$index_of.'.index', $index);
            return true;
        }else{
            return false;
        }
    }
    public function set_value($table, $index, $key, $values){
        $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index');
        $index = strlen($test_index) > 0 ? $test_index:$index;
        $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb');
        if(strlen($value_list) > 0){
            $value_list = json_decode($value_list, true);
            $value_list[$key] = $values;
            $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($value_list, JSON_PRETTY_PRINT));
            return true;
        }else{
            return false;
        }
    }
    public function get_values($table, $index){
        $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index');
        $index = strlen($test_index) > 0 ? $test_index:$index;
        return json_decode($this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb'), true);
    }
    public function anti_crash_get($path, $state = 500) {
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
    public function anti_crash_put($path, $values, $state = 500) {
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
    public function get_time_edited($file){
        return @filemtime($file);
    }
    public function get_auto_increment($table){
        return count(scandir($this->upload.$table))-3;
    }
    public function get_tables_index($table){
        $dir_list = scandir($this->upload.$table.'/index/');
        $list_table = [];
        foreach($dir_list as $table){
            if($table != ".." && $table != "."){
                $list_table[] = str_replace('.index', '', $table);
            }
        }
        return $list_table;
    }
    function encrypt($pure_string, $encryption_key) {
        return openssl_encrypt($pure_string,"AES-128-ECB",$encryption_key);
    }
    function decrypt($encrypted_string, $encryption_key) {
        return openssl_decrypt($encrypted_string,"AES-128-ECB",$encryption_key);
    }
}
