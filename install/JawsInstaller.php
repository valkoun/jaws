<?php
/**
 * Jaws installer management class.
 *
 * @category   Application
 * @package    Install
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class JawsInstaller
{
    /**
     * The filesystem path the installer is running from.
     * @var string
     */
    var $_stage_config;

    /**
     * The complete stage list.
     * @var array
     */
    var $Stages = array();

    /**
     * Predefined data
     * @var array
     * @access protected
     */
    var $_predefined = array();

    var $_isPredefined = false;

    /**
     * Constructor
     *
     * @param string The path this installer is running from.
     */
    function JawsInstaller($config = null)
    {
        $this->_stage_config = $config;
		$files = array();
		if ($handle = opendir(INSTALL_PATH)) { 
			while (false!== ($file = readdir($handle))) { 
				//find the predefined data ini file
				if ($file != "." && $file != ".." && $file != "example-external-data.ini" && $file != "example-internal-data.ini" && strtolower(strrchr($file,".")) == '.ini'){  
					$files[] = $file;
				} 
			} 
			closedir($handle); 
		}
		if (isset($files[0])) {
			if (file_exists($files[0])) {
				$this->_predefined = parse_ini_file($files[0], true);
				$this->_isPredefined = true;
			}
		}

    }

    function hasPredefined()
    {
        return $this->_isPredefined;
    }

    function getPredefinedData()
    {
        return $this->_predefined;
    }

    /**
     * Loads a stage based on an array of information.
     * The array should be like this:
     *   name => "Human Readable Name of Stage"
     *   file => "stage"
     *
     * file should be the file the stage's class is stored
     * in, without the .php extension.
     *
     * @access  public
     * @param   array   Information on the stage being loaded.
     * @param   boolean If the function should return the instance for the stage
     *
     * @return  object|bool|Jaws_Error
     */
    function LoadStage($stage, $instance = true)
    {
        $file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'install/stages/' . $stage['file'] . '.php';
		if (!file_exists($file)) {
            Jaws_Error::Fatal('The ' . $stage['name'] . " stage couldn't be loaded, because " . $stage['file'] . ".php doesn't exist.", __FILE__, __LINE__);
        }

        if ($instance) {
            include_once $file;
            $classname = 'Installer_' . $stage['file'];
            $classExists = version_compare(PHP_VERSION, '5.0', '>=') ?
                           class_exists($classname, false) : class_exists($classname);
            if ($classExists) {
                if (isset($stage['options'])) {
                    $stage = new $classname($stage['options']);
                } else {
                    $stage = new $classname;
                }

                return $stage;
            }

            Jaws_Error::Fatal("The ".$stage['name']." stage couldn't be loaded, because the class ".
                              $stage['file']." couldn't be found.", __FILE__, __LINE__);
        }

        $this->Stages[] = $stage;
        return true;
    }

    /**
     * Returns an array containing information about the stages.
     *
     * @access  public
     * @return  array
     */
    function GetStages()
    {
        return $this->Stages;
    }

    /**
     * Loads the list of stages available from a stage list file.
     *
     * @access  public
     * @param   string The file to load the stages from.
     * @return  bool|Jaws_Error
     */
    function LoadStages(&$stages)
    {
        foreach ($stages as $stage) {
            if (isset($stage['name']) && isset($stage['file'])) {
                $this->LoadStage($stage, false);
            }
        }
    }
}
