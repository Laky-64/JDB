<?php
namespace laky64\database;
use Memcache;

class CustomMemcache {
    protected Memcache $INSTANCE;
    function __construct(){
        $this->INSTANCE = new Memcache();
    }
    public function connect($host, $port){
        return $this->INSTANCE->connect($host, $port);
    }
    public function close(){
        return $this->INSTANCE->close();
    }
    public function add($key, $var){
        $RESULT = $this->INSTANCE->set($key, $var);
        if($RESULT){
            $LIST_KEYS = json_decode($this->INSTANCE->get('MEMCACHE_KEYS'),true);
            $LIST_KEYS[$key] = true;
            $this->INSTANCE->set('MEMCACHE_KEYS', json_encode($LIST_KEYS, true));
        }
        return $RESULT;
    }
    public function delete($key){
        $RESULT = $this->INSTANCE->delete($key);
        $LIST_KEYS = @json_decode($this->INSTANCE->get('MEMCACHE_KEYS'),true);
        unset($LIST_KEYS[$key]);
        $this->INSTANCE->set('MEMCACHE_KEYS', json_encode($LIST_KEYS, true));
        return $RESULT;
    }
    public function getAllKeys(){
        $return_d = json_decode($this->INSTANCE->get('MEMCACHE_KEYS'),true);
        $return_d = $return_d == null ? []:$return_d;
        $return_tmp = [];
        foreach ($return_d as $key=>$item){
            $return_tmp[] = $key;
        }
        return $return_tmp;
    }
    public function get($key){
        return $this->INSTANCE->get($key);
    }
}