<?php
namespace laky64\database;
use Amp\Loop;

class JDB_CORE
{
    /**
     * 1 I/O Thread, 1 Controller Unit and 1 Operation Thread (Very fast and optimized for high level of multiple connections but not async)
     */
    public static int $ONE_THREAD = 1;
    /**
     * 1 I/O Thread, 1 Controller Unit and 2 Operation Thread (Very fast but not optimized for multiple connections)
     */
    public static int $TWO_THREAD = 2;
    /**
     * 1 I/O Thread, 1 Controller Unit and 4 Operation Thread (Fast and optimized for mid level of multiple connections)
     */
    public static int $FOUR_THREAD = 4;
    /**
     * 1 I/O Thread, 1 Controller Unit and 8 Operation Thread (Slow but optimized for high level of multiple connections)
     */
    public static int $EIGHT_THREAD = 8;
    /**
     * 1 I/O Thread, 1 Controller Unit and 12 Operation Thread (Very slow but optimized for very high level of multiple connections)
     */
    public static int $TWELVE_THREAD = 12;

    protected static string $WINDOWS_ENVIRONMENT = '\\';
    protected static string $LINUX_ENVIRONMENT = '/';
    protected string $ENVIRONMENT_SEPARATOR = '';
    protected string $DATABASE_FOLDER = '';
    protected int $OPERATION_THREADS = 0;
    protected int $NUM_ACTIVE_OPERATION_THREAD = 0;
    protected int $TICK = 0;
    protected array $DATA_INPUT_ARRAY_OPERATION = [];
    protected array $DB_GLOBAL = [];
    protected array $STATE_CORE = [
        'OPERATION_THREADS' => []
    ];
    protected CustomMemcache $INSTANCE_RAM;

    function __construct(int $NUM_THREAD, string $PATH_FOLDER, string $MEM_CACHE_IP = '127.0.0.1', string $MEM_CACHE_PORT = '1211'){
        echo 'Detecting environment...' . PHP_EOL;
        if($PATH_FOLDER[0] == '/'){
            $this -> ENVIRONMENT_SEPARATOR = self::$LINUX_ENVIRONMENT;
            echo 'Detected Linux environment!' . PHP_EOL;
        }else{
            preg_match_all('/.*:\\\\/', $PATH_FOLDER, $is_windows);
            if(isset($is_windows[0][0])){
                $this -> ENVIRONMENT_SEPARATOR = self::$WINDOWS_ENVIRONMENT;
                echo 'Detected Windows environment!' . PHP_EOL;
            }else{
                die('Please set database folder with root path');
            }
        }
        echo 'Setting JDBI variables...' . PHP_EOL;
        $this->OPERATION_THREADS = $NUM_THREAD;
        echo 'Setted up JDBI variables!' . PHP_EOL;
        echo 'Setting database folder!' . PHP_EOL;
        if(file_exists($PATH_FOLDER) && is_dir($PATH_FOLDER)){
            $this->DATABASE_FOLDER = $PATH_FOLDER . $this -> ENVIRONMENT_SEPARATOR . 'database';
            if(!file_exists($this->DATABASE_FOLDER)){
                mkdir($this->DATABASE_FOLDER);
            }
            echo 'Setted up database folder!' . PHP_EOL;
            echo 'Connecting to local Memcache server...' . PHP_EOL;
            $this->INSTANCE_RAM = new CustomMemcache;
            if(@$this->INSTANCE_RAM -> connect($MEM_CACHE_IP,$MEM_CACHE_PORT)){
                echo 'Succeffully connected to Memcache server!' . PHP_EOL;
            }else{
                die('Error when connecting to memcached server!');
            }
            echo 'Importing database from disk to ram...' . PHP_EOL;
            $list_db = scandir($this->DATABASE_FOLDER);
            foreach ($list_db as $file){
                if($file != '.' && $file != '..'){
                    $CONTENT = file_get_contents($this->DATABASE_FOLDER . $this->ENVIRONMENT_SEPARATOR . $file);
                    if($file[0] != '.'){
                        $CONTENT = json_decode($this -> decrypt($CONTENT, file_get_contents($this->DATABASE_FOLDER . $this->ENVIRONMENT_SEPARATOR . '.' . $file)), true);
                    }
                    $this->DB_GLOBAL[str_replace('.jdb', '', $file)] = $CONTENT;
                }
            }
            echo 'Imported succefully database from disk!' . PHP_EOL;
            echo 'Starting running ' . $this->OPERATION_THREADS . ' threads...' . PHP_EOL;
            if($NUM_THREAD == self::$ONE_THREAD){
                $this->NUM_ACTIVE_OPERATION_THREAD++;
                Loop::run(function () {
                    $this->NUM_ACTIVE_OPERATION_THREAD++;
                    Loop::repeat(10,function (){
                        if($this->TICK >= 500){
                            $this->TICK = 0;
                            $this->execute_io();
                        }
                        $this->execute_control_unit();
                        $this->execute_operation(1);
                        $this->TICK += 10;
                    });
                });
            }else{
                Loop::run(function() {
                    Loop::repeat(1,function (){
                        if($this->NUM_ACTIVE_OPERATION_THREAD < $this->OPERATION_THREADS){
                            $this->NUM_ACTIVE_OPERATION_THREAD++;
                            $num_thread = $this->NUM_ACTIVE_OPERATION_THREAD;
                            $this->join_operation_thread($num_thread);
                        }
                    });
                    $this->join_io_thread();
                    $this->join_control_unit_thread();
                });
            }
        }else{
            die('The path not exist or is file');
        }
    }

    //JDBI I/O THREAD
    protected function join_io_thread(){
        Loop::repeat($msInterval =  500, function (){
            $this->execute_io();
            usleep(10000);
        });
    }

    protected function execute_io() {
        if(!isset($this->STATE_CORE['IO_THREAD'])){
            echo 'Started I/O Thread' . PHP_EOL;
        }
        $this->STATE_CORE['IO_THREAD'] = [
            'state' => 'RUNNING(' . time() . ')'
        ];
        foreach ($this->DB_GLOBAL as $DB_NAME => $DB_CONTENT){
            $DATA = $DB_CONTENT;
            if($DB_NAME[0] != '.'){
                $DATA = $this -> encrypt(json_encode($DB_CONTENT, true), $this->DB_GLOBAL['.'.$DB_NAME]);
            }
            file_put_contents($this->DATABASE_FOLDER . $this->ENVIRONMENT_SEPARATOR . $DB_NAME . '.jdb', $DATA);
        }
    }
    //END I/O THREAD

    //JDBI CONTROL UNIT THREADS
    protected function join_control_unit_thread(){
        Loop::repeat($msInterval = 10, function (){
            $this->execute_control_unit();
            usleep(10000);
        });
    }

    protected function execute_control_unit() {
        if(!isset($this->STATE_CORE['CONTROLLER_UNIT'])){
            echo 'Started Control Unit Thread' . PHP_EOL;
        }
        $this->STATE_CORE['CONTROLLER_UNIT'] = [
            'state' => 'RUNNING(' . time() . ')'
        ];
        $list_req = $this -> INSTANCE_RAM -> getAllKeys();
        if(count($list_req) > 0){
            foreach ($list_req as $key){
                if(strpos($key, 'JDB_REQ_') !== false) {
                    if($this->INSTANCE_RAM->get($key) != ''){
                        $save_to_thread_id = 1;
                        $num_more_free_space = 9999999999999999;
                        foreach ($this->DATA_INPUT_ARRAY_OPERATION as $thread_id => $thread) {
                            if (count($thread) < $num_more_free_space) {
                                $num_more_free_space = count($thread);
                                $save_to_thread_id = $thread_id;
                            }
                        }
                        $this->DATA_INPUT_ARRAY_OPERATION[$save_to_thread_id][] = [
                            'id' => $key,
                            'req' => json_decode($this->INSTANCE_RAM->get($key), true)
                        ];
                    }
                    $this->INSTANCE_RAM->delete($key);
                }
            }
        }
    }
    //END JDBI CONTROL UNIT THREAD

    //OPERATION THREAD
    protected function join_operation_thread($num_thread){
        $this->DATA_INPUT_ARRAY_OPERATION[$num_thread] = [];
        Loop::repeat($msInterval = 10, function () use ($num_thread){
            $this->execute_operation($num_thread);
            usleep(10000);
        });
    }

    protected function execute_operation($ID_THREAD) {
        if(!isset($this->STATE_CORE['OPERATION_THREADS'][$ID_THREAD])){
            echo 'Started Operation Thread n.' . $ID_THREAD . PHP_EOL;
            $this->DATA_INPUT_ARRAY_OPERATION[$ID_THREAD] = [];
        }
        $this->STATE_CORE['OPERATION_THREADS'][$ID_THREAD] = [
            'lastupdate' => time(),
            'pending' => count($this->DATA_INPUT_ARRAY_OPERATION[$ID_THREAD])
        ];
        $core_active = 0;
        for ($i = 1; $i < $this->OPERATION_THREADS + 1;$i++){
            if(isset($this->STATE_CORE['OPERATION_THREADS'][$i]['lastupdate'])){
                $core_active += $this->STATE_CORE['OPERATION_THREADS'][$i]['pending'];
                $this->STATE_CORE['OPERATION_THREADS'][$i]['state'] = time() - $this->STATE_CORE['OPERATION_THREADS'][$i]['lastupdate'] < 3 ? 'RUNNING':'OFFLINE';
                unset($this->STATE_CORE['OPERATION_THREADS'][$i]['lastupdate']);
            }
        }
        $this->STATE_CORE['OPERATION_THREADS_USAGE'] = ($core_active * 100) / $this->OPERATION_THREADS;
        $this->INSTANCE_RAM->add('THREAD_ALIVE_' . $ID_THREAD,time());
        $id_operation = @array_key_first($this->DATA_INPUT_ARRAY_OPERATION[$ID_THREAD]);
        if(is_int($id_operation)) {
            $DATA_OPERATION = $this->DATA_INPUT_ARRAY_OPERATION[$ID_THREAD][$id_operation];
            if(isset($DATA_OPERATION['req']['DB_DATA'])){
                $DB_DATA = $DATA_OPERATION['req']['DB_DATA'];
                $ACTION = $DATA_OPERATION['req']['DB_DATA']['QUERY']['ACTION'];
                $QUERY_DATA = $DATA_OPERATION['req']['DB_DATA']['QUERY']['DATA'];
                $OPERATION_ID = str_replace('JDB_REQ_', 'JDB_RESULT_', $DATA_OPERATION['id']);
                if($ACTION == 'CONNECT'){
                    if(isset($this->DB_GLOBAL[$QUERY_DATA['DB_NAME']]) && $QUERY_DATA['DB_NAME'][0] != '.'){
                        $TEST_PASS = $this->DB_GLOBAL['.' . $QUERY_DATA['DB_NAME']] == $QUERY_DATA['DB_PASSWORD'];
                        if($TEST_PASS != false){
                            $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'MAKE_DB'){
                    if(!isset($this->DB_GLOBAL[$QUERY_DATA['DB_NAME']]) && $QUERY_DATA['DB_NAME'][0] != '.'){
                        $this->DB_GLOBAL[$QUERY_DATA['DB_NAME']] = [];
                        $this->DB_GLOBAL['.' . $QUERY_DATA['DB_NAME']] = $QUERY_DATA['DB_PASSWORD'];
                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'DROP_DB'){
                    if(isset($this->DB_GLOBAL[$QUERY_DATA['DB_NAME']]) && $QUERY_DATA['DB_NAME'][0] != '.'){
                        unset($this->DB_GLOBAL[$QUERY_DATA['DB_NAME']]);
                        unset($this->DB_GLOBAL['.' . $QUERY_DATA['DB_NAME']]);
                        unlink($this->DATABASE_FOLDER . $this->ENVIRONMENT_SEPARATOR . $QUERY_DATA['DB_NAME'] . '.jdb');
                        unlink($this->DATABASE_FOLDER . $this->ENVIRONMENT_SEPARATOR . '.' .  $QUERY_DATA['DB_NAME'] . '.jdb');
                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'GET_DATABASE') {
                    if (isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD'])) {
                        if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])) {
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                $RESULT_DATA = [];
                                foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']] as $TABLE_NAME => $CONTENT){
                                    $RESULT_DATA[] = $TABLE_NAME;
                                }
                                $this->INSTANCE_RAM->add($OPERATION_ID, json_encode($RESULT_DATA, true));
                            } else {
                                $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                            }
                        } else {
                            $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                        }
                    } else {
                        $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                    }
                }else if($ACTION == 'MAKE_TABLE'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(!isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$QUERY_DATA['NAME']])){
                                    $CONTENT = [];
                                    $found_invalid_index = false;
                                    foreach ($QUERY_DATA['COLUMNS'] as $KEY => $TEST){
                                        if(is_int($KEY) && $TEST[0] != '.'){
                                            $CONTENT[$TEST] = [];
                                        }else{
                                            $found_invalid_index = true;
                                            break;
                                        }
                                    }
                                    if(!isset($CONTENT[$QUERY_DATA['PRIMARY_KEY']])){
                                        $found_invalid_index = true;
                                    }else{
                                        $CONTENT['.primary_key'] = $QUERY_DATA['PRIMARY_KEY'];
                                    }
                                    $CONTENT['.primary_key_type'] = $QUERY_DATA['AUTO_INCREMENT'] ? 'AUTO_INCREMENT':'DEFINED';
                                    if(!$found_invalid_index && $QUERY_DATA['NAME'][0] != '.'){
                                        $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$QUERY_DATA['NAME']] = $CONTENT;
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                                    }else{
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                    }
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'DROP_TABLE'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$QUERY_DATA['NAME']]) && $QUERY_DATA['NAME'][0] != '.'){
                                    unset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$QUERY_DATA['NAME']]);
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'MAKE_ROW'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']])){
                                    $ERROR_INDEX = false;
                                    if($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key_type'] == 'AUTO_INCREMENT'){
                                        if(!isset($QUERY_DATA['ROW_VALUES'][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']])){
                                            $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']][] = count($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']]);
                                        }else{
                                            $ERROR_INDEX = true;
                                        }
                                    }else{
                                        if(isset($QUERY_DATA['ROW_VALUES'][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']])){
                                            foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $COL => $ROW){
                                                if($QUERY_DATA['ROW_VALUES'][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] == $ROW){
                                                    $ERROR_INDEX = true;
                                                }
                                            }
                                        }else{
                                            $ERROR_INDEX = true;
                                        }
                                    }
                                    $found_invalid_index = false;
                                    foreach ($QUERY_DATA['ROW_VALUES'] as $COL => $ROW){
                                        if($COL[0] == '.' || !isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COL])) {
                                            $found_invalid_index = true;
                                        }
                                    }
                                    if(!$found_invalid_index && !$ERROR_INDEX){
                                        foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']] as $COL => $ROW){
                                            if($COL[0] != '.' && !($COL == $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key'] && $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key_type'] == 'AUTO_INCREMENT')){
                                                $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COL][] = isset($QUERY_DATA['ROW_VALUES'][$COL]) ? $QUERY_DATA['ROW_VALUES'][$COL]:null;
                                            }
                                        }
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                                    }else{
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                    }
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'SET_ROW'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]) && isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']])){
                                    $INDEX_OF_ROW = [];
                                    foreach($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']] as $KEY => $VALUE){
                                        if($QUERY_DATA['ROW_BY'] == $VALUE){
                                            $INDEX_OF_ROW[] = $KEY;
                                        }
                                    }
                                    if($INDEX_OF_ROW != []){
                                        foreach ($INDEX_OF_ROW as $INDEX){
                                            foreach($QUERY_DATA['ROW_VALUES'] as $COL => $ROW){
                                                $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COL][$INDEX] = $ROW;
                                            }
                                        }
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                                    }else{
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                    }
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'DROP_ROW'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]) && isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']])){
                                    $RESULT_OPERATION = 'false';
                                    foreach($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']] as $KEY => $VALUE){
                                        if($VALUE == $QUERY_DATA['ROW_BY']){
                                            foreach($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']] as $COL => $ROW){
                                                if($COL[0] != '.'){
                                                    unset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COL][$KEY]);
                                                }
                                            }
                                            $RESULT_OPERATION = 'true';
                                        }
                                    }
                                    if($RESULT_OPERATION == 'true'){
                                        $this->INSTANCE_RAM->add($OPERATION_ID,'true');
                                    }
                                    $this->INSTANCE_RAM->add($OPERATION_ID,$RESULT_OPERATION);
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'false');
                    }
                }else if($ACTION == 'GET_ROW'){
                    if(isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])){
                        if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])){
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if($TEST_PASS != false){
                                if(isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]) && isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']])){
                                    $RESULT_DATA = [];
                                    foreach($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$QUERY_DATA['COLUMN_BY']] as $KEY => $VALUE){
                                        $TMP_DATA = [];
                                        foreach($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']] as $COL => $ROW){
                                            if($COL[0] != '.'){
                                                $TMP_DATA[$COL] = $ROW[$KEY];
                                            }
                                        }
                                        if($VALUE == $QUERY_DATA['ROW_BY']){
                                            $RESULT_DATA[] = $TMP_DATA;
                                        }
                                    }
                                    $this->INSTANCE_RAM->add($OPERATION_ID,json_encode($RESULT_DATA,true));
                                }else{
                                    $this->INSTANCE_RAM->add($OPERATION_ID,'null');
                                }
                            }else{
                                $this->INSTANCE_RAM->add($OPERATION_ID,'null');
                            }
                        }else{
                            $this->INSTANCE_RAM->add($OPERATION_ID,'null');
                        }
                    }else{
                        $this->INSTANCE_RAM->add($OPERATION_ID,'null');
                    }
                }else if($ACTION == 'GET_TABLE') {
                    if (isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])) {
                        if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])) {
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if ($TEST_PASS != false) {
                                if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']])) {
                                    $RESULT_DATA = [];
                                    foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $KEY => $VALUE) {
                                        $TMP_DATA = [];
                                        foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']] as $COL => $ROW) {
                                            if ($COL[0] != '.') {
                                                $TMP_DATA[$COL] = $ROW[$KEY];
                                            }
                                        }
                                        $RESULT_DATA[] = $TMP_DATA;
                                    }
                                    $this->INSTANCE_RAM->add($OPERATION_ID, json_encode($RESULT_DATA, true));
                                } else {
                                    $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                                }
                            } else {
                                $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                            }
                        } else {
                            $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                        }
                    } else {
                        $this->INSTANCE_RAM->add($OPERATION_ID, 'null');
                    }
                }else if($ACTION == 'ADD_COLUMN') {
                    if (isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])) {
                        if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])) {
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if ($TEST_PASS != false) {
                                if (isset($QUERY_DATA['COLUMNS']) && isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']])) {
                                    $found_invalid_index = false;
                                    foreach ($QUERY_DATA['COLUMNS'] as $COLUMN) {
                                        foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $ITEM) {
                                            if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COLUMN])) {
                                                $found_invalid_index = true;
                                            }
                                        }
                                    }
                                    if (!$found_invalid_index) {
                                        foreach ($QUERY_DATA['COLUMNS'] as $COLUMN) {
                                            foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $ITEM) {
                                                $this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COLUMN][] = null;
                                            }
                                        }
                                        $this->INSTANCE_RAM->add($OPERATION_ID, 'true');
                                    } else {
                                        $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                                    }
                                } else {
                                    $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                                }
                            } else {
                                $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                            }
                        } else {
                            $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                        }
                    } else {
                        $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                    }
                }else if($ACTION == 'DROP_COLUMN'){
                    if (isset($DB_DATA['DB_NAME']) && isset($DB_DATA['DB_PASSWORD']) && isset($DB_DATA['TABLE_NAME'])) {
                        if (isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']])) {
                            $TEST_PASS = $this->DB_GLOBAL['.' . $DB_DATA['DB_NAME']] == $DB_DATA['DB_PASSWORD'];
                            if ($TEST_PASS != false) {
                                if (isset($QUERY_DATA['COLUMNS']) && isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']])) {
                                    $found_invalid_index = false;
                                    foreach ($QUERY_DATA['COLUMNS'] as $COLUMN) {
                                        foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $ITEM) {
                                            if (!isset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COLUMN])) {
                                                $found_invalid_index = true;
                                            }
                                        }
                                    }
                                    if (!$found_invalid_index) {
                                        foreach ($QUERY_DATA['COLUMNS'] as $COLUMN) {
                                            foreach ($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']]['.primary_key']] as $ITEM) {
                                                unset($this->DB_GLOBAL[$DB_DATA['DB_NAME']][$DB_DATA['TABLE_NAME']][$COLUMN]);
                                            }
                                        }
                                        $this->INSTANCE_RAM->add($OPERATION_ID, 'true');
                                    } else {
                                        $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                                    }
                                } else {
                                    $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                                }
                            } else {
                                $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                            }
                        } else {
                            $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                        }
                    } else {
                        $this->INSTANCE_RAM->add($OPERATION_ID, 'false');
                    }
                }else if($ACTION == 'GET_STATUS') {
                    $this->INSTANCE_RAM->add($OPERATION_ID, json_encode($this->STATE_CORE, true));
                }else{
                    $this->INSTANCE_RAM->add($OPERATION_ID,'ERROR_UNKNOWN_OPERATION');
                }
            }
        }
        unset($this->DATA_INPUT_ARRAY_OPERATION[$ID_THREAD][$id_operation]);
    }
    //END OPERATION THREAD

    //OPENSSL CRYPTATION
    protected function encrypt($pure_string, $encryption_key) {
        return openssl_encrypt($pure_string,"AES-128-ECB",$encryption_key);
    }
    protected function decrypt($encrypted_string, $encryption_key) {
        return openssl_decrypt($encrypted_string,"AES-128-ECB",$encryption_key);
    }
    //END OPENSSL CRYPTATION
}