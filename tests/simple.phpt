--TEST--
DB::DataObject test
--SKIPIF--
<?php
// define('DB_DATAOBJECT_NO_OVERLOAD',true);  

if (!require(dirname(__FILE__)."/../DataObject.php")) print "skip"; ?>
--FILE--
<?php // -*- C++ -*-

error_reporting(E_ALL);

// Test for: DB::parseDSN()
include_once dirname(__FILE__)."/../DataObject.php";
include_once 'PEAR.php';

$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);
$options['database'] = 'mysql://@localhost/test';
$options['debug_force_updates'] = TRUE;
$options['proxy'] = 'full';
$options['class_prefix'] = 'MyProject_DataObject_';
 
DB_DataObject::debugLevel(1);
// create a record




class test extends DB_DataObject {
	var $__table = 'test';
	 
    function doTests() {
        $this->createDB();
        $this->test1();
    }
    
    function createDB() {
        $this->query('DROP TABLE IF EXISTS test');
        $this->query('DROP TABLE IF EXISTS test2');
        $this->query('DROP TABLE IF EXISTS testproxy');
        $this->query('DROP TABLE IF EXISTS testproxy2');
        $this->query('DROP TABLE IF EXISTS testproxy2_seq');
       
        $this->query(
            "CREATE TABLE test (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )"); 
	
	// table 2 = manual sequences.
        $this->query(
            "CREATE TABLE test2 (
              id int(11) NOT NULL PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )");     
        $this->query(
            "CREATE TABLE testproxy (
              id int(11) NOT NULL  auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )");     
        $this->query(
            "CREATE TABLE testproxy2 (
              id int(11) NOT NULL PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            ) TYPE = InnoDB");     
         $this->query(
             'CREATE TABLE testproxy2_seq
                (id INTEGER UNSIGNED NOT NULL, PRIMARY KEY(id)) 
                TYPE = InnoDB');
        
         $this->query("INSERT INTO testproxy2_seq VALUES(0)");
    }
    
    function test1() {
       	echo "\n\n\n******create database' \n";
        $this->createRecordWithName('test');
        $this->dumpTest(); 
        $t = new test;
        //$t->id = 1;
	
       	echo "\n\n\n******delete everything with test and 'username' \n";
        $t->name = 'test';
        $t->username = 'username';
        $t->delete();
        $this->dumpTest(); 
	
        echo "\n\n\n***** update everything with username to firstname = 'fred' *\n";
        $this->createRecordWithName('test');
        $t = new test;
        $t->whereAdd("username = 'username'");
        $t->firstname='fred';
        $t->update(TRUE);
        $this->dumpTest(); 
	

        echo "\n\n\n****** now update based on key\n";
        $t= new test;
        $t->get(2);
        $t->firstname='brian';
        $t->update();
        $this->dumpTest();  
	
        echo "\n\n\n****** now update using changed items only\n";
        $t= new test;
        $t->get(2);
        $copy = $t;
        $t->firstname='jones';
        $t->update($copy);
        $this->dumpTest();  
        echo "\n\n\n****** now update using changed items only\n";	

        print_r($t->toArray('user[%s]'));

        echo "\n\n\n****** limited queries 1\n";
        $t= new test;

        $t->limit(1);
        $t->find();
        $t->fetch();
	
	
        echo "\n\n\n****** limited queries 1,1\n";
        $t= new test;

        $t->limit(1,1);
        $t->find();
        $t->fetch(); 
	
        echo "\n\n\n****** to Array on empty result\n";
        print_r($t->toArray('user[%s]'));
	

        echo "\n\n\n******get and delete an object key\n";
        $t = new test;
        $t->get(2);
        $t->delete();
        
        
        echo "\n\n\n******changing database stuff.\n";
        
        
        
        $t->query('BEGIN');
        $t->username = 'xxx';
        $t->insert();
        $t->query('ROLLBACK');
        
        
        $this->dumpTest('testproxy2');
        $t->query('BEGIN');
        $t->username = 'yyy';
        $t->insert();
        
        
        $t->query('COMMIT');
        
        
         // uncommitted.. 
        $this->dumpTest('testproxy2');
        
        
        $t->username = 'qqqqqq';
        $t->insert();
        
       
          
      
        
        echo "\n\n\n******sequences.\n";
            
        $t = new test2;
    
        $t->username = 'yyyy';
        $id = $t->insert();
        echo "\nRET: $id\n";
        
        
        $t->dumpTest('test2');
        
        $t = DB_DataObject::factory('testproxy');
        print_R($t);
        $t = DB_DataObject::factory('testproxy2');
        print_r($t->table());
        
        
        
        
        //bug #532
       
        $item = DB_DataObject::factory('testproxy'); 
        $item->id = 0; //id is the key with auto_increment flag on
        $newid = $item->insert();
        print_r($newid);
        $item->id = 0; //id is the key with auto_increment flag on
        $newid = $item->insert();
        print_r($newid);
        
        
        
        // bug #547
        $this->query('DROP TABLE IF EXISTS `page_module`');
        
        $this->query("
            CREATE TABLE `page_module` (
                `page_id` mediumint(8) unsigned NOT NULL default '0',
                `module_id` mediumint(8) unsigned NOT NULL default '0',
                `place` tinyint(3) unsigned NOT NULL default '0',
                `title` varchar(50) NOT NULL default '',
                `position` varchar(10) NOT NULL default 'top',
                PRIMARY KEY  (`page_id`,`module_id`)
            ) TYPE=MyISAM;");
        
        
        $page_module= DB_DataObject::Factory('page_module');
        
        // we should guess better.. but this is a kludgy fix.
        //$page_module->sequenceKey(false,false);
        $page_module->page_id=1;
        $page_module->module_id=1;
        $page_module->position='top';
        $page_module->insert();
        
        
        
        // type casting...
        $this->query('DROP TABLE IF EXISTS  typetest');
        $this->query("
            CREATE TABLE typetest (
                id int(11) NOT NULL auto_increment PRIMARY KEY,
                a_date date default '',
                a_time time NOT NULL default '',
                a_datetime  datetime default '',
                b_date  datetime default '',
                ts timestamp
            ) TYPE=MyISAM;");
            
        $x = DB_DataObject::factory('typetest');
        print_r($x->table());
        $x->seta_date(strtotime('1 jan 2003')); // 
        $x->seta_time('12pm');
        $x->seta_datetime(strtotime('1am yesterday'));
        $x->setb_date('null'); // 
        print_R($x);
        $id = $x->insert();
        $x = DB_DataObject::factory('typetest');
        $x->get($id);
        $x->setb_date(strtotime('12/1/1960'));
        $x->update();
        echo "TIMESTAMP = ".$x->getTs('%d/%m/%Y %H:%M:%S') . "\n";
        print_r($x);
        
        
        
        
        print_r(DB_DataObject::databaseStructure('test'));
        
        $this->postgresTest();
        
        
    }
    
    
    function postgresTest() {
    
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        //$options['schema_location'] = dirname(__FILE__);
        $options['database'] = 'pgsql://@localhost/test';
        $options['debug_force_updates'] = TRUE;
        $options['proxy'] = 'full';
        $options['class_prefix'] = 'MyProject_DataObject_';
        $options['sequence_seqtest'] = 'id:response_response_id_seq';
        
        
        $x  = new DB_DataObject;
        $x->query("DROP SEQUENCE response_response_id_seq");
        $x->query("DROP TABLE seqtest");
        
        
        $r = $x->query("CREATE SEQUENCE response_response_id_seq INCREMENT 1 START 1");
        $r = $x->query("
            CREATE TABLE seqtest (
                 id INT NOT NULL UNIQUE   default nextval( 'response_response_id_seq' ),
                xxx varchar(32)
            )");
            
            
        $x = DB_DataObject::factory('seqtest');
        $x->xxx = "Fred";
        var_dump($x->insert()); // will return id (based on response_response_id_seq)
        $x = DB_DataObject::factory('seqtest');
        $x->xxx = "Blogs";
        $options['ignore_sequence_keys'] = 'ALL';
        var_dump($x->insert()); // will not return anything!!!!
        
        unset($options['ignore_sequence_keys']);
        unset($options['sequence_seqtest']);
        $x = DB_DataObject::factory('seqtest');
        
        $x->xxx = "Jones";
        
        $x->sequenceKey('id',true,'response_response_id_seq');
        $options['ignore_sequence_keys'] = 'ALL';
        var_dump($x->insert());
        
        
    }
    
    
    
    
    
    function createRecordWithName($name) {
        $t = new test;
        $t->name = $name;
        $t->username = 'username';
        $r= $t->insert(); 
        echo "INSERT got $r\n";
    }
    
    function dumpTest($table = 'test') {
        $t = DB_DataObject::Factory($table);
        $t->find();
        if (!$t->N)  {
            echo "NO RESULTS!\n";
            return;
        }
        while ($t->fetch()) {
           $this->debugPrint($t);
        }
    }
    
    function debugPrint($t) {
      
        foreach(get_object_vars($t) as $k=>$v) {
            if ($k{0}== '_') {
                unset($t->$k);
            }
        }
        print_r($t);
    }
    
    
}


class test2 extends test { 
    var $__table = 'test2';
	function sequenceKey() {
		return array('id',false);
	}
}

class myproject_dataobject_testproxy2 extends db_dataobject { 
    var $__table = 'testproxy2';
	function sequenceKey() {
		return array('id',false);
	}
}





$t = new test;
$t->doTests();


?>
--GET--
--POST--
--EXPECT--
