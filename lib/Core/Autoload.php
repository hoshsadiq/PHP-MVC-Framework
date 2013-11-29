<?php

class Core_Autoload
{
    /**
     * Class constructor
     * Registers the autoload function
     */
    public function __construct()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Automatically includes the right files depending on the classname
     * Due to this, it is important that the classname structure must be known.
     * The following rules apply:
     * 1. A Config class MUST be in the format of {TYPE} and the file containing
     *    the class must be in the Config/ folder, and must have the first part camelCased
     *    E.g. class DB must be located in Config/Db.php
     * 2. Classes must be named with the path to the file relative to /lib/ in mind.
     *    E.g. If the class is located in /lib/Core/Autoload.php,
     *    The class MUST be called Core_Autoload.
     *    Similarly, the class Mysql MUST be in the file /lib/Mysql.php
     * 3. Exception classes MUST be placed in /lib/Exception/{$classname}.php
     *    If this file is not found a new class is defined with the following structure:
     *      class {$classname} extends Exception {}
     * 4. File and Folder names MUST be camelCased, e.g. the file lib/core/autoload.php
     *    is invalid. Instead, ignoring the /lib/ folder (this must always stay lowercase)
     *    the file should be in /lib/Core/Autoload.php, wherein the class is called
     *    Core_Autoload.php
     *
     * Note that Core_Autoload() and Maker() will not be able to use this, as such rules have
     * to be first initiated
     *
     * @param string $class The clasname
     * @throws Exception
     * @return boolean whether or not the file was found and included
     */
    public function autoload($class)
    {
        // This if clause tries to find any Exception file, and include that
        // If not, it will create a new Exception class that will extend Exception
        if (substr($class, -9) == 'Exception') {
            $classFile = implode(DS, array(ABSPATH, 'lib', 'Exceptions', $class . '.php'));
            if (file_exists($classFile)) {
                return include($classFile);
            } else {
                if (preg_match('/^[A-Z][a-zA-Z_]+$/', $class)) {
                    eval('class ' . $class . ' extends Exception {}');
                    return true;
                } else {
                    throw new Exception('Tried to initiate an invalid Exception class.');
                }
            }
        } elseif ($class != 'Model' && substr($class, -5) == 'Model') {
            $classFile = ABSPATH . DS . 'model' . DS . substr($class, 0, -5) . '.php';
            if (file_exists($classFile)) {
                return include $classFile;
            } else {
                $table = Inflector::tableize(substr($class, 0, -5));
                if (Mysql::table_exists($table)) {
                    eval('class ' . $class . ' extends Model {}');
                    return true;
                } else {
                    throw new Exception('Tried to initiate an invalid Model class.');
                }
            }
        } else { // Include regular library files.
            $classFile = self::class_to_path($class) . '.php';
            return include $classFile;
        }
    }

    /**
     * Changes class names into file paths, e.g. Core_Autoload will turn into Core/Autoload
     * @param string $class Replaces underscores with directory separators, and capitalises all words
     * @return string The new file location.
     */
    public static function class_to_path($class)
    {
        return str_replace(' ', DS, ucwords(str_replace('_', ' ', strtolower($class))));
    }
}
