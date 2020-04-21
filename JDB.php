<?php
    
    namespace JDB\sys\database;
    
    class Database {
        //add folder to upload, example /var/www/html/database/
        var $upload = '';
        //add folder to upload, example https://yourwebsite.com/database/
        var $download = '';
        
        public function make_table($table, $index, $values){
            $value_list = $this->anti_crash_get($this->download.$table.'/'.$index.'.json');
            if(strlen($value_list) == 0){
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.json', json_encode($values, JSON_PRETTY_PRINT));
                return true;
            }else{
                return false;
            }
        }
        public function drop_table($table, $index){
            return @unlink($this->upload.$table.'/'.$index.'.json');
        }
        public function make_index($table, $index_of, $index){
            $value_list = $this->anti_crash_get($this->download.$table.'/index/'.$index_of.'.index');
            if(strlen($value_list) == 0){
                $this->anti_crash_put($this->upload.$table.'/index/'.$index_of.'.index', $index);
                return true;
            }else{
                return false;
            }
        }
        public function set_value($table, $index, $key, $values){
            $test_index = $this->anti_crash_get($this->download.$table.'/index/'.$index.'.index');
            $index = strlen($test_index) > 0 ? $test_index:$index;
            $value_list = $this->anti_crash_get($this->download.$table.'/'.$index.'.json');
            if(strlen($value_list) > 0){
                $value_list = json_decode($value_list, true);
                $value_list[$key] = $values;
                $this->anti_crash_put($this->upload.$table.'/'.$index.'.json', json_encode($value_list, JSON_PRETTY_PRINT));
                return true;
            }else{
                return false;
            }
        }
        public function get_values($table, $index){
            $test_index = $this->anti_crash_get($this->download.$table.'/index/'.$index.'.index');
            $index = strlen($test_index) > 0 ? $test_index:$index;
            return json_decode($this->anti_crash_get($this->download.$table.'/'.$index.'.json'), true);
        }
        public function anti_crash_get($path, $state = 50) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result_test = $response != false;
            if(($state == 0 && $result_test == false && strlen($response) > 0)||$httpCode == '404'||$httpCode == '301'){
                return '';
            }else if($state > 0 && $result_test == false && strlen($response) > 0){
                return $this->anti_crash_get($path, $state - 1);
            }else{
                return $response;
            }
        }
        public function anti_crash_put($path, $values, $state = 50) {
            if($this->get_time_edited($path) == time()){
                usleep(500*1000);
            }
            $test1 = $this->anti_crash_get(str_replace($this->upload, $this->download, $path));
            $edited = file_put_contents($path, $values);
            $test2 = $this->anti_crash_get(str_replace($this->upload, $this->download, $path));
            if($state == 0 && $test1 == $test2){
                return false;
            }else if($state > 0 && $test1 == $test2){
                return $this->anti_crash_put($path, $values, $state - 1);
            }else{
                return true;
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
    }
?>
