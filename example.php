<?php
    header('Content-Type: text/plain');
    include('JDB.php');
    //1234 is password for example and you can change
    $DB_CLASS = new \Overdark\sys\database\Database('1234');
    
    //Make Table return false if already exist, you need to add default value in Array format
    $DB_CLASS->make_table('TABLE_NAME', 'ROW_NAME', ['VAL1' => '12345', 'VAL2' => ['12345', '67890']]);
    
    //Read Row, return null if not exist
    print_r($DB_CLASS->get_values('TABLE_NAME', 'ROW_NAME'));
    
    //Make alternative index row name's, you need set {TABLE NAME, NEW NAME, OLD NAME}
    $DB_CLASS->make_index('TABLE_NAME', 'R_NAME', 'ROW_NAME');
    
    //Now you can read from this index
    print_r($DB_CLASS->get_values('TABLE_NAME', 'ROW_NAME'));
    
    //Or from this
    print_r($DB_CLASS->get_values('TABLE_NAME', 'R_NAME'));
    
    //For delete table true if is deleted
    $DB_CLASS->drop_table('TABLE_NAME', 'ROW_NAME');
    
    //For set value's
    $DB_CLASS->set_value('TABLE_NAME', 'ROW_NAME', 'VAL2', ['ABCDEF', 'GHILMN']);
    
    //Get auto-increment index
    echo $DB_CLASS->get_auto_increment('TABLE_NAME');
    
    //Get all row index
    print_r($DB_CLASS->get_tables_index('TABLE_NAME'));
    
?>
