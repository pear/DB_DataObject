#!/usr/bin/php -q
<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author:  Alan Knowles <alan@akbkhome.com>
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'DB/DataObject/Generator.php';


if (!file_exists(dirname(__FILE__).'/gentest')) {
    require_once 'System.php';
    System::mkdir(dirname(__FILE__).'/gentest');
}
$config['DB_DataObject'] = array(
    'database_test'        => 'mysql://alan:@localhost/test',
    'schema_location'   => dirname(__FILE__).'/gentest',
    'class_location'   => dirname(__FILE__).'/gentest',
    'generator_include_regex' => '/^(phptest|test.*)$/i',
    'class_prefix'  => 'DataObjects_',
);

$options = &PEAR::getStaticProperty('DB_DataObject','options');
$options = $config['DB_DataObject'];

  
set_time_limit(0);
DB_DataObject::debugLevel(1);
$generator = new DB_DataObject_Generator;
$generator->start();
 