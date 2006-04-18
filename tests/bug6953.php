<?php

/*

Bug is in retreiving last row of a joined query where the result key/vals are not known.
*/

// load the config.
require_once 'testdb.php';

// create some test data..
 
class testdo1 extends DB_DataObject {
	var $__table = 'testdo1';
	 
    function createDB()
    {
        
        $this->query('DROP TABLE IF EXISTS testdo1');
        $this->query("CREATE TABLE testdo1 (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              testdo_id int(11) NOT NULL,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )");
        
    }
    function insertData($id)
    {
        $x = DB_DataObject::factory('testdo1');
        $x->setFrom(array(
            'username' => 'x'.rand(),
            'password' => 'y'.rand(),
            'testdo_id' => $id,
        ));
        $x->insert();
    }
    function createJoin()
    {
        $this->_join = ' LEFT JOIN testdo ON testdo1.testdo_id = testdo.id ';
        
    
    }
    
}

class testdo extends DB_DataObject {
	var $__table = 'testdo';
	 
    function createDB()
    {
        $this->query('DROP TABLE IF EXISTS testdo');
        $this->query("CREATE TABLE testdo (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )");
       
    }
    
    function insertData()
    {
        $x = DB_DataObject::factory('testdo');
        $x->setFrom(array(
            'username' => 'x'.rand(),
            'password' => 'y'.rand(),
        ));
       return $x->insert();
         
    }
}

$x = DB_DataObject::factory('testdo');
$x->createDB();
$id = $x->insertData();
$x = DB_DataObject::factory('testdo1');
$x->createDB();
$x->insertData($id);

$x = DB_DataObject::factory('testdo');
$id = $x->insertData();
$x = DB_DataObject::factory('testdo1');
$x->insertData($id);


$x = DB_DataObject::factory('testdo1');
$x->createJoin();
$x->selectAs();
$x->selectAdd('testdo.username as testdo_username');
$x->find();
while ($x->fetch()) {
    print_r($x->toArray());
}





    