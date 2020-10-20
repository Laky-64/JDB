<?php
namespace laky64\database;

use Exception;

class JDBI {
    protected CustomMemcache $INSTANCE_RAM;
    protected string $DB_NAME;
    protected string $DB_PASSWORD;
    protected bool $IS_CONNECTED = false;
    protected int $TIMEOUT = 4;

    /**
     * JDBI constructor.
     * Create instance to local TCP/UDP server using Sockets.
     * @param string $MEM_CACHE_IP Memcached Server IP [optional].
     * @param string $MEM_CACHE_PORT Memcached Server Port [optional].
     * @param int $TIMEOUT Max retry time [optional].
     * @throws Exception
     * @link https://github.com/Laky-64/JDB
     */
    function __construct(string $MEM_CACHE_IP = '127.0.0.1', string $MEM_CACHE_PORT = '1211', int $TIMEOUT = 4){
        $this->INSTANCE_RAM = new CustomMemcache();
        $this->TIMEOUT = $TIMEOUT;
        if(!@$this->INSTANCE_RAM -> connect($MEM_CACHE_IP,$MEM_CACHE_PORT)){
            throw new Exception('Error when connecting to memcached server!');
        }
    }

    /**
     * Connect to database.
     * @param string $DB_NAME JDB database name.
     * @param string $DB_PASSWORD JDB database password.
     * @return bool true on success or false on failure.
     */
    public function connect(string $DB_NAME, string $DB_PASSWORD):bool{
        $this->DB_NAME = $DB_NAME;
        $this->DB_PASSWORD = $DB_PASSWORD;
        $OPERATION_EXECUTE = [
            'DB_DATA' => [
                'QUERY' => [
                    'ACTION' => 'CONNECT',
                    'DATA' => [
                        'DB_NAME' => $DB_NAME,
                        'DB_PASSWORD' => $DB_PASSWORD,
                    ]
                ]
            ]
        ];
        $CONN_RES = $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        $this->IS_CONNECTED = $CONN_RES;
        return $CONN_RES;
    }

    /**
     * Connect to database.
     * @param string $QUERY Query to execute.
     * @return mixed Result of query
     * @throws Exception
     */
    public function query(string $QUERY){
        $NF_ERROR_MSG = 'Error: Not found this command!';
        $INVALID_COMMAND = 'Error: Invalid command!';
        if($QUERY[strlen($QUERY) -1] == ';'){
            preg_match('/CREATE.*DATABASE.*`(.*?)`.*PASSWORD.*`(.*?)`;/', $QUERY, $CMD1);
            preg_match('/DROP.*DATABASE.*`(.*?)`;/', $QUERY, $CMD2);
            preg_match('/CREATE.*TABLE.*`(.*?)`.*\((.*?)\).*AS.*PRIMARY.*`(.*?)`.*TYPE.*`(.*?)`;/',$QUERY,$CMD3);
            preg_match('/DROP.*TABLE.*`(.*?)`;/', $QUERY, $CMD4);
            preg_match('/INSERT.*INTO.*`(.*?)`.*\((.*?)\).*VALUES.*\((.*?)\);/', $QUERY, $CMD5);
            preg_match('/UPDATE.*`(.*?)`.*SET.*\((.*?)\).*VALUES.*\((.*?)\).*WHERE.*`(.*?)`.*IS.*`(.*?)`;/', $QUERY, $CMD6);
            preg_match('/DELETE.*FROM.*`(.*?)`.*WHERE.*`(.*?)`.*IS.*`(.*?)`;/', $QUERY, $CMD7);
            preg_match('/SELECT.*\*.*FROM.*`(.*?)`.*WHERE.*`(.*?)`.*IS.*`(.*?)`;/', $QUERY, $CMD8);
            preg_match('/SELECT.*\*.*FROM.*`(.*?)`;/', $QUERY, $CMD9);
            preg_match('/SHOW.*TABLES;/', $QUERY, $CMD10);
            preg_match('/SHOW.*PROCESSLIST;/', $QUERY, $CMD11);
            preg_match('/ALTER.*TABLE.*`(.*?)`.*ADD.*COLUMN.*\((.*?)\);/', $QUERY, $CMD12);
            preg_match('/ALTER.*TABLE.*`(.*?)`.*DROP.*COLUMN.*\((.*?)\);/', $QUERY, $CMD13);
            if($CMD1 != null){
                if(isset($CMD1[1]) && isset($CMD1[2])){
                    return $this->make_db($CMD1[1], $CMD1[2]);
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD2 != null){
                if(isset($CMD2[1])){
                    return $this->drop_db($CMD2[1]);
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD3 != null){
                if(isset($CMD3[1]) && isset($CMD3[2]) && isset($CMD3[3]) && isset($CMD3[4])){
                    $VARS = $this->convert_to_var($CMD3[2]);
                    if(count($VARS) > 0){
                        if($CMD3[4] == 'AUTO_INCREMENT'){
                            $w_ai = true;
                        }else if($CMD3[4] == 'DEFINED'){
                            $w_ai = false;
                        }else{
                            $this->close();
                            throw new Exception($INVALID_COMMAND);
                        }
                        return $this->make_table($CMD3[1],$VARS,$CMD3[3],$w_ai);
                    }else {
                        $this->close();
                        throw new Exception($INVALID_COMMAND);
                    }
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD4 != null) {
                if (isset($CMD4[1])) {
                    return $this->drop_table($CMD4[1]);
                } else {
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD5 != null){
                if(isset($CMD5[1]) && isset($CMD5[2]) && isset($CMD5[3])){
                    $VARS = $this->convert_to_var($CMD5[2],$CMD5[3]);
                    if(count($VARS) > 0){
                        return $this->make_row($CMD5[1], $VARS);
                    }else{
                        $this->close();
                        throw new Exception($INVALID_COMMAND);
                    }
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD6 != null){
                if(isset($CMD6[1]) && isset($CMD6[2]) && isset($CMD6[3]) && isset($CMD6[4]) && isset($CMD6[5])){
                    $VARS = $this->convert_to_var($CMD6[2],$CMD6[3]);
                    if(count($VARS) > 0){
                        return $this->set_row($CMD6[1],$VARS,$CMD6[4],$CMD6[5]);
                    }else{
                        $this->close();
                        throw new Exception($INVALID_COMMAND);
                    }
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD7 != null){
                if(isset($CMD7[1]) && isset($CMD7[2]) && isset($CMD7[3])){
                    return $this->drop_row($CMD7[1],$CMD7[2],$CMD7[3]);
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD8 != null){
                if(isset($CMD8[1]) && isset($CMD8[2]) && isset($CMD8[3])){
                    return $this->get_row($CMD8[1], $CMD8[2], $CMD8[3]);
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD9 != null){
                if(isset($CMD9[1])){
                    return $this->get_table($CMD9[1]);
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD10 != null){
                return $this->get_database();
            }else if($CMD11 != null){
                return $this->get_status();
            }else if($CMD12 != null) {
                if(isset($CMD12[1]) && isset($CMD12[2])){
                    $VARS = $this->convert_to_var($CMD12[2]);
                    if(count($VARS) > 0){
                        return $this->add_column($CMD12[1], $VARS);
                    }else{
                        $this->close();
                        throw new Exception($INVALID_COMMAND);
                    }
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else if($CMD13 != null) {
                if(isset($CMD13[1]) && isset($CMD13[2])) {
                    $VARS = $this->convert_to_var($CMD13[2]);
                    if(count($VARS) > 0) {
                        return $this->drop_column($CMD13[1], $VARS);
                    }else{
                        $this->close();
                        throw new Exception($INVALID_COMMAND);
                    }
                }else{
                    $this->close();
                    throw new Exception($INVALID_COMMAND);
                }
            }else{
                $this->close();
                throw new Exception($NF_ERROR_MSG);
            }
        }else{
            $this->close();
            throw new Exception('ERROR: Missing ";" at end of command');
        }
    }

    /**
     * @param string $PART_ARR1
     * @param string|null $PART_ARR2
     * @return array
     * @throws Exception
     */
    protected function convert_to_var(string $PART_ARR1,string $PART_ARR2 = null):array{
        parse_str(str_replace([' ',','],['&','&'],$PART_ARR1),$RESULT);
        $RESULT_TMP = [];
        foreach ($RESULT as $KEY => $ITEM){
            if($KEY[0] == '`' && $KEY[strlen($KEY)-1] == '`'){
                $RESULT_TMP[] = str_replace('`','',$KEY);
            }else{
                throw new Exception('Not valid vars');
            }
        }
        $RESULT = $RESULT_TMP;

        if($PART_ARR2 != null){
            parse_str(str_replace([' ',','],['&','&'],$PART_ARR2),$RESULT2);
            $RESULT_TMP = [];
            foreach ($RESULT2 as $KEY => $ITEM){
                if($KEY[0] == '`' && $KEY[strlen($KEY)-1] == '`'){
                    $RESULT_TMP[] = str_replace('`','',$KEY);
                }else{
                    throw new Exception('Not valid vars');
                }
            }
            $RESULT2 = $RESULT_TMP;
            $RESULT_TMP = [];
            if(count($RESULT) == count($RESULT2)){
                for($i = 0;$i < count($RESULT);$i++){
                    $RESULT_TMP[$RESULT[$i]] = $RESULT2[$i];
                }
            }else{
                throw new Exception('Not valid vars');
            }
            return $RESULT_TMP;
        }else{
            return $RESULT;
        }
    }
    /**
     * Close TCP/UDP connection.
     * Instance to local TCP/UDP server using Sockets.
     */
    public function close(){
        $this->INSTANCE_RAM->close();
    }
    protected function get_status():array{
        $OPERATION_EXECUTE = [
            'DB_DATA' => [
                'QUERY' => [
                    'ACTION' => 'GET_STATUS',
                    'DATA' => []
                ]
            ]
        ];
        return json_decode($this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)), true);
    }

    //TODO DATABASE
    protected function make_db(string $DB_NAME, string $DB_PASSWORD):bool{
        $OPERATION_EXECUTE = [
            'DB_DATA' => [
                'QUERY' => [
                    'ACTION' => 'MAKE_DB',
                    'DATA' => [
                        'DB_NAME' => $DB_NAME,
                        'DB_PASSWORD' => $DB_PASSWORD,
                    ]
                ]
            ]
        ];
        return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
    }
    protected function drop_db(string $DB_NAME):bool{
        $OPERATION_EXECUTE = [
            'DB_DATA' => [
                'QUERY' => [
                    'ACTION' => 'DROP_DB',
                    'DATA' => [
                        'DB_NAME' => $DB_NAME,
                    ]
                ]
            ]
        ];
        return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
    }

    //TODO TABLE

    /**
     * @param string $table_name
     * @param array $columns
     * @param string $primary_key
     * @param bool $auto_increment
     * @return bool
     * @throws Exception
     */
    protected function make_table(string $table_name, array $columns, string $primary_key, bool $auto_increment):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'QUERY' => [
                        'ACTION' => 'MAKE_TABLE',
                        'DATA' => [
                            'NAME' => $table_name,
                            'COLUMNS' => $columns,
                            'PRIMARY_KEY' => $primary_key,
                            'AUTO_INCREMENT' => $auto_increment
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    protected function drop_table(string $name):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'QUERY' => [
                        'ACTION' => 'DROP_TABLE',
                        'DATA' => [
                            'NAME' => $name
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    //TODO COLUMN

    /**
     * @param string $table
     * @param array $vars
     * @return bool
     * @throws Exception
     */
    protected function add_column(string $table, array $vars):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'ADD_COLUMN',
                        'DATA' => [
                            'COLUMNS' => $vars
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $table
     * @param array $vars
     * @return bool
     * @throws Exception
     */
    protected function drop_column(string $table, array $vars):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'DROP_COLUMN',
                        'DATA' => [
                            'COLUMNS' => $vars
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    //TODO ROW

    /**
     * @param string $table
     * @param array $row_values
     * @return bool
     * @throws Exception
     */
    protected function make_row(string $table, array $row_values):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'MAKE_ROW',
                        'DATA' => [
                            'ROW_VALUES' => $row_values
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $table
     * @param string $column_by
     * @param string $row_by
     * @return bool
     * @throws Exception
     */
    protected function drop_row(string $table, string $column_by, string $row_by):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'DROP_ROW',
                        'DATA' => [
                            'COLUMN_BY' => $column_by,
                            'ROW_BY' => $row_by
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $table
     * @param array $row_values
     * @param string $column_by
     * @param string $row_by
     * @return bool
     * @throws Exception
     */
    protected function set_row(string $table, array $row_values, string $column_by, string $row_by):bool{
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'SET_ROW',
                        'DATA' => [
                            'ROW_VALUES' => $row_values,
                            'COLUMN_BY' => $column_by,
                            'ROW_BY' => $row_by
                        ]
                    ]
                ]
            ];
            return $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true)) == 'true';
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $table
     * @param string $column_by
     * @param string $row_by
     * @return mixed|null
     * @throws Exception
     */
    protected function get_row(string $table, string $column_by, string $row_by){
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'GET_ROW',
                        'DATA' => [
                            'COLUMN_BY' => $column_by,
                            'ROW_BY' => $row_by
                        ]
                    ]
                ]
            ];
            $result = $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true));
            $result = $result == 'null' ? null:json_decode($result,true);
            return $result;
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $table
     * @return mixed|null
     * @throws Exception
     */
    protected function get_table(string $table){
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'TABLE_NAME' => $table,
                    'QUERY' => [
                        'ACTION' => 'GET_TABLE',
                        'DATA' => []
                    ]
                ]
            ];
            $result = $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true));
            $result = $result == 'null' ? null:json_decode($result,true);
            return $result;
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    protected function get_database(){
        if($this->IS_CONNECTED){
            $OPERATION_EXECUTE = [
                'DB_DATA' => [
                    'DB_NAME' => $this->DB_NAME,
                    'DB_PASSWORD' => $this->DB_PASSWORD,
                    'QUERY' => [
                        'ACTION' => 'GET_DATABASE',
                        'DATA' => []
                    ]
                ]
            ];
            $result = $this->send_req_with_response(json_encode($OPERATION_EXECUTE, true));
            $result = $result == 'null' ? null:json_decode($result,true);
            return $result;
        }else{
            throw new Exception('Connect before execute query');
        }
    }

    /**
     * @param string $OPERATION_EXECUTE Operation to execute.
     * @return string Return result of JDB Core.
     * @throws Exception
     */
    protected function send_req_with_response(string $OPERATION_EXECUTE):string{
        $curr_id = $this -> generateRandomString(64);
        $this->INSTANCE_RAM -> add('JDB_REQ_' . $curr_id, $OPERATION_EXECUTE);
        //$start = microtime(true);
        $start_time = time();
        while($this->INSTANCE_RAM -> get('JDB_RESULT_' . $curr_id) == ''){
            if((time() - $start_time) > $this->TIMEOUT){
                throw new Exception('Error when connecting to JDBI CORE: ERROR_CONN');
            }
        }
        //echo (int)((microtime(true) - $start) * 1000) . 'ms ADD' .PHP_EOL;
        $DATA_RES = $this->INSTANCE_RAM -> get('JDB_RESULT_' . $curr_id);
        $this->INSTANCE_RAM -> delete('JDB_RESULT_' . $curr_id);
        return $DATA_RES;
    }
    protected function generateRandomString($length = 10):string{
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
