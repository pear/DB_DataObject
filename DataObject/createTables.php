#!/usr/bin/php -q
<?
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
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

require_once("DB/DataObject/Generator.php");


if (!@$argv[1]) {
    PEAR::raiseError("\nERROR: createTable.php usage:\n\ncreateTable example.ini\n\n",null,PEAR_ERROR_DIE);
    exit;
}
$config = parse_ini_file($argv[1],TRUE);
$options = &PEAR::getStaticProperty('DB_DataObject','options');
$options = $config['DB_DataObject'];
//DB_DataObject::debugLevel(5);
$generator = new DB_DataObject_Generator;
$generator->start();


?> 