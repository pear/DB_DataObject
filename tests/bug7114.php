<?php

/*

Bug is in generating of file. with full schema embedded in file.
*/

// load the config.
require_once 'testdb.php';

class testdo extends DB_DataObject {
	var $__table = 'testdo';
	 
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
        $this->query('DROP TABLE IF EXISTS liveuser_users');
        $this->query("CREATE TABLE `liveuser_users` (
                  `auth_user_id` varchar(32) NOT NULL default '',
                  `handle` varchar(32) default NULL,
                  `passwd` varchar(32) default NULL,
                  `owner_user_id` int(11) default NULL,
                  `owner_group_id` int(11) default NULL,
                  `lastlogin` datetime default NULL,
                  `is_active` char(1) default NULL,
                  `company_id` int(10) default 0,
                  `ao_maitreouvrage_id` int(11) default 0,
                  `title` varchar(10) NOT NULL default '',
                  `first_name` varchar(255) NOT NULL default '',
                  `last_name` varchar(255) NOT NULL default '',
                  `fonction` varchar(45) NOT NULL default '',
                  `phone` varchar(20) NOT NULL default '',
                  `gsm` varchar(20) default NULL,
                  `fax` varchar(20) NOT NULL default '',
                  `email` varchar(45) NOT NULL default '',
                  PRIMARY KEY  (`auth_user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ");
        
    }
}

$x = DB_DataObject::factory('testdo');
$x->createDB();

// now generate file stuff..
$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);
$options['debug_force_updates'] = TRUE;
unset($options['proxy']);
$options['class_prefix'] = '';
$options['generator_no_ini'] = 1;

$options['class_location'] = '/tmp/bug7114';
$options['schema_location']   = '/tmp/bug7114';

$options['generator_include_regex'] = '/^(liveuser_.*|testdo)$/i';
$options['class_prefix']  = 'DataObjects_';
set_time_limit(0);
DB_DataObject::debugLevel(1);
$generator = new DB_DataObject_Generator;
$generator->start();

echo file_get_contents('/tmp/bug7114/Testdo.php');
echo file_get_contents('/tmp/bug7114/Liveuser_users.php');