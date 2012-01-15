--TEST--
DB::DataObject test
--SKIPIF--
<?php
//define('DB_DATAOBJECT_NO_OVERLOAD',true);  



if (empty($_SERVER['MYSQL_TEST_USER'])) {
   echo "
         so basic usage is:
         mysqladmin create test -uroot;
         export MYSQL_TEST_USER=root;export MYSQL_TEST_DB=test; pear run-tests
    ";

    die("SKIP enviorment variable MYSQL_TEST_USER no set");
}

?>
--FILE--
<?php // -*- C++ -*-

// Test for: DB::parseDSN()
ini_set('include_path', __DIR__.'/..' . PATH_SEPARATOR . ini_get('include_path'));

require_once 'DB/DataObject.php';
require_once 'PEAR.php';


 

$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);



$options['database'] = 'mysql://'.$_SERVER['MYSQL_TEST_USER']. ':' . 
                        (empty($_SERVER['MYSQL_TEST_PASSWD']) ? '' : $_SERVER['MYSQL_TEST_PASSWD']) .
                        '@' .  
                        ( empty($_SERVER['MYSQL_TEST_HOST']) ? 'localhost' :  $_SERVER['MYSQL_TEST_HOST']) .
                        '/'. $_SERVER['MYSQL_TEST_DB'] ;



$options['debug_force_updates'] = TRUE;
$options['proxy'] = 'full';
$options['class_prefix'] = 'MyProject_DataObject_';

print_R($options);
 
DB_DataObject::debugLevel(1);
// create a record

class Client extends DB_DataObject {
    var $__table = 'Client';
    function get($id) {
        return parent::get($id);
    }
}


class test extends DB_DataObject {
	var $__table = 'test';
	 
    function doTests() {
        $this->createDB();
        
        if (isset($_SERVER['argv'][1]) && method_exists($this,'test'.$_SERVER['argv'][1])) {
            $this->{'test'.$_SERVER['argv'][1]}();
            return;
        }
        for($i=0;$i<110;$i++) {
            if (method_exists($this,'test'.$i)) {
                $this->{'test'.$i}();
            }
        }
    }
    
    function createDB() {
        $this->query('DROP TABLE IF EXISTS test');
        $this->query('DROP TABLE IF EXISTS test2');
        $this->query('DROP TABLE IF EXISTS testproxy');
        $this->query('DROP TABLE IF EXISTS testproxy2');
        $this->query('DROP TABLE IF EXISTS testproxy2_seq');
        $this->query('DROP TABLE IF EXISTS testset');
       
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
        $this->query(
            "CREATE TABLE testset (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              gender SET('male','female', 'not quite sure')
            )"); 
            
            
      
         
    }
    
    function test10() {
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
    }
    function test11() { // bug #2928
        DB_DataObject::debugLevel(1);
        $t = new test;
        $t->query("SELECT * FROMs {$t->__table} where id < 5 ORDER BY id");
        print_r($t);
    
    }
    
	function test30() {
	
        echo "\n\n\n***** update everything with username to firstname = 'fred' *\n";
        $this->createRecordWithName('test');
        $t = new test;
        $t->whereAdd("username = 'username'");
        $t->firstname='fred';
        $t->update(TRUE);
        $this->dumpTest(); 
	
    }
	function test40() {

        echo "\n\n\n****** now update based on key\n";
        $t= new test;
        $t->get(2);
        $t->firstname='brian';
        $t->update();
        $this->dumpTest();  
    }
    function test41() {
        DB_DataObject::debuglevel(1);
        

        echo "\n\n\n****** toArray on only fetched keys.\n";
        $t= new test;
        $t->id = 2;
        $t->selectAdd();
        $t->selectAdd('firstname,lastname');
        $t->find(true);
        print_R($t->toArray());
    }
    
    function test42() { //serialize test bug #2739
        $t= new test;
        $t->id = 2;
        $t->find(true);
        $s = serialize($t);
        echo $s;
    }
    function test43()  // set toAray() bug #
    {
        $t = DB_DataObject::factory('testset');
        $t->name = "fred";
        $t->gender= 'not quite sure';
        $t->insert();
        $t = DB_DataObject::factory('testset');
        $t->find(true);
        print_R($t->toArray());
        
    }
    
    
	function test50() {
	
        echo "\n\n\n****** now update using changed items only\n";
        $t= new test;
        $t->get(2);
        $copy = $t;
        $t->firstname='jones';
        $t->update($copy);
        $this->dumpTest();  
        echo "\n\n\n****** now update using changed items only\n";	

        print_r($t->toArray('user[%s]'));
    }
	function test60() {

        echo "\n\n\n****** limited queries 1\n";
        $t= new test;

        $t->limit(1);
        $t->find();
        $t->fetch();
	
    }
	function test70() {
	
        echo "\n\n\n****** limited queries 1,1\n";
        $t= new test;

        $t->limit(1,1);
        $t->find();
        $t->fetch(); 
	
        echo "\n\n\n****** to Array on empty result\n";
        print_r($t->toArray('user[%s]'));
	
    }
	function test80() {

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
        
       
          
    }
    
    
    function test81() {
        // bug #992
        DB_DataObject::debugLevel(3);
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $options['dont_use_pear_sequences'] = true;
        $x  = new DB_DataObject;
        
        
        $x->query("DROP TABLE  IF EXISTS player_has_stats");
        
        
       
         $x->query("
            CREATE TABLE `player_has_stats` (
                  `player_id` int(10) unsigned NOT NULL default '0',
                  `deaths` int(10) unsigned NOT NULL default '0',
                  `kills` int(10) unsigned NOT NULL default '0',
                  PRIMARY KEY  (`player_id`)
                ) TYPE=MyISAM
                
             ");
        
         
        $player_has_stats = DB_DataObject::factory('player_has_stats');
        var_dump($player_has_stats->sequenceKey());
        print_R($player_has_stats );
        $player_has_stats-> player_id = 13;
        $player_has_stats-> insert();
    }
    
    


    function test82() {
    
       	echo "\n\n\nBug #2267 ******enum test  \n";
        
         
        $x  = new DB_DataObject;
         
        $x->query("DROP TABLE  IF EXISTS Client");
        
        
       
        $x->query("
            CREATE TABLE `Client` (
              `clientID` smallint(3) unsigned NOT NULL auto_increment,
              `client` varchar(100) NOT NULL default '',
              `type` enum('Ag\EAncia','Anunciante') NOT NULL default 'Ag\EAncia',
              `contact` varchar(100) NOT NULL default '',
              `email` varchar(60) NOT NULL default '',
              `signup` date NOT NULL default '0000-00-00',
              PRIMARY KEY  (`clientID`)
            )  
             ");
        $x->query("INSERT INTO `Client` VALUES (30, 'Internet', 'Anunciante', 'Leandro S.',
                'leandro@s.com', '2004-09-01');");
        $x->query("INSERT INTO `Client` VALUES (26, 'Grupos', 'Ag\EAncia', 'Gian',
            'gian@email.com', '2004-09-01');");
        $x = new Client;
        $x->get(30);
        print_r($x->toArray());
        
    }
    
    function test83() {     // bug #2116
        DB_DataObject::debugLevel(3);
        //$options = &PEAR::getStaticProperty('DB_DataObject','options');
        $x = new DB_DataObject;
        $x->query('DROP TABLE IF EXISTS resource');
        
        $x->query("CREATE TABLE `resource` (
              `id` int(11) NOT NULL auto_increment,
              `standard_number` varchar(20) NOT NULL default '',
              `callnumber` varchar(50) default NULL,
              `title` varchar(200) NOT NULL default '',
              `holding` varchar(100) default NULL,
              `status` tinyint(4) default NULL,
              `format` set('Electronic','Print','Microfilm') NOT NULL default
            'Electronic',
              `department_id` int(11) default NULL,
              `cost` double default NULL,
              `location` varchar(100) default NULL,
              `type_id` int(11) NOT NULL default '0',
              PRIMARY KEY  (`id`)
            )"
        );
        
        
        $resource = DB_DataObject::factory('resource');
        
        $resource->standard_number = '.NULL';
        $resource->title = 'ATLA Religion Database';
        $resource->format = 'Electronic';
        $resource->type_id = 2;
        $resource->insert();
        
        $resource = DB_DataObject::factory('resource');
        print_r($resource->keys());
        if ($resource->find()) {
            while ($resource->fetch()) {
                print_r($resource);
                print_r($resource->toArray());
            }
        }
        
        
    
    }
	function test90() {
  
        
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
        
        
    }
	function test91() {
    
        
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
        
        DB_DataObject::debugLevel(5);
        $page_module= DB_DataObject::Factory('page_module');
        
        // we should guess better.. but this is a kludgy fix.
        //$page_module->sequenceKey(false,false);
        $page_module->page_id=1;
        $page_module->module_id=1;
        $page_module->position='top';
        $page_module->insert();
        
        
        $page_module= DB_DataObject::Factory('page_module');
        $page_module->page_id=1;
        $page_module->module_id=1;
        $page_module->find(true);
        $page_module->position='bottom';
        
        $page_module->update();
        
        
        
    }
    function test92_disabled() {
        return;    
        
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
        if (!defined('DB_DATAOBJECT_NO_OVERLOAD')) {
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
        }
        print_r($x);
    }
    function test93_disabled() {
        
            // bug #753
        DB_DataObject::debugLevel(0);
        $org= DB_DataObject::factory('test');
        set_time_limit(0);
        ini_set('memory_limit','32M');
        $p = posix_getpid();
        $r = 'xxxx';
        for($i = 0; $i < 10000; $i++) {
            
            $org->name =$r;
            //$rslt = $org->query("INSERT INTO test (name) VALUES ('$r')");
            $rslt = $org->insert();
            if (!($i % 1000)) {
                echo "$i:".strlen(serialize($GLOBALS['_DB_DATAOBJECT']))."\n";
                //print_r($GLOBALS['_PEAR_error_handler_stack']);
                echo `cat /proc/$p/status | grep VmData`;
                //print_r($org);
            }
        }
        $org= DB_DataObject::factory('test');
        $org->find();
        $i=0;
        while($org->fetch()) {
            $i++;
            
            if (!($i % 1000)) {
                echo "$i:\n";
                echo `cat /proc/$p/status | grep VmData`;
            }
            
        }
        DB_DataObject::debugLevel(1);
            
        print_r(DB_DataObject::databaseStructure('test'));
     
    
    }
    
    
    function test94() {
    
        // bug #2116
        DB_DataObject::debugLevel(1);
        $s = DB_DataObject::factory('testset');
        $s->name = 'fred';
        $s->gender = 'male';
        $s->insert();
        $s = DB_DataObject::factory('testset');
        $s->find(true);
        print_r($s->toArray());
        
        
    
    
    
    
    }
    
    
    
    
    
    
    
    
    
    
    
    /* POSTGRES TESTS */
    
    function test100() {

    
    
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
                xxx varchar(32),
                price double precision
            )");
        
        //DB_DataObject::debugLevel(0);
        $x = DB_DataObject::factory('seqtest');
        print_r($x->table());
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
        
        
        // bug #970
        
        
        
        $options['ignore_sequence_keys'] = false;
        DB_DataObject::debugLevel(3);
        
        $x  = new DB_DataObject;
        $x->query("DROP SEQUENCE items_seq");
        $x->query("DROP TABLE itemtest");
        
        
        $r = $x->query("CREATE SEQUENCE items_seq INCREMENT 1 START 1");
        $r = $x->query("
            create table itemtest (
                id int not null primary key default nextval('items_seq'),
                word varchar(100) not null constraint word_unique unique
             )");
        $x = DB_DataObject::factory('itemtest');
          
        //print_r($x);
        print_r($x->table());
        print_r($x->keys());
        print_r($x->sequenceKey());
        $x->word = 'test';
        $x->insert();
        
        $x = DB_DataObject::factory('itemtest');
        $x->sequenceKey('id',true,'items_seq');
        print_r($x->sequenceKey());
         $x->word = 'test2';
        echo "insert ". $x->insert() . "\n";
       
       
        // bug #2519 - 
        $x  = new DB_DataObject;
        $x->query("DROP SEQUENCE bool_seq");
        $x->query("DROP TABLE booltest");
        
        
        $r = $x->query("CREATE SEQUENCE bool_seq INCREMENT 1 START 1");
        $r = $x->query("
            create table booltest (
                id int not null primary key default nextval('items_seq'),
                bo_test boolean default 'f',
                bo_int int default 0
             )");
        
        $x = DB_DataObject::factory('booltest');
        $x->bo_test = true;
        $x->bo_int = true;
        $x->find();
        
        $x->insert();
        
        
        
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
--EXPECTF--
Array
(
    [database] => %s
    [debug_force_updates] => 1
    [proxy] => full
    [class_prefix] => MyProject_DataObject_
)
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : DROP TABLE IF EXISTS test
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : DROP TABLE IF EXISTS test2
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : DROP TABLE IF EXISTS testproxy
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : DROP TABLE IF EXISTS testproxy2
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : DROP TABLE IF EXISTS testproxy2_seq
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : DROP TABLE IF EXISTS testset
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE test (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE test2 (
              id int(11) NOT NULL PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE testproxy (
              id int(11) NOT NULL  auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE testproxy2 (
              id int(11) NOT NULL PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            ) TYPE = InnoDB
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE testproxy2_seq
                (id INTEGER UNSIGNED NOT NULL, PRIMARY KEY(id)) 
                TYPE = InnoDB
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : INSERT INTO testproxy2_seq VALUES(0)
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : CREATE TABLE testset (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              gender SET('male','female', 'not quite sure')
            )
test   : query       : QUERY DONE IN  %f seconds



******create database' 
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
test   : QUERY       : INSERT INTO test (name , username ) VALUES ('test' , 'username' ) 
test   : query       : QUERY DONE IN  %f seconds
INSERT got 1
test   : FACTORY       : FAILED TO Autoload  .test - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
skip lm? - not setMyProject_DataObject_Test   : find       : 
MyProject_DataObject_Test   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 

MyProject_DataObject_Test   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test   : find       : CHECK autofetchd 
MyProject_DataObject_Test   : find       : DONE
MyProject_DataObject_Test   : FETCH       : a:6:{s:2:"id";s:1:"1";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:0:"";s:8:"lastname";s:0:"";}
MyProject_DataObject_Test Object
(
    [id] => 1
    [name] => test
    [username] => username
    [password] => 
    [firstname] => 
    [lastname] => 
    [N] => 1
)

Notice: Undefined property: MyProject_DataObject_Test::$_DB_resultid in /home/alan/git/DB_DataObject/trunk/DB/DataObject.php on line 583
MyProject_DataObject_Test   : 0       : fetched on object after fetch completed (no results found)



******delete everything with test and 'username' 
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : DELETE FROM test  WHERE (  test.name  = 'test' )  AND (  test.username  = 'username' )
test   : query       : QUERY DONE IN  %f seconds
test   : 1       : Clearing Cache for test
MyProject_DataObject_Test   : find       : 
MyProject_DataObject_Test   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 

MyProject_DataObject_Test   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test   : find       : CHECK autofetchd 
MyProject_DataObject_Test   : find       : DONE
NO RESULTS!
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : SELECT * FROMs test where id < 5 ORDER BY id
test   : Query Error       : [db_error: message="DB Error: syntax error" code=-2 mode=return level=notice prefix="" info="SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]"]
test   : ERROR       : DB_Error Object
(
    [error_message_prefix] => 
    [mode] => 1
    [level] => 1024
    [code] => -2
    [message] => DB Error: syntax error
    [userinfo] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
    [backtrace] => Array
        (
            [0] => Array
                (
                    [file] => /usr/share/php/DB.php
                    [line] => 966
                    [function] => PEAR_Error
                    [class] => PEAR_Error
                    [type] => ->
                    [args] => Array
                        (
                            [0] => DB Error: syntax error
                            [1] => -2
                            [2] => 1
                            [3] => 1024
                            [4] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                        )

                )

            [1] => Array
                (
                    [file] => /usr/share/php/PEAR.php
                    [line] => 531
                    [function] => DB_Error
                    [class] => DB_Error
                    [object] => DB_Error Object
 *RECURSION*
                    [type] => ->
                    [args] => Array
                        (
                            [0] => -2
                            [1] => 1
                            [2] => 1024
                            [3] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                        )

                )

            [2] => Array
                (
                    [file] => /usr/share/php/DB/common.php
                    [line] => 1908
                    [function] => raiseError
                    [class] => PEAR
                    [object] => DB_mysql Object
                        (
                            [phptype] => mysql
                            [dbsyntax] => mysql
                            [features] => Array
                                (
                                    [limit] => alter
                                    [new_link] => 4.2.0
                                    [numrows] => 1
                                    [pconnect] => 1
                                    [prepare] => 
                                    [ssl] => 
                                    [transactions] => 1
                                )

                            [errorcode_map] => Array
                                (
                                    [1004] => -15
                                    [1005] => -15
                                    [1006] => -15
                                    [1007] => -5
                                    [1008] => -17
                                    [1022] => -5
                                    [1044] => -26
                                    [1046] => -14
                                    [1048] => -3
                                    [1049] => -27
                                    [1050] => -5
                                    [1051] => -18
                                    [1054] => -19
                                    [1061] => -5
                                    [1062] => -5
                                    [1064] => -2
                                    [1091] => -4
                                    [1100] => -21
                                    [1136] => -22
                                    [1142] => -26
                                    [1146] => -18
                                    [1216] => -3
                                    [1217] => -3
                                    [1356] => -13
                                    [1451] => -3
                                    [1452] => -3
                                )

                            [connection] => Resource id #10
                            [dsn] => Array
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [username] => root
                                    [password] => 
                                    [protocol] => tcp
                                    [hostspec] => localhost
                                    [port] => 
                                    [socket] => 
                                    [database] => test
                                )

                            [autocommit] => 1
                            [transaction_opcount] => 0
                            [_db] => test
                            [fetchmode] => 1
                            [fetchmode_object_class] => stdClass
                            [was_connected] => 1
                            [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                            [options] => Array
                                (
                                    [result_buffering] => 500
                                    [persistent] => 
                                    [ssl] => 
                                    [debug] => 0
                                    [seqname_format] => %s_seq
                                    [autofree] => 
                                    [portability] => 0
                                    [optimize] => performance
                                )

                            [last_parameters] => Array
                                (
                                )

                            [prepare_tokens] => Array
                                (
                                )

                            [prepare_types] => Array
                                (
                                )

                            [prepared_queries] => Array
                                (
                                )

                            [_last_query_manip] => 
                            [_next_query_manip] => 
                            [_debug] => 
                            [_default_error_mode] => 
                            [_default_error_options] => 
                            [_default_error_handler] => 
                            [_error_class] => DB_Error
                            [_expected_errors] => Array
                                (
                                )

                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => 
                            [1] => -2
                            [2] => 
                            [3] => 
                            [4] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                            [5] => DB_Error
                            [6] => 1
                        )

                )

            [3] => Array
                (
                    [file] => /usr/share/php/DB/mysql.php
                    [line] => 898
                    [function] => raiseError
                    [class] => DB_common
                    [object] => DB_mysql Object
                        (
                            [phptype] => mysql
                            [dbsyntax] => mysql
                            [features] => Array
                                (
                                    [limit] => alter
                                    [new_link] => 4.2.0
                                    [numrows] => 1
                                    [pconnect] => 1
                                    [prepare] => 
                                    [ssl] => 
                                    [transactions] => 1
                                )

                            [errorcode_map] => Array
                                (
                                    [1004] => -15
                                    [1005] => -15
                                    [1006] => -15
                                    [1007] => -5
                                    [1008] => -17
                                    [1022] => -5
                                    [1044] => -26
                                    [1046] => -14
                                    [1048] => -3
                                    [1049] => -27
                                    [1050] => -5
                                    [1051] => -18
                                    [1054] => -19
                                    [1061] => -5
                                    [1062] => -5
                                    [1064] => -2
                                    [1091] => -4
                                    [1100] => -21
                                    [1136] => -22
                                    [1142] => -26
                                    [1146] => -18
                                    [1216] => -3
                                    [1217] => -3
                                    [1356] => -13
                                    [1451] => -3
                                    [1452] => -3
                                )

                            [connection] => Resource id #10
                            [dsn] => Array
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [username] => root
                                    [password] => 
                                    [protocol] => tcp
                                    [hostspec] => localhost
                                    [port] => 
                                    [socket] => 
                                    [database] => test
                                )

                            [autocommit] => 1
                            [transaction_opcount] => 0
                            [_db] => test
                            [fetchmode] => 1
                            [fetchmode_object_class] => stdClass
                            [was_connected] => 1
                            [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                            [options] => Array
                                (
                                    [result_buffering] => 500
                                    [persistent] => 
                                    [ssl] => 
                                    [debug] => 0
                                    [seqname_format] => %s_seq
                                    [autofree] => 
                                    [portability] => 0
                                    [optimize] => performance
                                )

                            [last_parameters] => Array
                                (
                                )

                            [prepare_tokens] => Array
                                (
                                )

                            [prepare_types] => Array
                                (
                                )

                            [prepared_queries] => Array
                                (
                                )

                            [_last_query_manip] => 
                            [_next_query_manip] => 
                            [_debug] => 
                            [_default_error_mode] => 
                            [_default_error_options] => 
                            [_default_error_handler] => 
                            [_error_class] => DB_Error
                            [_expected_errors] => Array
                                (
                                )

                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => -2
                            [1] => 
                            [2] => 
                            [3] => 
                            [4] => 1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1
                        )

                )

            [4] => Array
                (
                    [file] => /usr/share/php/DB/mysql.php
                    [line] => 327
                    [function] => mysqlRaiseError
                    [class] => DB_mysql
                    [object] => DB_mysql Object
                        (
                            [phptype] => mysql
                            [dbsyntax] => mysql
                            [features] => Array
                                (
                                    [limit] => alter
                                    [new_link] => 4.2.0
                                    [numrows] => 1
                                    [pconnect] => 1
                                    [prepare] => 
                                    [ssl] => 
                                    [transactions] => 1
                                )

                            [errorcode_map] => Array
                                (
                                    [1004] => -15
                                    [1005] => -15
                                    [1006] => -15
                                    [1007] => -5
                                    [1008] => -17
                                    [1022] => -5
                                    [1044] => -26
                                    [1046] => -14
                                    [1048] => -3
                                    [1049] => -27
                                    [1050] => -5
                                    [1051] => -18
                                    [1054] => -19
                                    [1061] => -5
                                    [1062] => -5
                                    [1064] => -2
                                    [1091] => -4
                                    [1100] => -21
                                    [1136] => -22
                                    [1142] => -26
                                    [1146] => -18
                                    [1216] => -3
                                    [1217] => -3
                                    [1356] => -13
                                    [1451] => -3
                                    [1452] => -3
                                )

                            [connection] => Resource id #10
                            [dsn] => Array
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [username] => root
                                    [password] => 
                                    [protocol] => tcp
                                    [hostspec] => localhost
                                    [port] => 
                                    [socket] => 
                                    [database] => test
                                )

                            [autocommit] => 1
                            [transaction_opcount] => 0
                            [_db] => test
                            [fetchmode] => 1
                            [fetchmode_object_class] => stdClass
                            [was_connected] => 1
                            [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                            [options] => Array
                                (
                                    [result_buffering] => 500
                                    [persistent] => 
                                    [ssl] => 
                                    [debug] => 0
                                    [seqname_format] => %s_seq
                                    [autofree] => 
                                    [portability] => 0
                                    [optimize] => performance
                                )

                            [last_parameters] => Array
                                (
                                )

                            [prepare_tokens] => Array
                                (
                                )

                            [prepare_types] => Array
                                (
                                )

                            [prepared_queries] => Array
                                (
                                )

                            [_last_query_manip] => 
                            [_next_query_manip] => 
                            [_debug] => 
                            [_default_error_mode] => 
                            [_default_error_options] => 
                            [_default_error_handler] => 
                            [_error_class] => DB_Error
                            [_expected_errors] => Array
                                (
                                )

                        )

                    [type] => ->
                    [args] => Array
                        (
                        )

                )

            [5] => Array
                (
                    [file] => /usr/share/php/DB/common.php
                    [line] => 1216
                    [function] => simpleQuery
                    [class] => DB_mysql
                    [object] => DB_mysql Object
                        (
                            [phptype] => mysql
                            [dbsyntax] => mysql
                            [features] => Array
                                (
                                    [limit] => alter
                                    [new_link] => 4.2.0
                                    [numrows] => 1
                                    [pconnect] => 1
                                    [prepare] => 
                                    [ssl] => 
                                    [transactions] => 1
                                )

                            [errorcode_map] => Array
                                (
                                    [1004] => -15
                                    [1005] => -15
                                    [1006] => -15
                                    [1007] => -5
                                    [1008] => -17
                                    [1022] => -5
                                    [1044] => -26
                                    [1046] => -14
                                    [1048] => -3
                                    [1049] => -27
                                    [1050] => -5
                                    [1051] => -18
                                    [1054] => -19
                                    [1061] => -5
                                    [1062] => -5
                                    [1064] => -2
                                    [1091] => -4
                                    [1100] => -21
                                    [1136] => -22
                                    [1142] => -26
                                    [1146] => -18
                                    [1216] => -3
                                    [1217] => -3
                                    [1356] => -13
                                    [1451] => -3
                                    [1452] => -3
                                )

                            [connection] => Resource id #10
                            [dsn] => Array
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [username] => root
                                    [password] => 
                                    [protocol] => tcp
                                    [hostspec] => localhost
                                    [port] => 
                                    [socket] => 
                                    [database] => test
                                )

                            [autocommit] => 1
                            [transaction_opcount] => 0
                            [_db] => test
                            [fetchmode] => 1
                            [fetchmode_object_class] => stdClass
                            [was_connected] => 1
                            [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                            [options] => Array
                                (
                                    [result_buffering] => 500
                                    [persistent] => 
                                    [ssl] => 
                                    [debug] => 0
                                    [seqname_format] => %s_seq
                                    [autofree] => 
                                    [portability] => 0
                                    [optimize] => performance
                                )

                            [last_parameters] => Array
                                (
                                )

                            [prepare_tokens] => Array
                                (
                                )

                            [prepare_types] => Array
                                (
                                )

                            [prepared_queries] => Array
                                (
                                )

                            [_last_query_manip] => 
                            [_next_query_manip] => 
                            [_debug] => 
                            [_default_error_mode] => 
                            [_default_error_options] => 
                            [_default_error_handler] => 
                            [_error_class] => DB_Error
                            [_expected_errors] => Array
                                (
                                )

                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => SELECT * FROMs test where id < 5 ORDER BY id
                        )

                )

            [6] => Array
                (
                    [file] => /home/alan/git/DB_DataObject/trunk/DB/DataObject.php
                    [line] => 2620
                    [function] => query
                    [class] => DB_common
                    [object] => DB_mysql Object
                        (
                            [phptype] => mysql
                            [dbsyntax] => mysql
                            [features] => Array
                                (
                                    [limit] => alter
                                    [new_link] => 4.2.0
                                    [numrows] => 1
                                    [pconnect] => 1
                                    [prepare] => 
                                    [ssl] => 
                                    [transactions] => 1
                                )

                            [errorcode_map] => Array
                                (
                                    [1004] => -15
                                    [1005] => -15
                                    [1006] => -15
                                    [1007] => -5
                                    [1008] => -17
                                    [1022] => -5
                                    [1044] => -26
                                    [1046] => -14
                                    [1048] => -3
                                    [1049] => -27
                                    [1050] => -5
                                    [1051] => -18
                                    [1054] => -19
                                    [1061] => -5
                                    [1062] => -5
                                    [1064] => -2
                                    [1091] => -4
                                    [1100] => -21
                                    [1136] => -22
                                    [1142] => -26
                                    [1146] => -18
                                    [1216] => -3
                                    [1217] => -3
                                    [1356] => -13
                                    [1451] => -3
                                    [1452] => -3
                                )

                            [connection] => Resource id #10
                            [dsn] => Array
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [username] => root
                                    [password] => 
                                    [protocol] => tcp
                                    [hostspec] => localhost
                                    [port] => 
                                    [socket] => 
                                    [database] => test
                                )

                            [autocommit] => 1
                            [transaction_opcount] => 0
                            [_db] => test
                            [fetchmode] => 1
                            [fetchmode_object_class] => stdClass
                            [was_connected] => 1
                            [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                            [options] => Array
                                (
                                    [result_buffering] => 500
                                    [persistent] => 
                                    [ssl] => 
                                    [debug] => 0
                                    [seqname_format] => %s_seq
                                    [autofree] => 
                                    [portability] => 0
                                    [optimize] => performance
                                )

                            [last_parameters] => Array
                                (
                                )

                            [prepare_tokens] => Array
                                (
                                )

                            [prepare_types] => Array
                                (
                                )

                            [prepared_queries] => Array
                                (
                                )

                            [_last_query_manip] => 
                            [_next_query_manip] => 
                            [_debug] => 
                            [_default_error_mode] => 
                            [_default_error_options] => 
                            [_default_error_handler] => 
                            [_error_class] => DB_Error
                            [_expected_errors] => Array
                                (
                                )

                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => SELECT * FROMs test where id < 5 ORDER BY id
                        )

                )

            [7] => Array
                (
                    [file] => /home/alan/git/DB_DataObject/trunk/DB/DataObject.php
                    [line] => 1781
                    [function] => _query
                    [class] => DB_DataObject
                    [object] => test Object
                        (
                            [__table] => test
                            [_DB_DataObject_version] => 1.9.6
                            [N] => 
                            [_database_dsn] => 
                            [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                            [_database] => test
                            [_query] => Array
                                (
                                    [condition] => 
                                    [group_by] => 
                                    [order_by] => 
                                    [having] => 
                                    [limit_start] => 
                                    [limit_count] => 
                                    [data_select] => *
                                    [unions] => Array
                                        (
                                        )

                                )

                            [_DB_resultid] => 
                            [_resultFields] => 
                            [_link_loaded] => 
                            [_join] => 
                            [_lastError] => 
                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => SELECT * FROMs test where id < 5 ORDER BY id
                        )

                )

            [8] => Array
                (
                    [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                    [line] => 139
                    [function] => query
                    [class] => DB_DataObject
                    [object] => test Object
                        (
                            [__table] => test
                            [_DB_DataObject_version] => 1.9.6
                            [N] => 
                            [_database_dsn] => 
                            [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                            [_database] => test
                            [_query] => Array
                                (
                                    [condition] => 
                                    [group_by] => 
                                    [order_by] => 
                                    [having] => 
                                    [limit_start] => 
                                    [limit_count] => 
                                    [data_select] => *
                                    [unions] => Array
                                        (
                                        )

                                )

                            [_DB_resultid] => 
                            [_resultFields] => 
                            [_link_loaded] => 
                            [_join] => 
                            [_lastError] => 
                        )

                    [type] => ->
                    [args] => Array
                        (
                            [0] => SELECT * FROMs test where id < 5 ORDER BY id
                        )

                )

            [9] => Array
                (
                    [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                    [line] => 54
                    [function] => test11
                    [class] => test
                    [object] => test Object
                        (
                            [__table] => test
                            [_DB_DataObject_version] => 1.9.6
                            [N] => 0
                            [_database_dsn] => 
                            [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                            [_database] => test
                            [_query] => Array
                                (
                                    [condition] => 
                                    [group_by] => 
                                    [order_by] => 
                                    [having] => 
                                    [limit_start] => 
                                    [limit_count] => 
                                    [data_select] => *
                                    [unions] => Array
                                        (
                                        )

                                )

                            [_DB_resultid] => 
                            [_resultFields] => 
                            [_link_loaded] => 
                            [_join] => 
                            [_lastError] => 
                        )

                    [type] => ->
                    [args] => Array
                        (
                        )

                )

            [10] => Array
                (
                    [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                    [line] => 721
                    [function] => doTests
                    [class] => test
                    [object] => test Object
                        (
                            [__table] => test
                            [_DB_DataObject_version] => 1.9.6
                            [N] => 0
                            [_database_dsn] => 
                            [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                            [_database] => test
                            [_query] => Array
                                (
                                    [condition] => 
                                    [group_by] => 
                                    [order_by] => 
                                    [having] => 
                                    [limit_start] => 
                                    [limit_count] => 
                                    [data_select] => *
                                    [unions] => Array
                                        (
                                        )

                                )

                            [_DB_resultid] => 
                            [_resultFields] => 
                            [_link_loaded] => 
                            [_join] => 
                            [_lastError] => 
                        )

                    [type] => ->
                    [args] => Array
                        (
                        )

                )

        )

    [callback] => 
)

test Object
(
    [__table] => test
    [_DB_DataObject_version] => 1.9.6
    [N] => 
    [_database_dsn] => 
    [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
    [_database] => test
    [_query] => Array
        (
            [condition] => 
            [group_by] => 
            [order_by] => 
            [having] => 
            [limit_start] => 
            [limit_count] => 
            [data_select] => *
            [unions] => Array
                (
                )

        )

    [_DB_resultid] => 
    [_resultFields] => 
    [_link_loaded] => 
    [_join] => 
    [_lastError] => DB_Error Object
        (
            [error_message_prefix] => 
            [mode] => 1
            [level] => 1024
            [code] => -2
            [message] => DB Error: syntax error
            [userinfo] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
            [backtrace] => Array
                (
                    [0] => Array
                        (
                            [file] => /usr/share/php/DB.php
                            [line] => 966
                            [function] => PEAR_Error
                            [class] => PEAR_Error
                            [type] => ->
                            [args] => Array
                                (
                                    [0] => DB Error: syntax error
                                    [1] => -2
                                    [2] => 1
                                    [3] => 1024
                                    [4] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                                )

                        )

                    [1] => Array
                        (
                            [file] => /usr/share/php/PEAR.php
                            [line] => 531
                            [function] => DB_Error
                            [class] => DB_Error
                            [object] => DB_Error Object
 *RECURSION*
                            [type] => ->
                            [args] => Array
                                (
                                    [0] => -2
                                    [1] => 1
                                    [2] => 1024
                                    [3] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                                )

                        )

                    [2] => Array
                        (
                            [file] => /usr/share/php/DB/common.php
                            [line] => 1908
                            [function] => raiseError
                            [class] => PEAR
                            [object] => DB_mysql Object
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [features] => Array
                                        (
                                            [limit] => alter
                                            [new_link] => 4.2.0
                                            [numrows] => 1
                                            [pconnect] => 1
                                            [prepare] => 
                                            [ssl] => 
                                            [transactions] => 1
                                        )

                                    [errorcode_map] => Array
                                        (
                                            [1004] => -15
                                            [1005] => -15
                                            [1006] => -15
                                            [1007] => -5
                                            [1008] => -17
                                            [1022] => -5
                                            [1044] => -26
                                            [1046] => -14
                                            [1048] => -3
                                            [1049] => -27
                                            [1050] => -5
                                            [1051] => -18
                                            [1054] => -19
                                            [1061] => -5
                                            [1062] => -5
                                            [1064] => -2
                                            [1091] => -4
                                            [1100] => -21
                                            [1136] => -22
                                            [1142] => -26
                                            [1146] => -18
                                            [1216] => -3
                                            [1217] => -3
                                            [1356] => -13
                                            [1451] => -3
                                            [1452] => -3
                                        )

                                    [connection] => Resource id #10
                                    [dsn] => Array
                                        (
                                            [phptype] => mysql
                                            [dbsyntax] => mysql
                                            [username] => root
                                            [password] => 
                                            [protocol] => tcp
                                            [hostspec] => localhost
                                            [port] => 
                                            [socket] => 
                                            [database] => test
                                        )

                                    [autocommit] => 1
                                    [transaction_opcount] => 0
                                    [_db] => test
                                    [fetchmode] => 1
                                    [fetchmode_object_class] => stdClass
                                    [was_connected] => 1
                                    [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                                    [options] => Array
                                        (
                                            [result_buffering] => 500
                                            [persistent] => 
                                            [ssl] => 
                                            [debug] => 0
                                            [seqname_format] => %s_seq
                                            [autofree] => 
                                            [portability] => 0
                                            [optimize] => performance
                                        )

                                    [last_parameters] => Array
                                        (
                                        )

                                    [prepare_tokens] => Array
                                        (
                                        )

                                    [prepare_types] => Array
                                        (
                                        )

                                    [prepared_queries] => Array
                                        (
                                        )

                                    [_last_query_manip] => 
                                    [_next_query_manip] => 
                                    [_debug] => 
                                    [_default_error_mode] => 
                                    [_default_error_options] => 
                                    [_default_error_handler] => 
                                    [_error_class] => DB_Error
                                    [_expected_errors] => Array
                                        (
                                        )

                                )

                            [type] => ->
                            [args] => Array
                                (
                                    [0] => 
                                    [1] => -2
                                    [2] => 
                                    [3] => 
                                    [4] => SELECT * FROMs test where id < 5 ORDER BY id [nativecode=1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1]
                                    [5] => DB_Error
                                    [6] => 1
                                )

                        )

                    [3] => Array
                        (
                            [file] => /usr/share/php/DB/mysql.php
                            [line] => 898
                            [function] => raiseError
                            [class] => DB_common
                            [object] => DB_mysql Object
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [features] => Array
                                        (
                                            [limit] => alter
                                            [new_link] => 4.2.0
                                            [numrows] => 1
                                            [pconnect] => 1
                                            [prepare] => 
                                            [ssl] => 
                                            [transactions] => 1
                                        )

                                    [errorcode_map] => Array
                                        (
                                            [1004] => -15
                                            [1005] => -15
                                            [1006] => -15
                                            [1007] => -5
                                            [1008] => -17
                                            [1022] => -5
                                            [1044] => -26
                                            [1046] => -14
                                            [1048] => -3
                                            [1049] => -27
                                            [1050] => -5
                                            [1051] => -18
                                            [1054] => -19
                                            [1061] => -5
                                            [1062] => -5
                                            [1064] => -2
                                            [1091] => -4
                                            [1100] => -21
                                            [1136] => -22
                                            [1142] => -26
                                            [1146] => -18
                                            [1216] => -3
                                            [1217] => -3
                                            [1356] => -13
                                            [1451] => -3
                                            [1452] => -3
                                        )

                                    [connection] => Resource id #10
                                    [dsn] => Array
                                        (
                                            [phptype] => mysql
                                            [dbsyntax] => mysql
                                            [username] => root
                                            [password] => 
                                            [protocol] => tcp
                                            [hostspec] => localhost
                                            [port] => 
                                            [socket] => 
                                            [database] => test
                                        )

                                    [autocommit] => 1
                                    [transaction_opcount] => 0
                                    [_db] => test
                                    [fetchmode] => 1
                                    [fetchmode_object_class] => stdClass
                                    [was_connected] => 1
                                    [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                                    [options] => Array
                                        (
                                            [result_buffering] => 500
                                            [persistent] => 
                                            [ssl] => 
                                            [debug] => 0
                                            [seqname_format] => %s_seq
                                            [autofree] => 
                                            [portability] => 0
                                            [optimize] => performance
                                        )

                                    [last_parameters] => Array
                                        (
                                        )

                                    [prepare_tokens] => Array
                                        (
                                        )

                                    [prepare_types] => Array
                                        (
                                        )

                                    [prepared_queries] => Array
                                        (
                                        )

                                    [_last_query_manip] => 
                                    [_next_query_manip] => 
                                    [_debug] => 
                                    [_default_error_mode] => 
                                    [_default_error_options] => 
                                    [_default_error_handler] => 
                                    [_error_class] => DB_Error
                                    [_expected_errors] => Array
                                        (
                                        )

                                )

                            [type] => ->
                            [args] => Array
                                (
                                    [0] => -2
                                    [1] => 
                                    [2] => 
                                    [3] => 
                                    [4] => 1064 ** You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FROMs test where id < 5 ORDER BY id' at line 1
                                )

                        )

                    [4] => Array
                        (
                            [file] => /usr/share/php/DB/mysql.php
                            [line] => 327
                            [function] => mysqlRaiseError
                            [class] => DB_mysql
                            [object] => DB_mysql Object
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [features] => Array
                                        (
                                            [limit] => alter
                                            [new_link] => 4.2.0
                                            [numrows] => 1
                                            [pconnect] => 1
                                            [prepare] => 
                                            [ssl] => 
                                            [transactions] => 1
                                        )

                                    [errorcode_map] => Array
                                        (
                                            [1004] => -15
                                            [1005] => -15
                                            [1006] => -15
                                            [1007] => -5
                                            [1008] => -17
                                            [1022] => -5
                                            [1044] => -26
                                            [1046] => -14
                                            [1048] => -3
                                            [1049] => -27
                                            [1050] => -5
                                            [1051] => -18
                                            [1054] => -19
                                            [1061] => -5
                                            [1062] => -5
                                            [1064] => -2
                                            [1091] => -4
                                            [1100] => -21
                                            [1136] => -22
                                            [1142] => -26
                                            [1146] => -18
                                            [1216] => -3
                                            [1217] => -3
                                            [1356] => -13
                                            [1451] => -3
                                            [1452] => -3
                                        )

                                    [connection] => Resource id #10
                                    [dsn] => Array
                                        (
                                            [phptype] => mysql
                                            [dbsyntax] => mysql
                                            [username] => root
                                            [password] => 
                                            [protocol] => tcp
                                            [hostspec] => localhost
                                            [port] => 
                                            [socket] => 
                                            [database] => test
                                        )

                                    [autocommit] => 1
                                    [transaction_opcount] => 0
                                    [_db] => test
                                    [fetchmode] => 1
                                    [fetchmode_object_class] => stdClass
                                    [was_connected] => 1
                                    [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                                    [options] => Array
                                        (
                                            [result_buffering] => 500
                                            [persistent] => 
                                            [ssl] => 
                                            [debug] => 0
                                            [seqname_format] => %s_seq
                                            [autofree] => 
                                            [portability] => 0
                                            [optimize] => performance
                                        )

                                    [last_parameters] => Array
                                        (
                                        )

                                    [prepare_tokens] => Array
                                        (
                                        )

                                    [prepare_types] => Array
                                        (
                                        )

                                    [prepared_queries] => Array
                                        (
                                        )

                                    [_last_query_manip] => 
                                    [_next_query_manip] => 
                                    [_debug] => 
                                    [_default_error_mode] => 
                                    [_default_error_options] => 
                                    [_default_error_handler] => 
                                    [_error_class] => DB_Error
                                    [_expected_errors] => Array
                                        (
                                        )

                                )

                            [type] => ->
                            [args] => Array
                                (
                                )

                        )

                    [5] => Array
                        (
                            [file] => /usr/share/php/DB/common.php
                            [line] => 1216
                            [function] => simpleQuery
                            [class] => DB_mysql
                            [object] => DB_mysql Object
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [features] => Array
                                        (
                                            [limit] => alter
                                            [new_link] => 4.2.0
                                            [numrows] => 1
                                            [pconnect] => 1
                                            [prepare] => 
                                            [ssl] => 
                                            [transactions] => 1
                                        )

                                    [errorcode_map] => Array
                                        (
                                            [1004] => -15
                                            [1005] => -15
                                            [1006] => -15
                                            [1007] => -5
                                            [1008] => -17
                                            [1022] => -5
                                            [1044] => -26
                                            [1046] => -14
                                            [1048] => -3
                                            [1049] => -27
                                            [1050] => -5
                                            [1051] => -18
                                            [1054] => -19
                                            [1061] => -5
                                            [1062] => -5
                                            [1064] => -2
                                            [1091] => -4
                                            [1100] => -21
                                            [1136] => -22
                                            [1142] => -26
                                            [1146] => -18
                                            [1216] => -3
                                            [1217] => -3
                                            [1356] => -13
                                            [1451] => -3
                                            [1452] => -3
                                        )

                                    [connection] => Resource id #10
                                    [dsn] => Array
                                        (
                                            [phptype] => mysql
                                            [dbsyntax] => mysql
                                            [username] => root
                                            [password] => 
                                            [protocol] => tcp
                                            [hostspec] => localhost
                                            [port] => 
                                            [socket] => 
                                            [database] => test
                                        )

                                    [autocommit] => 1
                                    [transaction_opcount] => 0
                                    [_db] => test
                                    [fetchmode] => 1
                                    [fetchmode_object_class] => stdClass
                                    [was_connected] => 1
                                    [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                                    [options] => Array
                                        (
                                            [result_buffering] => 500
                                            [persistent] => 
                                            [ssl] => 
                                            [debug] => 0
                                            [seqname_format] => %s_seq
                                            [autofree] => 
                                            [portability] => 0
                                            [optimize] => performance
                                        )

                                    [last_parameters] => Array
                                        (
                                        )

                                    [prepare_tokens] => Array
                                        (
                                        )

                                    [prepare_types] => Array
                                        (
                                        )

                                    [prepared_queries] => Array
                                        (
                                        )

                                    [_last_query_manip] => 
                                    [_next_query_manip] => 
                                    [_debug] => 
                                    [_default_error_mode] => 
                                    [_default_error_options] => 
                                    [_default_error_handler] => 
                                    [_error_class] => DB_Error
                                    [_expected_errors] => Array
                                        (
                                        )

                                )

                            [type] => ->
                            [args] => Array
                                (
                                    [0] => SELECT * FROMs test where id < 5 ORDER BY id
                                )

                        )

                    [6] => Array
                        (
                            [file] => /home/alan/git/DB_DataObject/trunk/DB/DataObject.php
                            [line] => 2620
                            [function] => query
                            [class] => DB_common
                            [object] => DB_mysql Object
                                (
                                    [phptype] => mysql
                                    [dbsyntax] => mysql
                                    [features] => Array
                                        (
                                            [limit] => alter
                                            [new_link] => 4.2.0
                                            [numrows] => 1
                                            [pconnect] => 1
                                            [prepare] => 
                                            [ssl] => 
                                            [transactions] => 1
                                        )

                                    [errorcode_map] => Array
                                        (
                                            [1004] => -15
                                            [1005] => -15
                                            [1006] => -15
                                            [1007] => -5
                                            [1008] => -17
                                            [1022] => -5
                                            [1044] => -26
                                            [1046] => -14
                                            [1048] => -3
                                            [1049] => -27
                                            [1050] => -5
                                            [1051] => -18
                                            [1054] => -19
                                            [1061] => -5
                                            [1062] => -5
                                            [1064] => -2
                                            [1091] => -4
                                            [1100] => -21
                                            [1136] => -22
                                            [1142] => -26
                                            [1146] => -18
                                            [1216] => -3
                                            [1217] => -3
                                            [1356] => -13
                                            [1451] => -3
                                            [1452] => -3
                                        )

                                    [connection] => Resource id #10
                                    [dsn] => Array
                                        (
                                            [phptype] => mysql
                                            [dbsyntax] => mysql
                                            [username] => root
                                            [password] => 
                                            [protocol] => tcp
                                            [hostspec] => localhost
                                            [port] => 
                                            [socket] => 
                                            [database] => test
                                        )

                                    [autocommit] => 1
                                    [transaction_opcount] => 0
                                    [_db] => test
                                    [fetchmode] => 1
                                    [fetchmode_object_class] => stdClass
                                    [was_connected] => 1
                                    [last_query] => SELECT * FROMs test where id < 5 ORDER BY id
                                    [options] => Array
                                        (
                                            [result_buffering] => 500
                                            [persistent] => 
                                            [ssl] => 
                                            [debug] => 0
                                            [seqname_format] => %s_seq
                                            [autofree] => 
                                            [portability] => 0
                                            [optimize] => performance
                                        )

                                    [last_parameters] => Array
                                        (
                                        )

                                    [prepare_tokens] => Array
                                        (
                                        )

                                    [prepare_types] => Array
                                        (
                                        )

                                    [prepared_queries] => Array
                                        (
                                        )

                                    [_last_query_manip] => 
                                    [_next_query_manip] => 
                                    [_debug] => 
                                    [_default_error_mode] => 
                                    [_default_error_options] => 
                                    [_default_error_handler] => 
                                    [_error_class] => DB_Error
                                    [_expected_errors] => Array
                                        (
                                        )

                                )

                            [type] => ->
                            [args] => Array
                                (
                                    [0] => SELECT * FROMs test where id < 5 ORDER BY id
                                )

                        )

                    [7] => Array
                        (
                            [file] => /home/alan/git/DB_DataObject/trunk/DB/DataObject.php
                            [line] => 1781
                            [function] => _query
                            [class] => DB_DataObject
                            [object] => test Object
 *RECURSION*
                            [type] => ->
                            [args] => Array
                                (
                                    [0] => SELECT * FROMs test where id < 5 ORDER BY id
                                )

                        )

                    [8] => Array
                        (
                            [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                            [line] => 139
                            [function] => query
                            [class] => DB_DataObject
                            [object] => test Object
 *RECURSION*
                            [type] => ->
                            [args] => Array
                                (
                                    [0] => SELECT * FROMs test where id < 5 ORDER BY id
                                )

                        )

                    [9] => Array
                        (
                            [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                            [line] => 54
                            [function] => test11
                            [class] => test
                            [object] => test Object
                                (
                                    [__table] => test
                                    [_DB_DataObject_version] => 1.9.6
                                    [N] => 0
                                    [_database_dsn] => 
                                    [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                                    [_database] => test
                                    [_query] => Array
                                        (
                                            [condition] => 
                                            [group_by] => 
                                            [order_by] => 
                                            [having] => 
                                            [limit_start] => 
                                            [limit_count] => 
                                            [data_select] => *
                                            [unions] => Array
                                                (
                                                )

                                        )

                                    [_DB_resultid] => 
                                    [_resultFields] => 
                                    [_link_loaded] => 
                                    [_join] => 
                                    [_lastError] => 
                                )

                            [type] => ->
                            [args] => Array
                                (
                                )

                        )

                    [10] => Array
                        (
                            [file] => /home/alan/git/DB_DataObject/trunk/tests/simple.php
                            [line] => 721
                            [function] => doTests
                            [class] => test
                            [object] => test Object
                                (
                                    [__table] => test
                                    [_DB_DataObject_version] => 1.9.6
                                    [N] => 0
                                    [_database_dsn] => 
                                    [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
                                    [_database] => test
                                    [_query] => Array
                                        (
                                            [condition] => 
                                            [group_by] => 
                                            [order_by] => 
                                            [having] => 
                                            [limit_start] => 
                                            [limit_count] => 
                                            [data_select] => *
                                            [unions] => Array
                                                (
                                                )

                                        )

                                    [_DB_resultid] => 
                                    [_resultFields] => 
                                    [_link_loaded] => 
                                    [_join] => 
                                    [_lastError] => 
                                )

                            [type] => ->
                            [args] => Array
                                (
                                )

                        )

                )

            [callback] => 
        )

)



***** update everything with username to firstname = 'fred' *
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : INSERT INTO test (name , username ) VALUES ('test' , 'username' ) 
test   : query       : QUERY DONE IN  %f seconds
INSERT got 2
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : 3       : got keys as a:1:{i:0;s:2:"id";}
test   : QUERY       : UPDATE  test  SET firstname = 'fred'   WHERE ( username = 'username' )  
test   : query       : QUERY DONE IN  %f seconds
test   : 1       : Clearing Cache for test
MyProject_DataObject_Test   : find       : 
MyProject_DataObject_Test   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 

MyProject_DataObject_Test   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test   : find       : CHECK autofetchd 
MyProject_DataObject_Test   : find       : DONE
MyProject_DataObject_Test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:4:"fred";s:8:"lastname";s:0:"";}
MyProject_DataObject_Test Object
(
    [id] => 2
    [name] => test
    [username] => username
    [password] => 
    [firstname] => fred
    [lastname] => 
    [N] => 1
)

Notice: Undefined property: MyProject_DataObject_Test::$_DB_resultid in /home/alan/git/DB_DataObject/trunk/DB/DataObject.php on line 583
MyProject_DataObject_Test   : 0       : fetched on object after fetch completed (no results found)



****** now update based on key
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : GET       : id 2 Array
(
    [0] => id
)

test   : find       : 1
test   : QUERY       : SELECT * 
 FROM test 
 
 WHERE (  test.id = 2 )  
 
 
 

test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 1
test   : find       : ABOUT TO AUTOFETCH
test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:4:"fred";s:8:"lastname";s:0:"";}
test   : find       : DONE
test   : 3       : got keys as a:1:{i:0;s:2:"id";}
test   : QUERY       : UPDATE  test  SET name = 'test' , username = 'username' , password = '' , firstname = 'brian' , lastname = ''   WHERE (  test.id = 2 )  
test   : query       : QUERY DONE IN  %f seconds
test   : 1       : Clearing Cache for test
MyProject_DataObject_Test   : find       : 
MyProject_DataObject_Test   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 

MyProject_DataObject_Test   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test   : find       : CHECK autofetchd 
MyProject_DataObject_Test   : find       : DONE
MyProject_DataObject_Test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
MyProject_DataObject_Test Object
(
    [id] => 2
    [name] => test
    [username] => username
    [password] => 
    [firstname] => brian
    [lastname] => 
    [N] => 1
)

Notice: Undefined property: MyProject_DataObject_Test::$_DB_resultid in /home/alan/git/DB_DataObject/trunk/DB/DataObject.php on line 583
MyProject_DataObject_Test   : 0       : fetched on object after fetch completed (no results found)



****** toArray on only fetched keys.
test   : find       : 1
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : SELECT  firstname,lastname  
 FROM test 
 
 WHERE (  test.id = 2 )  
 
 
 

test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 1
test   : find       : ABOUT TO AUTOFETCH
test   : FETCH       : a:2:{s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
test   : find       : DONE
Array
(
    [firstname] => brian
    [lastname] => 
    [id] => 2
    [name] => 
    [username] => 
    [password] => 
)
test   : find       : 1
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : QUERY       : SELECT * 
 FROM test 
 
 WHERE (  test.id = 2 )  
 
 
 

test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 1
test   : find       : ABOUT TO AUTOFETCH
test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
test   : find       : DONE
O:4:"test":18:{s:7:"__table";s:4:"test";s:22:"_DB_DataObject_version";s:5:"1.9.6";s:1:"N";i:1;s:13:"_database_dsn";s:0:"";s:17:"_database_dsn_md5";s:32:"c82e24dc3e78d758eadd72ede9d541fc";s:9:"_database";s:4:"test";s:6:"_query";a:8:{s:9:"condition";s:0:"";s:8:"group_by";s:0:"";s:8:"order_by";s:0:"";s:6:"having";s:0:"";s:11:"limit_start";s:0:"";s:11:"limit_count";s:0:"";s:11:"data_select";s:1:"*";s:6:"unions";a:0:{}}s:12:"_DB_resultid";i:7;s:13:"_resultFields";b:0;s:12:"_link_loaded";b:0;s:5:"_join";s:0:"";s:10:"_lastError";b:0;s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}test   : FACTORY       : FAILED TO Autoload  .testset - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
skip lm? - not setMyProject_DataObject_Testset   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Testset   : QUERY       : INSERT INTO testset (name , gender ) VALUES ('fred' , 'not quite sure' ) 
MyProject_DataObject_Testset   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Testset   : find       : 1
MyProject_DataObject_Testset   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Testset   : QUERY       : SELECT * 
 FROM testset 
 
 
 
 
 

MyProject_DataObject_Testset   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Testset   : find       : CHECK autofetchd 1
MyProject_DataObject_Testset   : find       : ABOUT TO AUTOFETCH
MyProject_DataObject_Testset   : FETCH       : a:3:{s:2:"id";s:1:"1";s:4:"name";s:4:"fred";s:6:"gender";s:14:"not quite sure";}
MyProject_DataObject_Testset   : find       : DONE
Array
(
    [id] => 1
    [name] => fred
    [gender] => not quite sure
)



****** now update using changed items only
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : GET       : id 2 Array
(
    [0] => id
)

test   : find       : 1
test   : QUERY       : SELECT * 
 FROM test 
 
 WHERE (  test.id = 2 )  
 
 
 

test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 1
test   : find       : ABOUT TO AUTOFETCH
test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
test   : find       : DONE
test   : 3       : got keys as a:1:{i:0;s:2:"id";}
MyProject_DataObject_Test   : find       : 
MyProject_DataObject_Test   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 

MyProject_DataObject_Test   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test   : find       : CHECK autofetchd 
MyProject_DataObject_Test   : find       : DONE
MyProject_DataObject_Test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
MyProject_DataObject_Test Object
(
    [id] => 2
    [name] => test
    [username] => username
    [password] => 
    [firstname] => brian
    [lastname] => 
    [N] => 1
)

Notice: Undefined property: MyProject_DataObject_Test::$_DB_resultid in /home/alan/git/DB_DataObject/trunk/DB/DataObject.php on line 583
MyProject_DataObject_Test   : 0       : fetched on object after fetch completed (no results found)



****** now update using changed items only
Array
(
    [user[id]] => 2
    [user[name]] => test
    [user[username]] => username
    [user[password]] => 
    [user[firstname]] => jones
    [user[lastname]] => 
)



****** limited queries 1
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : find       : 
test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 
 LIMIT 0, 1
test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 
test   : find       : DONE
test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}



****** limited queries 1,1
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : find       : 
test   : QUERY       : SELECT * 
 FROM test 
 
 
 
 
 
 LIMIT 1, 1
test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 
test   : find       : DONE



****** to Array on empty result
Array
(
    [user[id]] => 
    [user[name]] => 
    [user[username]] => 
    [user[password]] => 
    [user[firstname]] => 
    [user[lastname]] => 
)



******get and delete an object key
test   : CONNECT       : Checking for database specific ini ('') : database_ in options
test   : GET       : id 2 Array
(
    [0] => id
)

test   : find       : 1
test   : QUERY       : SELECT * 
 FROM test 
 
 WHERE (  test.id = 2 )  
 
 
 

test   : query       : QUERY DONE IN  %f seconds
test   : find       : CHECK autofetchd 1
test   : find       : ABOUT TO AUTOFETCH
test   : FETCH       : a:6:{s:2:"id";s:1:"2";s:4:"name";s:4:"test";s:8:"username";s:8:"username";s:8:"password";s:0:"";s:9:"firstname";s:5:"brian";s:8:"lastname";s:0:"";}
test   : find       : DONE
test   : QUERY       : DELETE FROM test  WHERE (  test.id = 2 ) 
test   : query       : QUERY DONE IN  %f seconds
test   : 1       : Clearing Cache for test



******changing database stuff.
test   : QUERY       : BEGIN
test   : QUERY       : INSERT INTO test (name , username , password , firstname , lastname ) VALUES ('test' , 'xxx' , '' , 'brian' , '' ) 
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : ROLLBACK
myproject_dataobject_testproxy2   : find       : 
myproject_dataobject_testproxy2   : CONNECT       : Checking for database specific ini ('') : database_ in options
myproject_dataobject_testproxy2   : 1       : Loading Generator to fetch Schema
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
myproject_dataobject_testproxy2   : QUERY       : SELECT * 
 FROM testproxy2 
 
 
 
 
 

myproject_dataobject_testproxy2   : query       : QUERY DONE IN  %f seconds
myproject_dataobject_testproxy2   : find       : CHECK autofetchd 
myproject_dataobject_testproxy2   : find       : DONE
NO RESULTS!
test   : QUERY       : BEGIN
test   : QUERY       : INSERT INTO test (name , username , password , firstname , lastname ) VALUES ('test' , 'yyy' , '' , 'brian' , '' ) 
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : COMMIT
myproject_dataobject_testproxy2   : find       : 
myproject_dataobject_testproxy2   : CONNECT       : Checking for database specific ini ('') : database_ in options
myproject_dataobject_testproxy2   : QUERY       : SELECT * 
 FROM testproxy2 
 
 
 
 
 

myproject_dataobject_testproxy2   : query       : QUERY DONE IN  %f seconds
myproject_dataobject_testproxy2   : find       : CHECK autofetchd 
myproject_dataobject_testproxy2   : find       : DONE
NO RESULTS!
test   : QUERY       : INSERT INTO test (name , username , password , firstname , lastname ) VALUES ('test' , 'qqqqqq' , '' , 'brian' , '' ) 
test   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject   : QUERY       : DROP TABLE  IF EXISTS player_has_stats
DB_DataObject   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : QUERY       : 
            CREATE TABLE `player_has_stats` (
                  `player_id` int(10) unsigned NOT NULL default '0',
                  `deaths` int(10) unsigned NOT NULL default '0',
                  `kills` int(10) unsigned NOT NULL default '0',
                  PRIMARY KEY  (`player_id`)
                ) TYPE=MyISAM
                
             
DB_DataObject   : query       : QUERY DONE IN  %f seconds
test   : FACTORY       : FAILED TO Autoload  .player_has_stats - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/player_has_stats
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => player_has_stats
            [name] => player_id
            [type] => int
            [len] => 10
            [flags] => not_null primary_key unsigned
        )

    [1] => Array
        (
            [table] => player_has_stats
            [name] => deaths
            [type] => int
            [len] => 10
            [flags] => not_null unsigned
        )

    [2] => Array
        (
            [table] => player_has_stats
            [name] => kills
            [type] => int
            [len] => 10
            [flags] => not_null unsigned
        )

)

TABLE STRUCTURE FOR player_has_stats
Array
(
    [0] => stdClass Object
        (
            [table] => player_has_stats
            [name] => player_id
            [type] => int
            [len] => 10
            [flags] => not_null primary_key unsigned
        )

    [1] => stdClass Object
        (
            [table] => player_has_stats
            [name] => deaths
            [type] => int
            [len] => 10
            [flags] => not_null unsigned
        )

    [2] => stdClass Object
        (
            [table] => player_has_stats
            [name] => kills
            [type] => int
            [len] => 10
            [flags] => not_null unsigned
        )

)
Array
(
    [0] => dump for player_has_stats
    [1] => Array
        (
            [table] => Array
                (
                    [player_id] => 129
                    [deaths] => 129
                    [kills] => 129
                )

            [keys] => Array
                (
                    [player_id] => K
                )

        )

)
skip lm? - not setMyProject_DataObject_Player_has_stats   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Player_has_stats   : CONNECT       : USING CACHED CONNECTION
array(3) {
  [0]=>
  bool(false)
  [1]=>
  bool(false)
  [2]=>
  bool(false)
}
MyProject_DataObject_Player_has_stats Object
(
    [__table] => player_has_stats
    [player_id] => 
    [deaths] => 
    [kills] => 
    [_DB_DataObject_version] => 1.9.6
    [N] => 0
    [_database_dsn] => 
    [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
    [_database] => test
    [_query] => Array
        (
            [condition] => 
            [group_by] => 
            [order_by] => 
            [having] => 
            [limit_start] => 
            [limit_count] => 
            [data_select] => *
            [unions] => Array
                (
                )

        )

    [_DB_resultid] => 
    [_resultFields] => 
    [_link_loaded] => 
    [_join] => 
    [_lastError] => 
)
MyProject_DataObject_Player_has_stats   : QUERY       : INSERT INTO player_has_stats (player_id ) VALUES ( 13 ) 
MyProject_DataObject_Player_has_stats   : query       : QUERY DONE IN  %f seconds



Bug #2267 ******enum test  
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject   : QUERY       : DROP TABLE  IF EXISTS Client
DB_DataObject   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : QUERY       : 
            CREATE TABLE `Client` (
              `clientID` smallint(3) unsigned NOT NULL auto_increment,
              `client` varchar(100) NOT NULL default '',
              `type` enum('Ag\EAncia','Anunciante') NOT NULL default 'Ag\EAncia',
              `contact` varchar(100) NOT NULL default '',
              `email` varchar(60) NOT NULL default '',
              `signup` date NOT NULL default '0000-00-00',
              PRIMARY KEY  (`clientID`)
            )  
             
DB_DataObject   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : QUERY       : INSERT INTO `Client` VALUES (30, 'Internet', 'Anunciante', 'Leandro S.',
                'leandro@s.com', '2004-09-01');
DB_DataObject   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : QUERY       : INSERT INTO `Client` VALUES (26, 'Grupos', 'Ag\EAncia', 'Gian',
            'gian@email.com', '2004-09-01');
DB_DataObject   : query       : QUERY DONE IN  %f seconds
Client   : CONNECT       : Checking for database specific ini ('') : database_ in options
Client   : CONNECT       : USING CACHED CONNECTION
Client   : 1       : Loading Generator to fetch Schema
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/Client
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => Client
            [name] => clientID
            [type] => int
            [len] => 3
            [flags] => not_null primary_key unsigned auto_increment
        )

    [1] => Array
        (
            [table] => Client
            [name] => client
            [type] => string
            [len] => 100
            [flags] => not_null
        )

    [2] => Array
        (
            [table] => Client
            [name] => type
            [type] => string
            [len] => 10
            [flags] => not_null enum
        )

    [3] => Array
        (
            [table] => Client
            [name] => contact
            [type] => string
            [len] => 100
            [flags] => not_null
        )

    [4] => Array
        (
            [table] => Client
            [name] => email
            [type] => string
            [len] => 60
            [flags] => not_null
        )

    [5] => Array
        (
            [table] => Client
            [name] => signup
            [type] => date
            [len] => 10
            [flags] => not_null binary
        )

)

TABLE STRUCTURE FOR Client
Array
(
    [0] => stdClass Object
        (
            [table] => Client
            [name] => clientID
            [type] => int
            [len] => 3
            [flags] => not_null primary_key unsigned auto_increment
        )

    [1] => stdClass Object
        (
            [table] => Client
            [name] => client
            [type] => string
            [len] => 100
            [flags] => not_null
        )

    [2] => stdClass Object
        (
            [table] => Client
            [name] => type
            [type] => string
            [len] => 10
            [flags] => not_null enum
        )

    [3] => stdClass Object
        (
            [table] => Client
            [name] => contact
            [type] => string
            [len] => 100
            [flags] => not_null
        )

    [4] => stdClass Object
        (
            [table] => Client
            [name] => email
            [type] => string
            [len] => 60
            [flags] => not_null
        )

    [5] => stdClass Object
        (
            [table] => Client
            [name] => signup
            [type] => date
            [len] => 10
            [flags] => not_null binary
        )

)
Array
(
    [0] => dump for Client
    [1] => Array
        (
            [table] => Array
                (
                    [clientID] => 129
                    [client] => 130
                    [type] => 130
                    [contact] => 130
                    [email] => 130
                    [signup] => 134
                )

            [keys] => Array
                (
                    [clientID] => N
                )

        )

)
Client   : GET       : clientID 30 Array
(
    [0] => clientID
)

Client   : find       : 1
Client   : QUERY       : SELECT * 
 FROM Client 
 
 WHERE (  Client.clientID = 30 )  
 
 
 

Client   : query       : QUERY DONE IN  %f seconds
Client   : find       : CHECK autofetchd 1
Client   : find       : ABOUT TO AUTOFETCH
Client   : FETCH       : a:6:{s:8:"clientID";s:2:"30";s:6:"client";s:8:"Internet";s:4:"type";s:10:"Anunciante";s:7:"contact";s:10:"Leandro S.";s:5:"email";s:13:"leandro@s.com";s:6:"signup";s:10:"2004-09-01";}
Client   : fetchrow LINE       : clientID = 30
Client   : fetchrow LINE       : client = Internet
Client   : fetchrow LINE       : type = Anunciante
Client   : fetchrow LINE       : contact = Leandro S.
Client   : fetchrow LINE       : email = leandro@s.com
Client   : fetchrow LINE       : signup = 2004-09-01
Client   : fetchrow       : Client DONE
Client   : find       : DONE
Array
(
    [clientID] => 30
    [client] => Internet
    [type] => Anunciante
    [contact] => Leandro S.
    [email] => leandro@s.com
    [signup] => 2004-09-01
)
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject   : QUERY       : DROP TABLE IF EXISTS resource
DB_DataObject   : query       : QUERY DONE IN  %f seconds
DB_DataObject   : QUERY       : CREATE TABLE `resource` (
              `id` int(11) NOT NULL auto_increment,
              `standard_number` varchar(20) NOT NULL default '',
              `callnumber` varchar(50) default NULL,
              `title` varchar(200) NOT NULL default '',
              `holding` varchar(100) default NULL,
              `status` tinyint(4) default NULL,
              `format` set('Electronic','Print','Microfilm') NOT NULL default
            'Electronic',
              `department_id` int(11) default NULL,
              `cost` double default NULL,
              `location` varchar(100) default NULL,
              `type_id` int(11) NOT NULL default '0',
              PRIMARY KEY  (`id`)
            )
DB_DataObject   : query       : QUERY DONE IN  %f seconds
test   : FACTORY       : FAILED TO Autoload  .resource - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/resource
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => resource
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key auto_increment
        )

    [1] => Array
        (
            [table] => resource
            [name] => standard_number
            [type] => string
            [len] => 20
            [flags] => not_null
        )

    [2] => Array
        (
            [table] => resource
            [name] => callnumber
            [type] => string
            [len] => 50
            [flags] => 
        )

    [3] => Array
        (
            [table] => resource
            [name] => title
            [type] => string
            [len] => 200
            [flags] => not_null
        )

    [4] => Array
        (
            [table] => resource
            [name] => holding
            [type] => string
            [len] => 100
            [flags] => 
        )

    [5] => Array
        (
            [table] => resource
            [name] => status
            [type] => int
            [len] => 4
            [flags] => 
        )

    [6] => Array
        (
            [table] => resource
            [name] => format
            [type] => string
            [len] => 26
            [flags] => not_null set
        )

    [7] => Array
        (
            [table] => resource
            [name] => department_id
            [type] => int
            [len] => 11
            [flags] => 
        )

    [8] => Array
        (
            [table] => resource
            [name] => cost
            [type] => real
            [len] => 22
            [flags] => 
        )

    [9] => Array
        (
            [table] => resource
            [name] => location
            [type] => string
            [len] => 100
            [flags] => 
        )

    [10] => Array
        (
            [table] => resource
            [name] => type_id
            [type] => int
            [len] => 11
            [flags] => not_null
        )

)

TABLE STRUCTURE FOR resource
Array
(
    [0] => stdClass Object
        (
            [table] => resource
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key auto_increment
        )

    [1] => stdClass Object
        (
            [table] => resource
            [name] => standard_number
            [type] => string
            [len] => 20
            [flags] => not_null
        )

    [2] => stdClass Object
        (
            [table] => resource
            [name] => callnumber
            [type] => string
            [len] => 50
            [flags] => 
        )

    [3] => stdClass Object
        (
            [table] => resource
            [name] => title
            [type] => string
            [len] => 200
            [flags] => not_null
        )

    [4] => stdClass Object
        (
            [table] => resource
            [name] => holding
            [type] => string
            [len] => 100
            [flags] => 
        )

    [5] => stdClass Object
        (
            [table] => resource
            [name] => status
            [type] => int
            [len] => 4
            [flags] => 
        )

    [6] => stdClass Object
        (
            [table] => resource
            [name] => format
            [type] => string
            [len] => 26
            [flags] => not_null set
        )

    [7] => stdClass Object
        (
            [table] => resource
            [name] => department_id
            [type] => int
            [len] => 11
            [flags] => 
        )

    [8] => stdClass Object
        (
            [table] => resource
            [name] => cost
            [type] => real
            [len] => 22
            [flags] => 
        )

    [9] => stdClass Object
        (
            [table] => resource
            [name] => location
            [type] => string
            [len] => 100
            [flags] => 
        )

    [10] => stdClass Object
        (
            [table] => resource
            [name] => type_id
            [type] => int
            [len] => 11
            [flags] => not_null
        )

)
Array
(
    [0] => dump for resource
    [1] => Array
        (
            [table] => Array
                (
                    [id] => 129
                    [standard_number] => 130
                    [callnumber] => 2
                    [title] => 130
                    [holding] => 2
                    [status] => 1
                    [format] => 130
                    [department_id] => 1
                    [cost] => 1
                    [location] => 2
                    [type_id] => 129
                )

            [keys] => Array
                (
                    [id] => N
                )

        )

)
skip lm? - not setMyProject_DataObject_Resource   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Resource   : CONNECT       : USING CACHED CONNECTION
MyProject_DataObject_Resource   : QUERY       : INSERT INTO resource (standard_number , title , format , type_id ) VALUES ('.NULL' , 'ATLA Religion Database' , 'Electronic' ,  2 ) 
MyProject_DataObject_Resource   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Resource   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Resource   : CONNECT       : USING CACHED CONNECTION
Array
(
    [0] => id
)
MyProject_DataObject_Resource   : find       : 
MyProject_DataObject_Resource   : QUERY       : SELECT * 
 FROM resource 
 
 
 
 
 

MyProject_DataObject_Resource   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Resource   : find       : CHECK autofetchd 
MyProject_DataObject_Resource   : find       : DONE
MyProject_DataObject_Resource   : FETCH       : a:11:{s:2:"id";s:1:"1";s:15:"standard_number";s:5:".NULL";s:10:"callnumber";N;s:5:"title";s:22:"ATLA Religion Database";s:7:"holding";N;s:6:"status";N;s:6:"format";s:10:"Electronic";s:13:"department_id";N;s:4:"cost";N;s:8:"location";N;s:7:"type_id";s:1:"2";}
MyProject_DataObject_Resource   : fetchrow LINE       : id = 1
MyProject_DataObject_Resource   : fetchrow LINE       : standard_number = .NULL
MyProject_DataObject_Resource   : fetchrow LINE       : callnumber = 
MyProject_DataObject_Resource   : fetchrow LINE       : title = ATLA Religion Database
MyProject_DataObject_Resource   : fetchrow LINE       : holding = 
MyProject_DataObject_Resource   : fetchrow LINE       : status = 
MyProject_DataObject_Resource   : fetchrow LINE       : format = Electronic
MyProject_DataObject_Resource   : fetchrow LINE       : department_id = 
MyProject_DataObject_Resource   : fetchrow LINE       : cost = 
MyProject_DataObject_Resource   : fetchrow LINE       : location = 
MyProject_DataObject_Resource   : fetchrow LINE       : type_id = 2
MyProject_DataObject_Resource   : fetchrow       : resource DONE
MyProject_DataObject_Resource Object
(
    [__table] => resource
    [id] => 1
    [standard_number] => .NULL
    [callnumber] => 
    [title] => ATLA Religion Database
    [holding] => 
    [status] => 
    [format] => Electronic
    [department_id] => 
    [cost] => 
    [location] => 
    [type_id] => 2
    [_DB_DataObject_version] => 1.9.6
    [N] => 1
    [_database_dsn] => 
    [_database_dsn_md5] => c82e24dc3e78d758eadd72ede9d541fc
    [_database] => test
    [_query] => 
    [_DB_resultid] => 17
    [_resultFields] => 
    [_link_loaded] => 
    [_join] => 
    [_lastError] => 
)
Array
(
    [id] => 1
    [standard_number] => .NULL
    [callnumber] => 
    [title] => ATLA Religion Database
    [holding] => 
    [status] => 
    [format] => Electronic
    [department_id] => 
    [cost] => 
    [location] => 
    [type_id] => 2
)
MyProject_DataObject_Resource   : FETCH       : N;
MyProject_DataObject_Resource   : FETCH       : Last Data Fetch'ed after %f seconds



******sequences.
test2   : CONNECT       : Checking for database specific ini ('') : database_ in options
test2   : CONNECT       : USING CACHED CONNECTION
test2   : 1       : Loading Generator to fetch Schema
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/test2
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => test2
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key
        )

    [1] => Array
        (
            [table] => test2
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => Array
        (
            [table] => test2
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => Array
        (
            [table] => test2
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => Array
        (
            [table] => test2
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => Array
        (
            [table] => test2
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)

TABLE STRUCTURE FOR test2
Array
(
    [0] => stdClass Object
        (
            [table] => test2
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key
        )

    [1] => stdClass Object
        (
            [table] => test2
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => stdClass Object
        (
            [table] => test2
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => stdClass Object
        (
            [table] => test2
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => stdClass Object
        (
            [table] => test2
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => stdClass Object
        (
            [table] => test2
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)
Array
(
    [0] => dump for test2
    [1] => Array
        (
            [table] => Array
                (
                    [id] => 129
                    [name] => 130
                    [username] => 130
                    [password] => 130
                    [firstname] => 130
                    [lastname] => 130
                )

            [keys] => Array
                (
                    [id] => K
                )

        )

)
test2   : QUERY       : INSERT INTO test2 (id , username ) VALUES ( %d , 'yyyy' ) 
test2   : query       : QUERY DONE IN  %f seconds

RET: %d
test2   : FACTORY       : FAILED TO Autoload  .test2 - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/test2
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => test2
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key
        )

    [1] => Array
        (
            [table] => test2
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => Array
        (
            [table] => test2
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => Array
        (
            [table] => test2
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => Array
        (
            [table] => test2
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => Array
        (
            [table] => test2
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)

TABLE STRUCTURE FOR test2
Array
(
    [0] => stdClass Object
        (
            [table] => test2
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key
        )

    [1] => stdClass Object
        (
            [table] => test2
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => stdClass Object
        (
            [table] => test2
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => stdClass Object
        (
            [table] => test2
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => stdClass Object
        (
            [table] => test2
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => stdClass Object
        (
            [table] => test2
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)
Array
(
    [0] => dump for test2
    [1] => Array
        (
            [table] => Array
                (
                    [id] => 129
                    [name] => 130
                    [username] => 130
                    [password] => 130
                    [firstname] => 130
                    [lastname] => 130
                )

            [keys] => Array
                (
                    [id] => K
                )

        )

)
skip lm? - not setMyProject_DataObject_Test2   : find       : 
MyProject_DataObject_Test2   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Test2   : CONNECT       : USING CACHED CONNECTION
MyProject_DataObject_Test2   : QUERY       : SELECT * 
 FROM test2 
 
 
 
 
 

MyProject_DataObject_Test2   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Test2   : find       : CHECK autofetchd 
MyProject_DataObject_Test2   : find       : DONE
MyProject_DataObject_Test2   : FETCH       : %s
MyProject_DataObject_Test2   : fetchrow LINE       : id = %d
MyProject_DataObject_Test2   : fetchrow LINE       : name = 
MyProject_DataObject_Test2   : fetchrow LINE       : username = yyyy
MyProject_DataObject_Test2   : fetchrow LINE       : password = 
MyProject_DataObject_Test2   : fetchrow LINE       : firstname = 
MyProject_DataObject_Test2   : fetchrow LINE       : lastname = 
MyProject_DataObject_Test2   : fetchrow       : test2 DONE
MyProject_DataObject_Test2 Object
(
    [id] => %d
    [name] => 
    [username] => yyyy
    [password] => 
    [firstname] => 
    [lastname] => 
    [N] => 1
)

Notice: Undefined property: MyProject_DataObject_Test2::$_DB_resultid in /home/alan/git/DB_DataObject/trunk/DB/DataObject.php on line 583
MyProject_DataObject_Test2   : 0       : fetched on object after fetch completed (no results found)
test   : FACTORY       : FAILED TO Autoload  .testproxy - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/testproxy
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => testproxy
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key auto_increment
        )

    [1] => Array
        (
            [table] => testproxy
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => Array
        (
            [table] => testproxy
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => Array
        (
            [table] => testproxy
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => Array
        (
            [table] => testproxy
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => Array
        (
            [table] => testproxy
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)

TABLE STRUCTURE FOR testproxy
Array
(
    [0] => stdClass Object
        (
            [table] => testproxy
            [name] => id
            [type] => int
            [len] => 11
            [flags] => not_null primary_key auto_increment
        )

    [1] => stdClass Object
        (
            [table] => testproxy
            [name] => name
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [2] => stdClass Object
        (
            [table] => testproxy
            [name] => username
            [type] => string
            [len] => 32
            [flags] => not_null
        )

    [3] => stdClass Object
        (
            [table] => testproxy
            [name] => password
            [type] => string
            [len] => 13
            [flags] => not_null binary
        )

    [4] => stdClass Object
        (
            [table] => testproxy
            [name] => firstname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

    [5] => stdClass Object
        (
            [table] => testproxy
            [name] => lastname
            [type] => string
            [len] => 255
            [flags] => not_null
        )

)
Array
(
    [0] => dump for testproxy
    [1] => Array
        (
            [table] => Array
                (
                    [id] => 129
                    [name] => 130
                    [username] => 130
                    [password] => 130
                    [firstname] => 130
                    [lastname] => 130
                )

            [keys] => Array
                (
                    [id] => N
                )

        )

)
skip lm? - not setMyProject_DataObject_Testproxy Object
(
    [__table] => testproxy
    [id] => 
    [name] => 
    [username] => 
    [password] => 
    [firstname] => 
    [lastname] => 
    [_DB_DataObject_version] => 1.9.6
    [N] => 0
    [_database_dsn] => 
    [_database_dsn_md5] => 
    [_database] => 
    [_query] => Array
        (
            [condition] => 
            [group_by] => 
            [order_by] => 
            [having] => 
            [limit_start] => 
            [limit_count] => 
            [data_select] => *
            [unions] => Array
                (
                )

        )

    [_DB_resultid] => 
    [_resultFields] => 
    [_link_loaded] => 
    [_join] => 
    [_lastError] => 
)
myproject_dataobject_testproxy2   : CONNECT       : Checking for database specific ini ('') : database_ in options
myproject_dataobject_testproxy2   : CONNECT       : USING CACHED CONNECTION
Array
(
    [id] => 129
    [name] => 130
    [username] => 130
    [password] => 130
    [firstname] => 130
    [lastname] => 130
)
MyProject_DataObject_Testproxy   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Testproxy   : CONNECT       : USING CACHED CONNECTION
MyProject_DataObject_Testproxy   : QUERY       : INSERT INTO testproxy () VALUES () 
MyProject_DataObject_Testproxy   : query       : QUERY DONE IN  %f seconds
1MyProject_DataObject_Testproxy   : QUERY       : INSERT INTO testproxy () VALUES () 
MyProject_DataObject_Testproxy   : query       : QUERY DONE IN  %f seconds
2test   : QUERY       : DROP TABLE IF EXISTS `page_module`
test   : query       : QUERY DONE IN  %f seconds
test   : QUERY       : 
            CREATE TABLE `page_module` (
                `page_id` mediumint(8) unsigned NOT NULL default '0',
                `module_id` mediumint(8) unsigned NOT NULL default '0',
                `place` tinyint(3) unsigned NOT NULL default '0',
                `title` varchar(50) NOT NULL default '',
                `position` varchar(10) NOT NULL default 'top',
                PRIMARY KEY  (`page_id`,`module_id`)
            ) TYPE=MyISAM;
test   : query       : QUERY DONE IN  %f seconds
test   : FACTORY       : FAILED TO Autoload  .page_module - using proxy.
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : CONNECT       : Checking for database specific ini ('test') : database_test in options
DB_DataObject_Generator   : CONNECT       : USING CACHED CONNECTION
DB_DataObject_Generator   : fillTable       : getting def for test/page_module
DB_DataObject_Generator   : defs       : Array
(
    [0] => Array
        (
            [table] => page_module
            [name] => page_id
            [type] => int
            [len] => 8
            [flags] => not_null primary_key unsigned
        )

    [1] => Array
        (
            [table] => page_module
            [name] => module_id
            [type] => int
            [len] => 8
            [flags] => not_null primary_key unsigned
        )

    [2] => Array
        (
            [table] => page_module
            [name] => place
            [type] => int
            [len] => 3
            [flags] => not_null unsigned
        )

    [3] => Array
        (
            [table] => page_module
            [name] => title
            [type] => string
            [len] => 50
            [flags] => not_null
        )

    [4] => Array
        (
            [table] => page_module
            [name] => position
            [type] => string
            [len] => 10
            [flags] => not_null
        )

)

TABLE STRUCTURE FOR page_module
Array
(
    [0] => stdClass Object
        (
            [table] => page_module
            [name] => page_id
            [type] => int
            [len] => 8
            [flags] => not_null primary_key unsigned
        )

    [1] => stdClass Object
        (
            [table] => page_module
            [name] => module_id
            [type] => int
            [len] => 8
            [flags] => not_null primary_key unsigned
        )

    [2] => stdClass Object
        (
            [table] => page_module
            [name] => place
            [type] => int
            [len] => 3
            [flags] => not_null unsigned
        )

    [3] => stdClass Object
        (
            [table] => page_module
            [name] => title
            [type] => string
            [len] => 50
            [flags] => not_null
        )

    [4] => stdClass Object
        (
            [table] => page_module
            [name] => position
            [type] => string
            [len] => 10
            [flags] => not_null
        )

)
Array
(
    [0] => dump for page_module
    [1] => Array
        (
            [table] => Array
                (
                    [page_id] => 129
                    [module_id] => 129
                    [place] => 129
                    [title] => 130
                    [position] => 130
                )

            [keys] => Array
                (
                    [page_id] => K
                    [module_id] => K
                )

        )

)
skip lm? - not setMyProject_DataObject_Page_module   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Page_module   : CONNECT       : USING CACHED CONNECTION
MyProject_DataObject_Page_module   : QUERY       : INSERT INTO page_module (page_id , module_id , position ) VALUES ( 1 ,  1 , 'top' ) 
MyProject_DataObject_Page_module   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Page_module   : find       : 1
MyProject_DataObject_Page_module   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Page_module   : CONNECT       : USING CACHED CONNECTION
MyProject_DataObject_Page_module   : QUERY       : SELECT * 
 FROM page_module 
 
 WHERE (  page_module.page_id = 1 )  AND (  page_module.module_id = 1 ) 
 
 
 

MyProject_DataObject_Page_module   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Page_module   : RESULT       : O:9:"DB_result":11:{s:8:"autofree";b:0;s:3:"dbh";O:8:"DB_mysql":8:{s:10:"autocommit";b:1;s:8:"dbsyntax";s:5:"mysql";s:3:"dsn";a:9:{s:7:"phptype";s:5:"mysql";s:8:"dbsyntax";s:5:"mysql";s:8:"username";s:4:"root";s:8:"password";s:0:"";s:8:"protocol";s:3:"tcp";s:8:"hostspec";s:9:"localhost";s:4:"port";b:0;s:6:"socket";b:0;s:8:"database";s:4:"test";}s:8:"features";a:7:{s:5:"limit";s:5:"alter";s:8:"new_link";s:5:"4.2.0";s:7:"numrows";b:1;s:8:"pconnect";b:1;s:7:"prepare";b:0;s:3:"ssl";b:0;s:12:"transactions";b:1;}s:9:"fetchmode";i:1;s:22:"fetchmode_object_class";s:8:"stdClass";s:7:"options";a:8:{s:16:"result_buffering";i:500;s:10:"persistent";b:0;s:3:"ssl";b:0;s:5:"debug";i:0;s:14:"seqname_format";s:6:"%s_seq";s:8:"autofree";b:0;s:11:"portability";i:0;s:8:"optimize";s:11:"performance";}s:13:"was_connected";b:1;}s:9:"fetchmode";i:1;s:22:"fetchmode_object_class";s:8:"stdClass";s:11:"limit_count";N;s:10:"limit_from";N;s:10:"parameters";a:0:{}s:5:"query";s:110:"SELECT * 
 FROM page_module 
 
 WHERE (  page_module.page_id = 1 )  AND (  page_module.module_id = 1 ) 
 
 
 
";s:6:"result";i:0;s:11:"row_counter";N;s:9:"statement";N;}
MyProject_DataObject_Page_module   : find       : CHECK autofetchd 1
MyProject_DataObject_Page_module   : find       : ABOUT TO AUTOFETCH
MyProject_DataObject_Page_module   : FETCH       : a:5:{s:7:"page_id";s:1:"1";s:9:"module_id";s:1:"1";s:5:"place";s:1:"0";s:5:"title";s:0:"";s:8:"position";s:3:"top";}
MyProject_DataObject_Page_module   : fetchrow LINE       : page_id = 1
MyProject_DataObject_Page_module   : fetchrow LINE       : module_id = 1
MyProject_DataObject_Page_module   : fetchrow LINE       : place = 0
MyProject_DataObject_Page_module   : fetchrow LINE       : title = 
MyProject_DataObject_Page_module   : fetchrow LINE       : position = top
MyProject_DataObject_Page_module   : fetchrow       : page_module DONE
MyProject_DataObject_Page_module   : find       : DONE
MyProject_DataObject_Page_module   : 3       : got keys as a:2:{i:0;s:7:"page_id";i:1;s:9:"module_id";}
MyProject_DataObject_Page_module   : QUERY       : UPDATE  page_module  SET place = 0 , title = '' , position = 'bottom'   WHERE (  page_module.page_id = 1 )  AND (  page_module.module_id = 1 ) 
MyProject_DataObject_Page_module   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Page_module   : 1       : Clearing Cache for myproject_dataobject_page_module
MyProject_DataObject_Testset   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Testset   : QUERY       : INSERT INTO testset (name , gender ) VALUES ('fred' , 'male' ) 
MyProject_DataObject_Testset   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Testset   : find       : 1
MyProject_DataObject_Testset   : CONNECT       : Checking for database specific ini ('') : database_ in options
MyProject_DataObject_Testset   : QUERY       : SELECT * 
 FROM testset 
 
 
 
 
 

MyProject_DataObject_Testset   : query       : QUERY DONE IN  %f seconds
MyProject_DataObject_Testset   : find       : CHECK autofetchd 1
MyProject_DataObject_Testset   : find       : ABOUT TO AUTOFETCH
MyProject_DataObject_Testset   : FETCH       : a:3:{s:2:"id";s:1:"1";s:4:"name";s:4:"fred";s:6:"gender";s:14:"not quite sure";}
MyProject_DataObject_Testset   : find       : DONE
Array
(
    [id] => 1
    [name] => fred
    [gender] => not quite sure
)
DB_DataObject   : CONNECT       : Checking for database specific ini ('') : database_ in options
DB_DataObject   : ERROR       : Connect failed, turn on debugging to 5 see why
DB_DataObject Error: Connect failed, turn on debugging to 5 see why