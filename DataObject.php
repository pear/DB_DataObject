<?php
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
// $Id$
//
// Object Based Database Query Builder and data store
//
require_once( 'DB.php' );
require_once( 'PEAR.php' );

/**
 * these are constants for the get_table array 
 * user to determine what type of escaping is required around the object vars.
 */
define("DB_DATAOBJECT_INT",      1);  // does not require ''
define("DB_DATAOBJECT_STR",      2);  // requires ''
define("DB_DATAOBJECT_DATE",     4);  // is date #TODO
define("DB_DATAOBJECT_TIME",     8);  // is time #TODO
define("DB_DATAOBJECT_BOOL",    16);  // is boolean #TODO
define("DB_DATAOBJECT_TXT",     32);  // is long text #TODO

/*  
 * Theses are the standard error codes, most methods will fail silently - and return false
 * to access the error message either use $table->_lastError 
 * or $last_error = PEAR::getStaticProperty('DB_DataObject','lastError');
 * the code is $last_error->code, and the message is $last_error->message (a standard PEAR error)
 */

define("DB_DATAOBJECT_ERROR_INVALIDARGS",     -1);  // wrong args to function
define("DB_DATAOBJECT_ERROR_NODATA",          -2);  // no data available
define("DB_DATAOBJECT_ERROR_INVALIDCONFIG",   -3);  // something wrong with the config
define("DB_DATAOBJECT_ERROR_NOCLASS",         -4);  // no class exists

/**
 * The main "DB_DataObject" class is really a base class for your own tables classes
 * 
 * // Set up the class by creating an ini file (refer to the manual for more details
 * [DB_DataObject]
 * database         = mysql:/username:password@host/database
 * schema_location = /home/myapplication/database
 * class_location  = /home/myapplication/DBTables/
 * clase_prefix    = DBTables_
 * 
 * 
 * 
 * //Start and initialize...................... - dont forget the &
 * $config = parse_ini_file('example.ini',TRUE);
 * $options = &PEAR::setStaticProperty('DB_DataObject','options');
 * $options = $config['DB_DataObject'];
 * 
 * // example of a class (that does not use the 'auto generated tables data')
 * class mytable extends DB_DataObject { 
 *     // mandatory - set the table
 *     var $_database_dsn = "mysql://username:password@localhost/database";
 *     var $__table = "mytable";
 *     function _get_table() {
 *         return array(
 *             'id' => 1, // integer or number
 *             'name' => 2, // string
 *        );
 *     }
 *     function _get_keys() {
 *         return array('id');
 *     }
 * }
 * 
 * // use in the application
 * 
 * 
 * Simple get one row
 * 
 * $instance = new mytable;
 * $instance->get("id",12);
 * echo $instance->somedata;
 * 
 * 
 * Get multiple rows
 * 
 * $instance = new mytable;
 * $instance->whereAdd("ID > 12");
 * $instance->whereAdd("ID < 14");
 * $instance->find();
 * while ($instance->fetch()) {
 *     echo $instance->somedata;
 * }
 * 
 * 
 * @package  DB_DataObject
 * @author   Alan Knowles <alan@akbkhome.com>
 * @since    PHP 4.0
 */


Class DB_DataObject {

   /**
    * The Version - use this to check feature changes
    *
    * @access 	private
    * @var  string
    */
    var $_DB_DataObject_version = "1.0";

    /**
    * The Database table (used by table extends)
    *
    * @access 	private
    * @var  string
    */

    var $__table = "";  // database table

    /* =========================================================== */
    /*  Major Public Methods (designed to be optionally then called with parent::method())*/
    /* =========================================================== */
    


    /**
    * get an result using key, value
    *
    * for example 
    * $object->get("ID",1234);
    * Returns Number of rows located (usually 1) for success,
    * and puts all the table columns into this classes variables
    *
    * see the fetch example on how to extend this.
    *
    * if no value is entered, it is assumed that $key is a value
    * and get will then use the first key in _get_keys
    * to obtain the key.
    *
    * @param 	string 	key column
    * @param 	string 	key value
    * @access	public
    * @return 	int No. of rows
    */
    
    function get($k,$v=NULL) { 
        if ($v === NULL) {
            $v= $k;
            $keys = $this->_get_keys();
            if (!$keys) {
                DB_DataObject::raiseError("No Keys available for {$this->__table}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
                return;
            }
            $k = $keys[0];
        }
        $this->debug("$k $v " .serialize(@$keys), "GET");
        if (!$v) {
            DB_DataObject::raiseError("No Value specified for get", DB_DATAOBJECT_ERROR_INVALIDARGS);
            return;
        }
        $this->$k = $v;
        return $this->find(1);
    }  

    /**
    * a autoloading, caching static get method  using key, value (based on get)
    *
    * Usage:
    * $object = DB_DataObject::staticGet("DbTable_mytable",12);   
    * or 
    * $object =  DB_DataObject::staticGet("DbTable_mytable","name","fred");
    *
    * or write it into your extended class:
    * function &staticGet($k,$v=NULL) { return DB_DataObject::staticGet("This_Class",$k,$v);  }
    *
    * @param 	string 	class name
    * @param 	string 	key column (or value if using _get_keys)
    * @param 	string 	key value (optional)
    * @access	public
    * @return 	object
    */ 
    
    function &staticGet($class,$k,$v=NULL) {
        
        $cache = &PEAR::getStaticProperty('DB_DataObject','cache');

        $key = "$k:$v";
        if ($v===NULL) {
            $key = $k;
        }
        
        DB_DataObject::debug("$class $key","STATIC GET");
        if (@$cache[$class][$key]) {
            return $cache[$class][$key];
        }
        DB_DataObject::debug("$class $key","STATIC GET");
        
        $newclass = DB_DataObject::_autoloadClass($class);
        if (!$newclass) {
            DB_DataObject::raiseError("could not autoload $class", DB_DATAOBJECT_ERROR_NOCLASS);
            return;
        }
        $obj = &new $newclass;
        if (!$obj) {
            DB_DataObject::raiseError("Error creating $newclass", DB_DATAOBJECT_ERROR_NOCLASS);
            return;
        }
        
        if (!@$cache[$class]) {
            $cache[$class] = array();
        }
        if (!$obj->get($k,$v)) {
            DB_DataObject::raiseError("No Data return from get $k $v", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
        $cache[$class][$key] = $obj;
        return $cache[$class][$key];    
    }
    /**
    * find results, either normal or crosstable
    *
    * for example 
    * 
    * $object = new mytable();
    * $object->ID = 1;
    * $object->find();
    * 
    *
    * will set $object->N to number of rows, and expects next command to fetch rows
    * will return $object->N
    *
    * @param    boolean Fetch first result
    * @access	public
    * @return 	int 
    */
    
    
    function find($n=FALSE) {          
        $this->debug($n, "__find",1);       
        if (!$this->__table) { 
            echo "NO \$__table SPECIFIED in class definition";
            exit;
        }
        $this->N =0;
        $tmpcond = $this->_condition;
        $this->_build_condition($this->_get_table()) ;
        $this->_query("SELECT ". 
            $this->_data_select .
            " FROM ".$this->__table ." ". 
            $this->_condition." ".
            $this->_group_by." ".
            $this->_order_by." ".
            $this->_limit); // is select
        ////add by ming ... keep old condition .. so that find can reuse
        $this->_condition = $tmpcond;
      
        $this->debug("CHECK autofetchd $n", "__find",1);  
        if ($n && $this->N > 0 ) {
            $this->debug("ABOUT TO AUTOFETCH", "__find",1);  
            $this->fetchRow(0) ;
        }
        $this->debug("DONE", "__find",1);
        return $this->N;
    }

    
    /**
    * fetches next row into this objects var's
    *
    * returns 1 on success 0 on failure
    *
    * 
    *
    * Example
    * $object = new mytable();
    * $object->name = "fred";
    * $object->find();
    * $store = array();
    * while ($object->fetch()) {
    *   echo $this->ID;
    *   $store[] = $object; // builds an array of object lines.
    * }
    *
    * to add features to a fetch 
    * function fetch () {
    *    $ret = parent::fetch();
    *    $this->date_formated = date('dmY',$this->date);
    *    return $ret;
    * }
    * 
    * @access	public
    * @return 	boolean on success 
    */

    function fetch() { 
        $results = &PEAR::getStaticProperty('DB_DataObject','results');
        
        if (!@$this->N) {
            DB_DataObject::raiseError("fetch: No Data Availabe", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
        $result = &$results[$this->_DB_resultid];
        $array = $result->fetchRow(DB_FETCHMODE_ASSOC);
        $this->debug(serialize($array),"FETCH",3);
      
        if (!is_array($array)) {
            // this is probably end of data!!
            //DB_DataObject::raiseError("fetch: no data returned", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
        
        foreach($array as $k=>$v) {
            $kk = str_replace(".", "_",$k);
            $kk = str_replace(" ", "_",$kk);
            $this->debug("$kk = ". $array[$k], "fetchrow LINE",3);
            $this->$kk = $array[$k];
        } 
         
        // set link flag
        $this->_link_loaded=FALSE;
        
        $this->debug("{$this->__table} DONE", "fetchrow");
     
        return TRUE;
    }
    /**
    * Adds a condition to the WHERE statement, defaults to AND
    * 
    * $object->whereAdd(); //reset or cleaer ewhwer
    * $object->whereAdd("ID > 20");
    * $object->whereAdd("age > 20","OR");
    *
    * @param 	string 	condition
    * @param 	string 	optional logic "OR" (defaults to "AND")
    * @access	public
    * @return 	none
    */

    function whereAdd($cond=FALSE,$logic="AND") {
        if ($cond === FALSE) {
            $this->_condition = '';
            return;
        }
        if ($this->_condition) {
            $this->_condition .= " {$logic} {$cond}";
            return;
        } 
        $this->_condition = " WHERE {$cond}";
        

    }
    
    /**
    * Adds a order by condition 
    * 
    * $object->orderBy(); //clears order by
    * $object->orderBy("ID");
    * $object->orderBy("ID,age");
    *
    * @param 	string 	Order
    * @access	public
    * @return 	none
    */
    function orderBy($order=FALSE) {
        if ($order === FALSE) {
            $this->_order_by = "";
            return;
        }
        if (!$this->_order_by) {
            $this->_order_by = " ORDER BY {$order} ";
            return;
        } 
        $this->_order_by .= " , {$order}";
    }
    
    /**
    * Adds a group by condition 
    * 
    * $object->groupBy(); //reset the grouping
    * $object->groupBy("ID DESC");
    * $object->groupBy("ID,age");
    *
    * @param 	string 	Grouping
    * @access	public
    * @return 	none
    */
    function groupBy($group=FALSE) {
        if ($group === FALSE) {
            $this->_group_by = "";
            return;
        }
        if (!$this->_group_by) {
            $this->_group_by = " GROUP BY {$group} ";
            return;
        } 
        $this->_group_by .= " , {$group}";
    }
    
    /**
    * Sets the Limit 
    * 
    * $boject->limit(); // clear limit
    * $object->limit(12);
    * $object->limit(12,10);
    *
    * @param 	string 	limit start (or number), or blank to reset
    * @param 	string 	number
    * @access	public
    * @return 	none
    */
    
    function limit($a=NULL,$b=NULL) {
        if ($a===NULL) {
           $this->_limit = "";
           return;
        }
        if ($b===NULL) {
           $this->_limit = " LIMIT $a ";
           return;
        }
        $this->_limit = " LIMIT $a,$b ";
    }
    
     
    /**
    * Adds a select columns  
    * 
    * $object->selectAdd(); // resets select to nothing!
    * $object->selectAdd("*"); // default select
    * $object->selectAdd("unixtime(DATE) as udate");   
    * $object->selectAdd("DATE");
    *
    *
    * @access	public
    * @return 	none
    */
    
    
    function selectAdd($k=NULL) {
        if ($k===NULL) {
            $this->_data_select ='';
            return;
        }
        if ($this->_data_select) 
            $this->_data_select .= ", ";
        $this->_data_select .= " $k ";
    }
    
   
    /**
    * Insert the current objects variables into the database
    *
    * Returns the ID of the inserted element - mysql specific = fixme?
    *
    * for example 
    * 
    * Designed to be extended
    *
    * $object = new mytable();
    * $object->name = "fred";
    * echo $object->insert();
    *
    * @access	public
    * @return 	int 
    */
    
    function insert() { 
        $this->_connect();
        $connections = &PEAR::getStaticProperty('DB_DataObject','connections');
        $__DB= &$connections[$this->_database_dsn_md5];
        $items = $this->_get_table();
        if (!$items) {
            DB_DataObject::raiseError("insert:No table definition for {$this->__table}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return;
        }
        $datasaved=1; 
        $leftq="";
        $rightq = "";
        $key = FALSE;
        $keys = $this->_get_keys();
        $dbtype  = $connections[$this->_database_dsn_md5]->dsn["phptype"];
        if (($key = @$keys[0]) && ($dbtype != 'mysql')) {
            $this->$key = $__DB->nextId();
        }
        
        foreach($items as $k=>$v) {
            if (isset($this->$k)) {
                if ($leftq) {
                    $leftq .= ", $k ";
                    $rightq .=", '".addslashes($this->$k)."' ";
                } else { 
                    $leftq = "$k ";
                    $rightq .="'".addslashes($this->$k)."' ";
                }
            }
        }
        if ($leftq) {
            $this->_query("INSERT INTO {$this->__table} ($leftq) VALUES ($rightq) ");
            if ($key && ($dbtype == 'mysql'))
                $this->$key = mysql_insert_id($connections[$this->_database_dsn_md5]->connection);
            $this->_clear_cache();
            return $this->$key;
        } 
        DB_DataObject::raiseError("insert: No Data specifed for query", DB_DATAOBJECT_ERROR_NODATA);
    }

    /**
    * Updates  current objects variables into the database
    * uses the _get_keys() to decide how to update
    * Returns the  TRUE on success 
    *
    * for example 
    * 
    * $object = new mytable();
    * $object->get("ID",234);
    * $object->email="testing@test.com";
    * if(!$object->update())
    *   echo "UPDATE FAILED";
    *
    * @access	public
    * @return 	boolearn TRUE = success 
    */
    
    function update() {       
        $items = $this->_get_table();
        $keys = $this->_get_keys();
        
        if (!$items) {
            DB_DataObject::raiseError("update:No table definition for {$this->__table}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return;
        }    
        $datasaved=1;  
        $settings ="";
        foreach($items as $k=>$v) {
            if (isset($this->$k) && !in_array($k, $keys)) {
                if ($settings)  {
                    $settings .=", ";
                }
                $settings .="$k = '".addslashes($this->$k)."' ";
  	    }
        }
          
        //$this->_condition=""; // dont clear condition
        $this->debug("got keys as ".serialize($keys),3);
        $this->_build_condition($items,$keys);
        //  echo " $settings, $this->condition ";
        if ($settings && $this->_condition) {
            $this->_query("UPDATE  {$this->__table}  SET {$settings} {$this->_condition} "); 
            $this->_clear_cache();
            return TRUE;
        }
        DB_DataObject::raiseError("update: No Data specifed for query $settings , {$this->_condition}", DB_DATAOBJECT_ERROR_NODATA);
    }
    /**
    * Deletes items from table which match current objects variables 
    *
    * Returns the TRUE on success
    *
    * for example 
    * 
    * Designed to be extended
    *
    * $object = new mytable();
    * $object->ID=123;
    * echo $object->delete(); // builds a conditon
    * $object = new mytable();
    * $object->whereAdd('age > 12');
    * $object->delete(TRUE); // use the condition
    *
    * @access	public
    * @param boolean use the whereAdd conditions (default = no - use current values.)
    * @return 	boolean TRUE on success
    */  
    function delete($use_where=FALSE) {     
        $keys = $this->_get_keys();
        if (!$use_where) {
            $this->_condition=""; // default behaviour not to use where condition
        }
        $this->_build_condition($this->_get_table()); 
 
        if ($this->_condition) {
            $this->_query("DELETE FROM {$this->__table} {$this->_condition}");  
            $this->_clear_cache();
            return TRUE;
        }
        DB_DataObject::raiseError("delete: No Data specifed for query {$this->_condition}", DB_DATAOBJECT_ERROR_NODATA);
    }
    /**
    * fetches a specific row into this object variables
    *
    * Not recommended - better to use fetch()
    *
    * Returens TRUE on success
    *  
    * @param 	int   row
    * @access	public
    * @return 	boolean TRUE on success
    */
  
    
       
    function fetchRow($row=NULL) {
        $this->debug("{$this->__table} $row of {$this->N}", "fetchrow",3);       
        if (!$this->__table) {
            DB_DataObject::raiseError("fetchrow: No table", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return;
        }
        if ($row === NULL) {
            DB_DataObject::raiseError("fetchrow: No row specified", DB_DATAOBJECT_ERROR_INVALIDARGS);
            return;
        }
        if (!$this->N) {
            DB_DataObject::raiseError("fetchrow: No results avaiable", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
           
        $this->debug("{$this->__table} $row of {$this->N}", "fetchrow",3);       
        $results = &PEAR::getStaticProperty('DB_DataObject','results');
        
        $result = &$results[$this->_DB_resultid];
        $array = $result->fetchrow(DB_FETCHMODE_ASSOC,$row);
        if (!is_array($array)) {
            DB_DataObject::raiseError("fetchrow: No results available", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
        
        foreach($array as $k=>$v) {
            $kk = str_replace(".", "_",$k);
            $this->debug("$kk = ". $array[$k], "fetchrow LINE",3);
            $this->$kk = $array[$k];
        } 
                   
       
        $this->debug("{$this->__table} DONE", "fetchrow",3);

        return TRUE;
     
        
    }
    
    /**
    * find the number of results from a simple query
    *
    * for example 
    * 
    * $object = new mytable();
    * $object->name = "fred";
    * echo $object->count();
    *
    * @access	public
    * @return 	int 
    */



    function count() {
    
        $items = $this->_get_table();
        $tmpcond = $this->_condition;
        if ($items)  {
            while (list ($k,$v) = each($items)) {
                if (isset($this->$k))  {
                    $this->whereAdd($k." = '".addslashes($this->$k) ."' ");
                }
            }
        }
        $keys = $this->_get_keys();
        
        if (!$keys[0]) {
            echo "CAN NOT COUNT WITHOUT PRIMARY KEYS ";
            exit;
        }

        $this->query("SELECT count({$keys[0]}) as num FROM {$this->__table} {$this->_condition}");
        $this->_condition = $tmpcond;
        $results = &PEAR::getStaticProperty('DB_DataObject','results');
        $result = &$results[$this->_DB_resultid];
        $l=$result->fetchRow(DB_FETCHMODE_ASSOC,0);
        return $l["num"];
    }
    
    /**
    * sends raw query to database
    *
    * Since _query has to be a private 'non overwriteable method', this is a relay
    *
    * @param 	string 	SQL Query
    * @access	public
    * @return 	none
    */
  
    function query($string) { 
        $this->_query($string);  
    }
    
    
    
    
    
    /* ==================================================== */
    /*        Major Private Vars                            */
    /* ==================================================== */
    
    
    /**
    * The Database connection dsn (as described in the PEAR DB)
    * only used really if you are writing a very simple application/test..
    * try not to use this - it is better stored in configuration files..
    * 
    * @access 	private
    * @var  string
    */
    var $_database_dsn = "";
    /**
    * The Database connection id (md5 sum of databasedsn)
    * @access 	private
    * @var  string
    */
    var $_database_dsn_md5 = "";
    
    
    
    
    
    
    /**
    * The Database name
    * created in __connection
    * @access 	private
    * @var  string
    */
    var $_database = "";  
    /**
    * The WHERE condition
    * @access 	private
    * @var  string
    */  
    var $_condition="";
    
    /**
    * The GROUP BY condition
    * @access 	private
    * @var  string
    */  
    var $_group_by="";
    /**
    * The ORDER BY condition
    * @access 	private
    * @var  string
    */  
    var $_order_by="";
    
    /**
    * The Limit by statement
    * @access 	private
    * @var  string
    */  
    var $_limit="";
    /**
    * The default Select
    * @access 	private
    * @var  string
    */    
    var $_data_select="*";
    
    
    /**
    * Database result id (references global $__DB_DataObject_results
    * @access 	private
    * @var  integer
    */ 
    var $_DB_resultid; // database result object
    
     
    
    /* =========================================================== */
    /*  Major Private Methods - the core part!*/
    /* =========================================================== */
    
    /**
    * Autoload  the table definitions
    * 
    *
    * @param string database name
    * @param string table name
    * @access	private
    * @return array
    */
   
    function &_staticGetDefinitions($database,$table) {
    
        static $definitions = array();
        if (@$definitions[$database][$table]) {
            return $definitions[$database][$table];
        }
        $options= &PEAR::getStaticProperty('DB_DataObject','options');
        $location = $options['schema_location'];
        $definitions[$database] = parse_ini_file($location."/{$database}.ini",TRUE);
        return $definitions[$database][$table];
         
    }
   
   
   
    /**
    * get an associative array of table columns
    * 
    * @access	private
    * @return 	array (associative)
    */
    function &_get_table() { 
        if (!@$this->_database) {
            $this->_connect();
        }
        $ret = &DB_DataObject::_staticGetDefinitions($this->_database,$this->__table);
        return $ret;
    }
    
    
    
    /**
    * get an  array of table primary keys 
    *
    * This is defined in the table definition which should extend this class
    * 
    * @access	private
    * @return 	array
    */   
    
    
    function &_get_keys() { 
        if (!@$this->_database) {
            $this->_connect();
        }
        return array_keys(DB_DataObject::_staticGetDefinitions($this->_database,$this->__table."__keys"));
    }
   
    
    /**
    * clear the cache values for this class  - normally done on insert/update etc.
    *
    * @access	private
    * @return 	none
    */
    
    function _clear_cache() {
        $cache = &PEAR::getStaticProperty('DB_DataObject','cache');
        $class = get_class($this);
        if (@$cache[$class]) {
            unset($cache[$class]);
        }
    }
    
 
    /**
    * connects to the database
    *
    * @access	private
    * @return 	none
    */
       
    function _connect () {
        $connections = &PEAR::getStaticProperty('DB_DataObject','connections');
        
        
        if ($this->_database_dsn_md5) {// already connected
            if (PEAR::isError($connections[$this->_database_dsn_md5])) {
                DB_DataObject::raiseError(
                        $connections[$this->_database_dsn_md5]->message, 
                        $connections[$this->_database_dsn_md5]->code, PEAR_ERROR_DIE
                );
                return;
            }
            $this->_database = $connections[$this->_database_dsn_md5]->dsn["database"];
            return;
        }
        
        $options= &PEAR::getStaticProperty('DB_DataObject','options');
        $dsn = @$this->_database_dsn;
        
        if (!$dsn) {
            if ($database = @$options["table_{$this->__table}"])  {
                $dsn = $options["database_{$database}"];
            } else if ($options['database']) { 
                $dsn = $options['database'];
            }
        }
        $this->_database_dsn_md5 = md5($dsn);
          
        if (@$connections[$this->_database_dsn_md5]) {
            $this->debug("USING CACHE", "CONNECT",3);
            $this->_database = $connections[$this->_database_dsn_md5]->dsn["database"];
            return;
        }
        $this->debug("NEW CONNECTION", "CONNECT",3);
        /* actualy make a connection */
        $this->debug("{$dsn} {$this->_database_dsn_md5}", "CONNECT",3);
        $connections[$this->_database_dsn_md5] = DB::connect($dsn); 
        $this->debug(serialize($connections), "CONNECT",3);
         
        if (PEAR::isError($connections[$this->_database_dsn_md5])) {
            DB_DataObject::raiseError(
                        $connections[$this->_database_dsn_md5]->message, 
                        $connections[$this->_database_dsn_md5]->code, PEAR_ERROR_DIE
            );
            
        }
        
        $this->_database = $connections[$this->_database_dsn_md5]->dsn["database"];
        return TRUE;
    }
    /**
    * sends query to database - this is the private one that must work - internal functions use this rather than $this->query()
    *
    * @access	private
    * @return 	none
    */
 
    function _query($string) {
        $connections = &PEAR::getStaticProperty('DB_DataObject','connections');
        $results = &PEAR::getStaticProperty('DB_DataObject','results');
     
        
        
        $this->debug("QUERY".$string,$log="sql");
        $this->_connect();
        $__DB = &$connections[$this->_database_dsn_md5];
       
        if (DB_DataObject::debugLevel() && 
            (strtolower(substr(trim($string),0,6)) != "select") && 
            (strtolower(substr(trim($string),0,4)) != "show") &&
            (strtolower(substr(trim($string),0,8)) != "describe")) {
            $this->debug("Disabling Update as you are in debug mode");
            return;
        }
        $this->_DB_resultid = count($results); // add to the results stuff...
        $results[$this->_DB_resultid] = $__DB->query($string);
        $result = &$results[$this->_DB_resultid];
        if (DB::isError($result)) {
            DB_DataObject::debug($string, "SENT");
            
            DB_DataObject::raiseError($result->message, $result->code);
            return;
        }
        $this->debug("DONE QUERY", "query");
        if (strtolower(substr(trim($string),0,6)) == "insert") {
            return;
        }
        if (strtolower(substr(trim($string),0,6)) == "update") { 
            return;
        }
        $this->N = 0;
        $this->debug(serialize($result), "RESULT",3);
        if (method_exists($result, "numrows")) {
            $this->N = $result->numrows();
        }
        
    }
    
  
    
    
  
    
    
    /**
    * Builds the WHERE based on the values of of this object
    *
    * @access	private
    * @return 	string
    */  
    function _build_condition(&$keys,$filter=array()) {
       
        foreach($keys as $k=>$v) {
            if ($filter) {
                if (!in_array($k,$filter)) {
                    continue;
                }
            }
            if (!isset($this->$k)) {
                continue;
            }
            
            if ($v & DB_DATAOBJECT_STR) {
                $this->whereAdd("{$k} = '".addslashes($this->$k) ."'");
                continue;
            }
            if (is_numeric($this->$k)) {
                $this->whereAdd("$k = {$this->$k}");
                continue;
            }
            /* this is probably an error condition! */
            $this->whereAdd("$k = 0");
        }
    }
    /**
    * autoload Class relating to a table
    *
    * @param table
    * @access	private
    * @return string classname on Success
    */
   
    function _autoloadTable($table) {
        $options= &PEAR::getStaticProperty('DB_DataObject','options');
        $class = DB_DataObject::_autoloadClass($options['class_prefix'].ucfirst($table));
        return $class;
    }
       
     /**
    * autoload Class 
    *
    * @param Class
    * @access	private
    * @return string classname on Success
    */
 
    function _autoloadClass($class) {
        $options= &PEAR::getStaticProperty('DB_DataObject','options');
        $table = substr($class,strlen($options['class_prefix']));
        
            // this should/could autoload
        @include_once($options['require_prefix'].ucfirst($table).".php");
        if (!class_exists($class)) {
            DB_DataObject::raiseError("autoload:Could not autoload {$class}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return FALSE;
        }
        return $class;
    }

    /**
    * Have the links been loaded?
    * @access 	private
    * @var  boolean
    */  
   
    var $_link_loaded=FALSE; 
    /**
    * load related objects 
    *
    * This is highly dependant on naming columns 'correctly' :)
    * using colname = xxxxx_yyyyyy
    * xxxxxx = related table; (yyyyy = user defined..) 
    * looks up table xxxxx, for value id=$this->xxxxx
    * stores it in $this->_xxxxx_yyyyy
    *
    * @access	public
    * @return 	boolean , TRUE on success
    */
    function getLinks() {
       
        if ($this->_link_loaded) {
            return;
        }
        $cols = $this->_get_table();      
        foreach (array_keys($cols) as $key) {
            
            if (!($p = strpos($key,'_'))) {
                continue;
            }
            // does the table exist. 
            $k = "_{$key}";
            $this->$k = $this->getLink($key);
        }    
        $this->_link_loaded=TRUE;
    }
     /**
    * return name from related object
    *
    * This is highly dependant on naming columns 'correctly' :)
    * using colname = xxxxx_yyyyyy
    * xxxxxx = related table; (yyyyy = user defined..) 
    * looks up table xxxxx, for value id=$this->xxxxx
    * stores it in $this->_xxxxx_yyyyy
    *
    * @access	public
    * @return mixed object on success
    */
    function &getLink($row, $table=NULL) {
        if ($table === NULL) {
            if (!($p = strpos($row,'_'))) { 
                return;
            }
            $table = substr($row,0,$p);
        }
        if (!isset($this->$row)) {
            DB_DataObject::raiseError("getLink: row not set $row", DB_DATAOBJECT_ERROR_NODATA);
            return;
        }
        
        
        $class = $this->_autoloadTable($table);
        if (!$class) {
            DB_DataObject::raiseError("getLink:Could not find class for row $row, table $table", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return;
        }
        
        
        return DB_DataObject::staticGet($class,$this->$row);
    }
    /*
     * return a list of options for a linked table
    *
    * This is highly dependant on naming columns 'correctly' :)
    * using colname = xxxxx_yyyyyy
    * xxxxxx = related table; (yyyyy = user defined..) 
    * looks up table xxxxx, for value id=$this->xxxxx
    * stores it in $this->_xxxxx_yyyyy
    *
    * @access	public
    * @return array of results (empty array on failure)
    */

    function &getLinkArray($row,$table = NULL) {
        $ret = array();
        if (!$table) {
            if (!($p = strpos($row,'_'))) {
                return $ret;
            }
            $table = substr($row,0,$p);
        }
        $class = $this->_autoloadTable($table);
        if (!$class) {
            DB_DataObject::raiseError("getLinkArray:Could not find class for row $row, table $table", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return $ret;
        }
        $c = new $class;
        // if the user defined method list exists - use it...
        if (method_exists($c,'list')) {
            $c->list();
        } else {
            $c->find();
        }
        while ($c->fetch()) {
            $ret[] = $c;
        }
        return $ret;
    }
    /**
    * Copies items that are in the table definitions from an 
    * array or object into the current object
    * will not override key values.
    * 
    *
    * @param    array | object  
    * @access	public
    * @return 	boolean , TRUE on success
    */
  
    
    
    function setFrom(&$from) {
        $keys = $this->_get_keys();
        $items = $this->_get_table();
        if (!$items) {
            DB_DataObject::raiseError("setFrom:Could not find table definition for {$this->__table}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return;
        }
        foreach (array_keys($items) as $k) {
            if (in_array($k,$keys)) {
                continue; // dont overwrite keys
            }
            if (!$k) {
                continue; // ignore empty keys!!! what
            }
            if (is_object($from) && @isset($from->$k)) {
                $this->$k = $this->$k;    
                continue;
            }
            if (!@isset($from[$k])) {
                continue;
            }
            if (is_object($from[$k])) {
                continue;
            }
            if (is_array($from[$k])) {
                continue;
            }
            $this->$k = $from[$k];
        }
        return TRUE;
    }  
  
    /* ----------------------- Debugger ------------------ */
    /**
    * Debugger. - use this in your extended classes to output debugging information.
    * 
    * Uses DB_DataObject::DebugLevel(x) to turn it on
    *
    * @param    string message - message to output
    * @param    string logtype - bold at start
    * @param    string level - output level
    * @access	public
    * @return 	none
    */
    
    function debug($message,$logtype=0, $level=1) {
        if (DB_DataObject::debugLevel()<$level) {
            return;
        }
        
        if (!ini_get('html_errors')) {
            echo "$logtype       : $message\n"; 
            flush();
            return;
        }
        echo "<PRE><B>$logtype</B> $message</PRE>\n"; 
        flush();
    }
    /**
    * sets and returns debug level
    * eg. DB_DataObject::debugLevel(4);
    *
    * @param    int level 
    * @access	public
    * @return 	none
    */
    function debugLevel($v=NULL) {
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        if ($v !== NULL) {
            $options['debug']  = $v;
        }
        return @$options['debug'];
    }
    
    /**
    * Last Error that has occured
    * - use $this->_lastError or 
    * $last_error = &PEAR::getStaticProperty('DB_DataObject','lastError');
    * 
    * @access 	public
    * @var  object PEAR_Error (or FALSE)
    */  
   
    var $_lastError=FALSE; 
    
     /**
    * Default error handling is to create a pear error, but never return it. 
    * if you need to handle errors you should look at setting the PEAR_Error callback
    * this is due to the fact it would wreck havoc on the internal methods!
    *
    * @param    int message 
    * @param    int type
    * @param    int behaviour (die or contine!);
    * @access	  public
    * @return   error object
    */
    function raiseError($message,$type,$behaviour=NULL) {
        $error = PEAR::raiseError($message,$type,$behaviour);
        if (is_object($this)) {
            $this->_lastError = $error;
        } 
        $last_error = &PEAR::getStaticProperty('DB_DataObject','lastError');
        $last_error = $error;
        
        DB_DataObject::debug($message,"ERROR",1);
    }
   

}
       
 
?>