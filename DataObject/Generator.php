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
// $Id$

/**
 * Generation tools for DB_DataObjectp
 *
 * Config _$ptions
 * [DB_DataObject_Generator]
 * ; optional default = DB/DataObject.php
 * extends_location =
 * ; optional default = DB_DataObject
 * extends =
 * ; leave blank to not generate template stuff.
 * make_template = display,list,edit
 *
 * ; options for Template Generation (using FlexyTemplate
 * [DB_DataObject_Generator_Template_Flexy]
 * templateDir = /home/xxx/templates
 * compileDir = /home/xxx/compiled",
 * filters   = php,simpletags,bodyonly
 * forceCompile = 0
 *
 * ; fileds to flags as hidden for template generation(preg_match format)
 * hideFields = password
 * ; fields to flag as read only.. (preg_match format)
 * readOnlyFields = created|person_created|modified|person_modified
 * ; fields to flag as links (from lists->edit/view) (preg_match formate)
 * linkFields = id|username|name
 * ; alter the extends field when updating a class (defaults to only replacing DB_DataObject)
 * generator_class_rewrite = ANY|specific_name   // default is DB_DataObject
 *
 * @package  DB_DataObject
 * @category DB
 */

/**
 * Needed classes
 */
require_once 'DB/DataObject.php';
//require_once('Config.php');

/**
 * Generator class
 *
 * @package DB_DataObject
 */
class DB_DataObject_Generator extends DB_DataObject
{
    /* =========================================================== */
    /*  Utility functions - for building db config files           */
    /* =========================================================== */

    /**
     * Array of table names
     *
     * @var array
     * @access private
     */
    var $tables;

    /**
     * associative array table -> array of table row objects
     *
     * @var array
     * @access private
     */
    var $_definitions;

    /**
     * active table being output
     *
     * @var string
     * @access private
     */
    var $table; // active tablename


    /**
     * The 'starter' = call this to start the process
     *
     * @access  public
     * @return  none
     */
    function start()
    {
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $databases = array();
        foreach($options as $k=>$v) {
            if (substr($k,0,9) == 'database_') {
                $databases[substr($k,9)] = $v;
            }
        }

        if (@$options['database']) {
            $dsn = DB::parseDSN($options['database']);
            if (!isset($database[$dsn['database']])) {
                $databases[$dsn['database']] = $options['database'];
            }
        }

        foreach($databases as $databasename => $database) {
            if (!$database) continue;
            $this->debug("CREATING FOR $databasename\n");
            $class = get_class($this);
            $t = new $class;
            $t->_database_dsn = $database;
            $t->_database = $databasename;
            $t->_createTableList();

            foreach(get_class_methods($class) as $method) {
                if (substr($method,0,8 ) != 'generate') {
                    continue;
                }
                $this->debug("calling $method");
                $t->$method();
            }
        }
        $this->debug("DONE\n\n");
    }

    /**
     * Output File was config object, now just string
     * Used to generate the Tables
     *
     * @var    string outputbuffer for table definitions
     * @access private
     */
    var $_newConfig;

    /**
     * Build a list of tables;
     * Currently this is very Mysql Specific - ideas for more generic stiff welcome
     *
     * @access  private
     * @return  none
     */
    function _createTableList()
    {
        $this->_connect();
        $options = &PEAR::getStaticProperty('DB_DataObject','options');

        $__DB= &$GLOBALS['_DB_DATAOBJECT']['CONNECTIONS'][$this->_database_dsn_md5];

        $this->tables = $__DB->getListOf('tables');

        foreach($this->tables as $table) {
            if (isset($options['generator_include_regex']) &&
                !preg_match($options['generator_include_regex'],$table)) {
                    continue;
            } else if (isset($options['generator_exclude_regex']) &&
                preg_match($options['generator_exclude_regex'],$table)) {
                    continue;
            }
            $defs =  $__DB->tableInfo($table);
            
            // cast all definitions to objects - as we deal with that better.
            foreach($defs as $def) {
                if (is_array($def)) {
                    $this->_definitions[$table][] = (object) $def;
                }
            }
        }
        //print_r($this->_definitions);
    }

    /**
     * Auto generation of table data.
     *
     * it will output to db_oo_{database} the table definitions
     *
     * @access  private
     * @return  none
     */
    function generateDefinitions()
    {
        $this->debug("Generating Definitions file:        ");
        if (!$this->tables) {
            $this->debug("-- NO TABLES -- \n");
            return;
        }

        $options = &PEAR::getStaticProperty('DB_DataObject','options');


        //$this->_newConfig = new Config('IniFile');
        $this->_newConfig = '';
        foreach($this->tables as $this->table) {
            $this->_generateDefinitionsTable();
        }
        $this->_connect();
        // dont generate a schema if location is not set
        // it's created on the fly!
        if (!@$options['schema_location'] && @!$options["ini_{$this->_database}"] ) {
            return;
        }
        $base =  @$options['schema_location'];
        if (isset($options["ini_{$this->_database}"])) {
            $file = $options["ini_{$this->_database}"];
        } else {
            $file = "{$base}/{$this->_database}.ini";
        }
        
        if (!file_exists(dirname($file))) {
            require_once 'System.php';
            System::mkdir(array('-p','-m',0755,dirname($file)));
        }
        $this->debug("Writing ini as {$file}\n");
        touch($file);
        //print_r($this->_newConfig);
        $fh = fopen($file,'w');
        fwrite($fh,$this->_newConfig);
        fclose($fh);
        //$ret = $this->_newConfig->writeInput($file,false);

        //if (PEAR::isError($ret) ) {
        //    return PEAR::raiseError($ret->message,null,PEAR_ERROR_DIE);
        // }
    }

    /**
     * The table geneation part
     *
     * @access  private
     * @return  tabledef and keys array.
     */
    function _generateDefinitionsTable()
    {
        global $_DB_DATAOBJECT;
        
        $defs = $this->_definitions[$this->table];
        $this->_newConfig .= "\n[{$this->table}]\n";
        $keys_out =  "\n[{$this->table}__keys]\n";
        $keys_out_primary = '';
        $keys_out_secondary = '';
        if (@$_DB_DATAOBJECT['CONFIG']['debug'] > 2) {
            echo "TABLE STRUCTURE FOR {$this->table}\n";
            print_r($defs);
        }
        $DB = $this->getDatabaseConnection();
        $dbtype = $DB->phptype;
        
        $ret = array(
                'table' => array(),
                'keys' => array(),
            );
            
        $ret_keys_primary = array();
        $ret_keys_secondary = array();
        
        
        
        foreach($defs as $t) {
             
            $n=0;

            switch (strtoupper($t->type)) {

                case 'INT':
                case 'INT2':    // postgres
                case 'INT4':    // postgres
                case 'INT8':    // postgres
                case 'SERIAL4': // postgres
                case 'SERIAL8': // postgres
                case 'INTEGER':
                case 'TINYINT':
                case 'SMALLINT':
                case 'MEDIUMINT':
                case 'BIGINT':
                    $type = DB_DATAOBJECT_INT;
                    if ($t->len == 1) {
                        $type +=  DB_DATAOBJECT_BOOL;
                    }
                    break;
               
                case 'REAL':
                case 'DOUBLE':
                case 'FLOAT':
                case 'FLOAT8': // double precision (postgres)
                case 'DECIMAL':
                case 'NUMERIC':
                    $type = DB_DATAOBJECT_INT; // should really by FLOAT!!! / MONEY...
                    break;
                    
                case 'YEAR':
                    $type = DB_DATAOBJECT_INT; 
                    break;
                    
                case 'BIT':
                case 'BOOL':   
                case 'BOOLEAN':   
                
                    $type = DB_DATAOBJECT_BOOL;
                    // postgres needs to quote '0'
                    if ($dbtype == 'pgsql') {
                        $type +=  DB_DATAOBJECT_STR;
                    }
                    break;
                    
                case 'STRING':
                case 'CHAR':
                case 'VARCHAR':
                case 'VARCHAR2':
                case 'TINYTEXT':
                case 'TEXT':
                case 'MEDIUMTEXT':
                case 'LONGTEXT':
                case 'ENUM':
                case 'SET':         // not really but oh well
                case 'TIMESTAMPTZ': // postgres
                case 'BPCHAR':      // postgres
                case 'INTERVAL':    // postgres (eg. '12 days')
                
                case 'CIDR':        // postgres IP net spec
                case 'INET':        // postgres IP
                case 'MACADDR':     // postgress network Mac address.
                
                
                    $type = DB_DATAOBJECT_STR;
                    break;
                    
                case 'DATE':    
                    $type = DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE;
                    break;
                    
                case 'TIME':    
                    $type = DB_DATAOBJECT_STR + DB_DATAOBJECT_TIME;
                    break;    
                    
                
                case 'DATETIME': 
                     
                    $type = DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME;
                    break;    
                    
                case 'TIMESTAMP': // do other databases use this???
                    
                    $type = ($dbtype == 'mysql') ?
                        DB_DATAOBJECT_MYSQLTIMESTAMP : 
                        DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME;
                    break;    
                    
                    
                case 'TINYBLOB':
                case 'BLOB':       /// these should really be ignored!!!???
                case 'MEDIUMBLOB':
                case 'LONGBLOB':
                case 'BYTEA':   // postgres blob support..
                    $type = DB_DATAOBJECT_STR + DB_DATAOBJECT_BLOB;
                    break;
                    
                    
            }
            
            
            if (!strlen(trim($t->name))) {
                continue;
            }
            
            if (preg_match('/not_null/i',$t->flags)) {
                $type += DB_DATAOBJECT_NOTNULL;
            }
            
            $this->_newConfig .= "{$t->name} = $type\n";
            $ret['table'][$t->name] = $type;
            // i've no idea if this will work well on other databases?
            // only use primary key or nextval(), cause the setFrom blocks you setting all key items...
            // if no keys exist fall back to using unique
            //echo "\n{$t->name} => {$t->flags}\n";
            if (preg_match("/(auto_increment|nextval\()/i",$t->flags)) {
                // native sequences = 2
                $keys_out_primary .= "{$t->name} = N\n";
                $ret_keys_primary[$t->name] = 'N';
            } else if (preg_match("/(primary|unique)/i",$t->flags)) {
                // keys.. = 1
                $keys_out_secondary .= "{$t->name} = K\n";
                $ret_keys_secondary[$t->name] = 'K';
            }
            
            
            

        }
        
        $this->_newConfig .= $keys_out . (empty($keys_out_primary) ? $keys_out_secondary : $keys_out_primary);
        $ret['keys'] = empty($keys_out_primary) ? $ret_keys_secondary : $ret_keys_primary;
        
        if (@$_DB_DATAOBJECT['CONFIG']['debug'] > 2) {
            print_r(array("dump for {$this->table}", $ret));
        }
        
        return $ret;
        
        
    }

    /*
     * building the class files
     * for each of the tables output a file!
     */
    function generateClasses()
    {
        //echo "Generating Class files:        \n";
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $base = $options['class_location'];
        if (!file_exists($base))
            mkdir($base,0755);
        $class_prefix  = $options['class_prefix'];
        if ($extends = @$options['extends']) {
            $this->_extends = $extends;
            $this->_extendsFile = $options['extends_location'];
        }

        foreach($this->tables as $this->table) {
            $this->table = trim($this->table);
            $this->classname = $class_prefix.preg_replace('/[^A-Z0-9]/i','_',ucfirst($this->table));
            $i = '';
            $outfilename = "{$base}/".preg_replace('/[^A-Z0-9]/i','_',ucfirst($this->table)).".php";
            if (file_exists($outfilename))
                $i = implode('',file($outfilename));
            $out = $this->_generateClassTable($i);
            $this->debug( "writing $this->classname\n");
            $fh = fopen($outfilename, "w");
            fputs($fh,$out);
            fclose($fh);
        }
        //echo $out;
    }

    /**
     * class being extended (can be overridden by [DB_DataObject_Generator] extends=xxxx
     *
     * @var    string
     * @access private
     */
    var $_extends = 'DB_DataObject';

    /**
     * line to use for require('DB/DataObject.php');
     *
     * @var    string
     * @access private
     */
    var $_extendsFile = "DB/DataObject.php";

    /**
     * class being generated
     *
     * @var    string
     * @access private
     */
    var $_className;

    /**
     * The table class geneation part - single file.
     *
     * @access  private
     * @return  none
     */
    function _generateClassTable($input = '')
    {
        // title = expand me!
        $foot = "";
        $head = "<?php\n/**\n * Table Definition for {$this->table}\n */\n";
        // requires
        $head .= "require_once '{$this->_extendsFile}';\n\n";
        // add dummy class header in...
        // class
        $head .= "class {$this->classname} extends {$this->_extends} \n{\n";

        $body =  "\n    ###START_AUTOCODE\n";
        $body .= "    /* the code below is auto generated do not remove the above tag */\n\n";
        // table
        $padding = (30 - strlen($this->table));
        if ($padding < 2) $padding =2;
        $p =  str_repeat(' ',$padding) ;
        $body .= "    var \$__table = '{$this->table}';  {$p}// table name\n";
        
        
        // if we are using the option database_{databasename} = dsn
        // then we should add var $_database = here
        // as database names may not always match.. 
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        if (isset($options["database_{$this->_database}"])) {
            $body .= "    var \$_database = '{$this->_database}';  {$p}// database name (used with database_{*} config)\n";
        }
        
        
        
        
        $defs = $this->_definitions[$this->table];

        // show nice information!
        $connections = array();
        $sets = array();
        foreach($defs as $t) {
            if (!strlen(trim($t->name))) {
                continue;
            }
            $padding = (30 - strlen($t->name));
            if ($padding < 2) $padding =2;
            $p =  str_repeat(' ',$padding) ;
            $body .="    var \${$t->name};  {$p}// {$t->type}({$t->len})  {$t->flags}\n";
            // can not do set as PEAR::DB table info doesnt support it.
            //if (substr($t->Type,0,3) == "set")
            //    $sets[$t->Field] = "array".substr($t->Type,3);
            $body .= $this->derivedHookVar($t,$padding);
        }

        // THIS IS TOTALLY BORKED old FC creation
        // IT WILL BE REMOVED!!!!! in DataObjects 1.6
        // grep -r __clone * to find all it's uses
        // and replace them with $x = clone($y);
        // due to the change in the PHP5 clone design.
        
        if ( substr(phpversion(),0,1) < 5) {
            $body .= "\n";
            $body .= "    /* ZE2 compatibility trick*/\n";
            $body .= "    function __clone() { return \$this;}\n";
        }

        // simple creation tools ! (static stuff!)
        $body .= "\n";
        $body .= "    /* Static get */\n";
        $body .= "    function staticGet(\$k,\$v=NULL) { return DB_DataObject::staticGet('{$this->classname}',\$k,\$v); }\n";

        /*
        theoretically there is scope here to introduce 'list' methods
        based up 'xxxx_up' column!!! for heiracitcal trees..
        */

        // set methods
        //foreach ($sets as $k=>$v) {
        //    $kk = strtoupper($k);
        //    $body .="    function getSets{$k}() { return {$v}; }\n";
        //}
        $body .= $this->derivedHookFunctions();

        $body .= "\n    /* the code above is auto generated do not remove the tag below */";
        $body .= "\n    ###END_AUTOCODE\n";

        $foot .= "}\n";
        $full = $head . $body . $foot;

        if (!$input) {
            return $full;
        }
        if (!preg_match('/(\n|\r\n)\s*###START_AUTOCODE(\n|\r\n)/s',$input))  {
            return $full;
        }
        if (!preg_match('/(\n|\r\n)\s*###END_AUTOCODE(\n|\r\n)/s',$input)) {
            return $full;
        }


        /* this will only replace extends DB_DataObject by default,
            unless use set generator_class_rewrite to ANY or a name*/

        $class_rewrite = 'DB_DataObject';
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        if (!($class_rewrite = @$options['generator_class_rewrite'])) {
            $class_rewrite = 'DB_DataObject';
        }
        if ($class_rewrite == 'ANY') {
            $class_rewrite = '[a-z_]+';
        }

        $input = preg_replace(
            '/(\n|\r\n)class\s*[a-z0-9_]+\s*extends\s*' .$class_rewrite . '\s*\{(\n|\r\n)/si',
            "\nclass {$this->classname} extends {$this->_extends} \n{\n",
            $input);

        return preg_replace(
            '/(\n|\r\n)\s*###START_AUTOCODE(\n|\r\n).*(\n|\r\n)\s*###END_AUTOCODE(\n|\r\n)/s',
            $body,$input);
    }

    /**
     * hook to add extra methods to all classes
     *
     * called once for each class, use with $this->table and
     * $this->_definitions[$this->table], to get data out of the current table,
     * use it to add extra methods to the default classes.
     *
     * @access   public
     * @return  string added to class eg. functions.
     */
    function derivedHookFunctions()
    {
        // This is so derived generator classes can generate functions
        // It MUST NOT be changed here!!!
        return "";
    }

    /**
     * hook for var lines
     * called each time a var line is generated, override to add extra var
     * lines
     *
     * @param object t containing type,len,flags etc. from tableInfo call
     * @param int padding number of spaces
     * @access   public
     * @return  string added to class eg. functions.
     */
    function derivedHookVar(&$t,$padding)
    {
        // This is so derived generator classes can generate variabels
        // It MUST NOT be changed here!!!
        return "";
    }


    /**
    * getProxyFull - create a class definition on the fly and instantate it..
    *
    * similar to generated files - but also evals the class definitoin code.
    * 
    * 
    * @param   string database name
    * @param   string  table   name of table to create proxy for.
    * 
    *
    * @return   object    Instance of class. or PEAR Error
    * @access   public
    */
    function getProxyFull($database,$table) {
        
        if ($err = $this->fillTableSchema($database,$table)) {
            return $err;
        }
        
        
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $class_prefix  = $options['class_prefix'];
        
        if ($extends = @$options['extends']) {
            $this->_extends = $extends;
            $this->_extendsFile = $options['extends_location'];
        }

        
        $classname = $this->classname = $class_prefix.preg_replace('/[^A-Z0-9]/i','_',ucfirst(trim($this->table)));

        $out = $this->_generateClassTable();
        //echo $out;
        eval('?>'.$out);
        return new $classname;
        
    }
    
     /**
    * fillTableSchema - set the database schema on the fly
    *
    * 
    * 
    * @param   string database name
    * @param   string  table   name of table to create schema info for
    *
    * @return   none | PEAR::error()
    * @access   public
    */
    function fillTableSchema($database,$table) {
        global $_DB_DATAOBJECT;
        $this->_database  = $database; 
        $this->_connect();
        $table = trim($table);
        
        $__DB= &$GLOBALS['_DB_DATAOBJECT']['CONNECTIONS'][$this->_database_dsn_md5];
        
        $defs =  $__DB->tableInfo($table);
        if (PEAR::isError($defs)) {
            return $defs;
        }
        if (@$_DB_DATAOBJECT['CONFIG']['debug'] > 2) {
            $this->debug("getting def for $database/$table",'fillTable');
            $this->debug(print_r($defs,true),'defs');
        }
        // cast all definitions to objects - as we deal with that better.
        
            
        foreach($defs as $def) {
            if (is_array($def)) {
                $this->_definitions[$table][] = (object) $def;
            }
        }

        $this->table = trim($table);
        $ret = $this->_generateDefinitionsTable();
        
        $_DB_DATAOBJECT['INI'][$database][$table] = $ret['table'];
        $_DB_DATAOBJECT['INI'][$database][$table.'__keys'] = $ret['keys'];
        return false;
        
    }
    


}
