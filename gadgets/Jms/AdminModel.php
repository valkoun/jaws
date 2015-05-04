<?php
/**
 * JMS (Jaws Management System) Gadget
 *
 * @category   GadgetModel
 * @package    JMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi �ormar <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class JmsAdminModel extends Jaws_Model
{
    var $_Name = 'Jms';

    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        $GLOBALS['app']->Registry->NewKey('/gadgets/Jms/pluggable', 'false');
        return true;
    }

    /**
     * Get a list of gadgets, installed or non installed, core or not core, has layout or not,...
     *
     * @access  public
     * @param   boolean $core_gadget accept true/false/null value
     * @param   boolean $installed   accept true/false/null value
     * @param   boolean $updated     accept true/false/null value
     * @param   boolean $has_layout  accept true/false/null value
     * @return  array   A list of gadgets
     */
    function GetGadgetsList($core_gadget = null, $installed = null, $updated = null, $has_layout = null)
    {
        //TODO: implementing cache for this method
        static $gadgetsList;
        if (!isset($gadgetsList)) {
            $gadgetsList = array();
            $gDir = JAWS_PATH . 'gadgets' . DIRECTORY_SEPARATOR;
            if (!is_dir($gDir)) {
                Jaws_Error::Fatal('The gadgets directory does not exists!', __FILE__, __LINE__);
            }

            $gadgets = scandir($gDir);
            foreach ($gadgets as $gadget) {
                if ($gadget{0} == '.' || !is_dir($gDir . $gadget)) {
                    continue;
                }

                $gInfo = $GLOBALS['app']->LoadGadget($gadget, 'Info');
                if (Jaws_Error::IsError($gInfo)) {
                    continue;
                }

                $gInstalled = Jaws_Gadget::IsGadgetInstalled($gadget);
                if ($gInstalled) {
                    $gUpdated = Jaws_Gadget::IsGadgetUpdated($gadget);
                } else {
                    $gUpdated = true;
                }

                $gadgetsList[$gadget] = array('realname'    => $gadget,
                                              'name'        => $gInfo->GetName(),
                                              'core_gadget' => (bool)$gInfo->GetAttribute('core_gadget'),
                                              'description' => $gInfo->GetDescription(),
                                              'version'     => $gInfo->GetVersion(),
                                              'installed'   => (bool)$gInstalled,
                                              'updated'     => (bool)$gUpdated,
                                              'has_layout'  => file_exists($gDir . $gadget . DIRECTORY_SEPARATOR . 'LayoutHTML.php'),
                                             );
            }
        }

        $resList = array();
        foreach ($gadgetsList as $name => $gadget) {
            if ((is_null($core_gadget) || $gadget['core_gadget'] == $core_gadget) &&
                (is_null($installed) || $gadget['installed'] == $installed) &&
                (is_null($updated) || $gadget['updated'] == $updated) &&
                (is_null($has_layout) || $gadget['has_layout'] == $has_layout))
            {
                $resList[$name] = $gadget;
            }
        }

        return $resList;
    }

    /**
     * Get a list of plugins, installed or non installed
     *
     * @access  public
     * @param   boolean $installed   accept true/false/null value
     * @return  array   A list of plugins
     */
    function GetPluginsList($installed = null)
    {
        //TODO: implementing cache for this method
        static $pluginsList;
        if (!isset($pluginsList)) {
            $pluginsList = array();
            $pDir = JAWS_PATH . 'plugins' . DIRECTORY_SEPARATOR;
            if (!is_dir($pDir)) {
                Jaws_Error::Fatal('The plugins directory does not exists!', __FILE__, __LINE__);
            }

            $plugins = scandir($pDir);
            foreach ($plugins as $plugin) {
                if ($plugin{0} == '.' || !is_dir($pDir . $plugin)) {
                    continue;
                }

                $ei = explode(',', $GLOBALS['app']->Registry->Get('/plugins/parse_text/enabled_items'));
                $ei = str_replace(' ', '', $ei);
                $pInstalled = in_array($plugin, $ei);

                $pluginsList[$plugin] = array('realname'  => $plugin,
                                              'name'      => $plugin,
                                              'installed' => (bool)$pInstalled,
                                             );
            }
        }

        $resList = array();
        foreach ($pluginsList as $name => $plugin) {
            if (is_null($installed) || $plugin['installed'] == $installed) {
                $resList[$name] = $plugin;
            }
        }

        return $resList;
    }

    /**
     * Get Information of a plugin
     *
     * @access  public
     * @param   string  $plugin Plugin
     * @return  array   Plugin information
     */
    function GetPluginInfo($plugin)
    {
        $plugin_file = JAWS_PATH . '/plugins/' . $plugin . '/' . $plugin . '.php';
        if (file_exists($plugin_file)) {
            require_once $plugin_file;
            $p = new $plugin();
            $plugin = array(
                'version'     => $p->GetVersion(),
                'realname'    => $plugin,
                'name'        => $plugin,
                'friendly'    => $p->IsFriendly(),
                'accesskey'   => $p->GetAccessKey(),
                'example'     => $p->GetExample(),
                'description' => _t('_PLUGINS_' . strtoupper($plugin) . '_DESCRIPTION'),
            );

            return $plugin;
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetPluginInfo'), _t('JMS_NAME'));
    }


    /**
     * Get the registry keys of a gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget keys to fetch
     * @return  array   Array with registry keys
     */
    function GetGadgetRegistryKeys($gadget)
    {
        $rs = array();
        $fileKeys = $GLOBALS['app']->Registry->LoadFile($gadget, 'gadgets', true);
        foreach ($fileKeys as $key => $value) {
            if (!isset($rs[$key])) {
                $rs[] = array(
                    'name'  => $key,
                    'value' => $value,
                );
            }
        }

        return $rs;
    }

    /**
     * Get the ACL keys of a gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget keys to fetch
     * @return  array   Array with registry keys
     */
    function GetGadgetACLKeys($gadget)
    {
        $rs = array();
        $fileKeys = $GLOBALS['app']->ACL->LoadFile($gadget, 'gadgets', true);
        foreach ($fileKeys as $key => $value) {
            $rs[] = array(
                'name'  => $key,
                'value' => $value,
            );
        }

        return $rs;
    }
}