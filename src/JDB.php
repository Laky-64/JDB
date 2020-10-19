<?php
namespace laky64\database;
use Exception;

class JDB{
    protected $upload;
    protected $password;
    protected $timeout;

    /**
     * Database constructor.
     * Sequential Function
     * @param string|null $password Make password for your database
     * @param string|null $database_location For example /var/www/html
     * @param int|null $timeout_seconds Time in seconds
     * @throws Exception
     */
    function __construct($password = null, $database_location = null, $timeout_seconds = 1){
        if($password != null){
            if($database_location != null){
                $this->password = $password;
                $this->upload = $database_location;
                $this->timeout = $timeout_seconds;
                if(!file_exists($this->upload)){
                    mkdir($this->upload);
                }
            }else{
                throw new Exception('Need folder to specify where save Database with www-data permission');
            }
        }else{
            throw new Exception( 'Need login with password');
        }
    }

    /**
     * Make row.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param array $values Values to set
     * @return array Result of "make_row"
     * @throws Exception
     * @deprecated
     */
    public function make_row($table = '', $index = '', $values = []){
        if(!file_exists($this->upload.$table)){
            mkdir($this->upload.$table);
        }
        $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
        if(strlen($value_list) == 0 & strlen($index) > 0 & strlen($table) > 0){
            $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($values, JSON_PRETTY_PRINT), $this -> timeout);
            return ['result' => $this -> str_to_bool(true), 'message' => 'Maked Correctly Table and Row'];
        }else{
            throw new Exception('Already Exist this Table and Row');
        }
    }

    /**
     * Drop Row.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "drop_row"
     * @throws Exception
     * @deprecated
     */
    public function drop_row($table, $index) {
        $result = @unlink($this->upload.$table.'/'.$index.'.jdb');
        $result = $result == null ? false:$result;
        $message = $result ? 'Dropped correctly row':'Not found this row';
        return ['result' => $this -> str_to_bool($result), 'message' => $message];
    }


    //TODO Drop Index
    /**
     * Drop Alternative Index.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "drop_index"
     * @throws Exception
     * @deprecated
     */
    public function drop_index($table, $index) {
        $result = @unlink($this->upload.$table.'/index/'.$index.'.index');
        $result = $result == null ? false:$result;
        $message = $result ? 'Dropped correctly index':'Not found this index';
        return ['result' => $this -> str_to_bool($result), 'message' => $message];
    }


    //TODO Make Index
    /**
     * Make Alternative Index.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param string $new_index Alternative Row Name
     * @return array Result of "make_index"
     * @throws Exception
     * @deprecated
     */
    public function make_alternative_index($table, $index, $new_index) {
        if(file_exists($this->upload.$table.'/'.$index.'.jdb')){
            if(!file_exists($this->upload.$table.'/index/')){
                mkdir($this->upload.$table.'/index/');
            }
            $value_list = $this->anti_crash_get($this->upload.$table.'/index/'.$new_index.'.index', $this -> timeout);
            if(strlen($value_list) == 0){
                $this->anti_crash_put($this->upload.$table.'/index/'.$new_index.'.index', $index, $this -> timeout);
                return ['result' => $this -> str_to_bool(true), 'message' => 'Maked correctly alternative index'];
            }else{
                throw new Exception('Alternative index "' . $new_index. '" already exist');
            }
        }else{
            throw new Exception( 'Not found row or table');
        }
    }


    //TODO Set Value
    /**
     * Set Value.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @param string $key Column Name
     * @param string|array $values Value of Column
     * @return array Result of "set_value"
     * @throws Exception
     * @deprecated
     */
    public function set_value($table, $index, $key, $values) {
        $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
        $index = strlen($test_index) > 0 ? $test_index:$index;
        $value_list = $this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
        if(strlen($value_list) > 0){
            $value_list = json_decode($value_list, true);
            $value_list[$key] = $values;
            $this->anti_crash_put($this->upload.$table.'/'.$index.'.jdb', json_encode($value_list, JSON_PRETTY_PRINT), $this -> timeout);
            return ['result' => $this -> str_to_bool(true), 'message' => 'Setted correctly value of column "' . $key . '"'];
        }else{
            return ['result' => $this -> str_to_bool(true), 'message' => 'Not found table "' . $table . '"'];
        }
    }


    //TODO Get Values
    /**
     * Get Row Values.
     * Sequential Function
     * @param string $table Table Name
     * @param string $index Row Name
     * @return array Result of "get_values"
     * @throws Exception
     * @deprecated
     */
    public function get_values($table, $index) {
        $test_index = $this->anti_crash_get($this->upload.$table.'/index/'.$index.'.index', $this -> timeout);
        $index = strlen($test_index) > 0 ? $test_index:$index;
        $return_get = @$this->anti_crash_get($this->upload.$table.'/'.$index.'.jdb', $this -> timeout);
        if($return_get){
            return json_decode($return_get, true);
        }else{
            throw new Exception( 'Not found row or table');
        }
    }


    //TODO Get Auto increment index
    /**
     * Get Auto increment index of Row.
     * Sequential Function
     * @param string $table Table Name
     * @return int Result of "get_auto_increment"
     * @throws Exception
     * @deprecated
     */
    public function get_auto_increment($table) {
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
    }


    //TODO Get Rows Name
    /**
     * Get Rows Names
     * Sequential Function
     * @param string $table Table Name
     * @return array Result of "get_rows_name"
     * @throws Exception
     * @deprecated
     */
    public function get_rows_name($table) {
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
    }

    //TODO Get Alternative Rows Name
    /**
     * Get Alternative Rows Names
     * Sequential Function
     * @param string $table Table Name
     * @return array Result of "get_rows_name"
     * @throws Exception
     * @deprecated
     */
    public function get_rows_alternative_name($table) {
        if(file_exists($this->upload.$table.'/index')){
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
    }

    /**
     * @param $path
     * @param int $state
     * @return string
     */
    protected function anti_crash_get($path, $state = 5)  {
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
    protected function anti_crash_put($path, $values, $state = 5) {
        $fh = @fopen($path, 'w');
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
                $fh = @fopen($path, 'w');
                @fwrite($fh, $values);
                @fclose($fh);
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
    protected function str_to_bool($value){
        return $value ? 'true' : 'false';
    }
}