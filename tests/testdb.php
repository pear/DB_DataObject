<?php // -*- C++ -*-

error_reporting(E_ALL);


// Test for: DB::parseDSN()

require_once '../DataObject.php';
require_once '../DataObject/Generator.php';
require_once 'PEAR.php';

$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);
$options['database'] = 'mysql://alan@localhost/test';
$options['debug_force_updates'] = TRUE;
$options['proxy'] = 'light';
$options['class_prefix'] = '';
 
DB_DataObject::debugLevel(1);


