--TEST--
DB::DataObject test
--SKIPIF--
<?php
//define('DB_DATAOBJECT_NO_OVERLOAD',true);  

?>
--FILE--
<?php // -*- C++ -*-

error_reporting(E_ALL);


// Test for: DB::parseDSN()

require_once 'DB/DataObject.php';
require_once 'PEAR.php';

$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);
$options['database'] = 'mysql://alan@localhost/test';
$options['debug_force_updates'] = TRUE;
$options['proxy'] = 'full';
$options['class_prefix'] = 'MyProject_DataObject_';
 
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
              `type` enum('Agência','Anunciante') NOT NULL default 'Agência',
              `contact` varchar(100) NOT NULL default '',
              `email` varchar(60) NOT NULL default '',
              `signup` date NOT NULL default '0000-00-00',
              PRIMARY KEY  (`clientID`)
            )  
             ");
        $x->query("INSERT INTO `Client` VALUES (30, 'Internet', 'Anunciante', 'Leandro S.',
                'leandro@s.com', '2004-09-01');");
        $x->query("INSERT INTO `Client` VALUES (26, 'Grupos', 'Agência', 'Gian',
            'gian@email.com', '2004-09-01');");
        $x = new Client;
        $x->get(30);
        print_r($x->toArray());
        
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
        
        
        $page_module= DB_DataObject::Factory('page_module');
        
        // we should guess better.. but this is a kludgy fix.
        //$page_module->sequenceKey(false,false);
        $page_module->page_id=1;
        $page_module->module_id=1;
        $page_module->position='top';
        $page_module->insert();
        
    }
	function test92() {
    
        
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
--EXPECT--
