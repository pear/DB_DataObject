<?php
//define('DB_DATAOBJECT_NO_OVERLOAD',true);
 
require_once 'DB/DataObject.php';

$options = &PEAR::getStaticProperty('DB_DataObject','options');
//$options['schema_location'] = dirname(__FILE__);
$options['database'] = 'mysql://@localhost/test';
$options['debug_force_updates'] = TRUE;
$options['proxy'] = 'full';
$options['class_prefix'] = 'MyProject_DataObject_';
  
$oldUsg = memory_get_usage();
$i=0;
for( ; true; ) {
  $mytable = &DB_DataObject::factory('test');
  $mytable->find();
  while($mytable->fetch() ) { /* NULL */ } // fetch ALL records
  $mytable->free();
  unset($mytable);
  if (strlen(serialize($_DB_DATAOBJECT)) > 7000) {
      print_r($_DB_DATAOBJECT);exit;
    }
  echo "$i. global size:".strlen(serialize($_DB_DATAOBJECT)). ",memory increase: ";

  //print_r($_DB_DATAOBJECT);
  $newUsg = memory_get_usage();
  echo ($deltaUsg = $newUsg - $oldUsg)."\n"; // this is within 728 and 1048 each iteration

  $oldUsg = memory_get_usage();
  $i++;
} 


