<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
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
// | Authors:  Alan Knowles <alan@akbkhome.com>                           |
// +----------------------------------------------------------------------+
//
// $Id$
//
//  DataObjects error handler, loaded on demand...
//

/**
 * DB_DataObject_Error is a quick wrapper around pear error, so you can distinguish the
 * error code source.
 * messages.
 *
 * @package  DB_DataObject
 * @author Alan Knowles <alan@akbkhome.com>
 */
class DB_DataObject_Error extends PEAR_Error
{
    
    /**
     * DB_DataObject_Error constructor.
     *
     * @param mixed   $code   DB error code, or string with error message.
     * @param integer $mode   what "error mode" to operate in
     * @param integer $level  what error level to use for $mode & PEAR_ERROR_TRIGGER
     * @param mixed   $debuginfo  additional debug info, such as the last query
     *
     * @access public
     *
     * @see PEAR_Error
     */
    function DB_DataObject_Error($message = '', $code = DB_ERROR, $mode = PEAR_ERROR_RETURN,
              $level = E_USER_NOTICE)
    {
        $this->PEAR_Error('DB_DataObject Error: ' . $message, $code, $mode, $level);
        
    }
    
    
    // todo : - support code -> message handling, and translated error messages...
    
    
    
}
