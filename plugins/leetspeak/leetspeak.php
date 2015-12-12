<?php
/**
 * GSManager
 *
 * This is a mighty and platform independent software for administrating game servers of various kinds.
 * If you need help with installing or using this software, please visit our website at: www.gsmanager.de
 * If you have licensing enquiries e.g. related to commercial use, please contact us at: sales@gsmanager.de
 *
 * @copyright Greenfield Concept UG (haftungsbeschränkt)
 * @license GSManager EULA <https://www.gsmanager.de/eula.php>
 * @version 1.1.0
**/

namespace GSM\Plugins\Leetspeak;

use GSM\Daemon\Core\Utils;

/**
 * Leetspeak plugin
 *
 * replaces server messages with custom chars (leetspeak for example)
 *
 */
class Leetspeak extends Utils {

    /**
     * The replacement level. 
     * 
     * 0 means no replacements 5 means max replacements.
     * The levels are defined in the levels.json file in this plugin folder
     * 
     * @var int
     */
    private $replacement_level = 0;
    
    /*
     * Contents of the levels file to avoid file access on every server message
     */
    private $replacement_array = array();

    /**
     * Inits the plugin
     *
     * This function initiates the plugin. This means that it register commands
     * default values, and events. It's important that every plugin has this function
     * Otherwise the plugin exists but can't be used
     */
    public function initPlugin() {
        parent::initPlugin();

        $this->config->setDefault('leetspeak', 'enabled', false);
        $this->config->setDefault('leetspeak', 'level', 0);
        $this->config->setDefault('leetspeak', 'ignorecase', true);
        $this->config->setDefault('leetspeak', 'customchars', []);
        $this->replacement_array = \GSM\Daemon\Libraries\Helper\Helper::parseJson('plugins/leetspeak/levels.json', false, false);
    }

    /**
     * Enables this module.
     *
     * This function is called every time the plugin get enabled.
     *
     * In this function you should call functions like registerCommand, registerEvent, registerHook, addPeriodicJob, addEveryTimeJob, addCronJob.
     *
     * Never call this method on your own, only PluginLoader should do this to enable dependent plugins, too.
     * Use $this->pluginloader->enablePlugin($namespace) instead.
     */
    public function enable() {
        parent::enable();
        
        $this->hooks->register('preRconSay', [$this, 'replaceMessage']);
        $this->hooks->register('preRconTell', [$this, 'replaceMessage']);
    }

    /**
     * Disables this module.
     *
     * In this function you should call functions like unregisterCommand, unregisterEvent, unregisterHook, deleteJob.
     *
     * Never call this method on your own, only PluginLoader should do this to disable dependent plugins, too.
     * Use $this->pluginloader->disablePlugin($namespace) instead.
     */
    public function disable() {
        parent::disable();

        $this->hooks->unregister('preRconSay', [$this, 'replaceMessage']);
        $this->hooks->unregister('preRconTell', [$this, 'replaceMessage']);
    }
    
    
    /**
     * Replaces the server messages with custom chars
     * 
     * @param string    $message   The rconSay or rconTell message
     * @param int       $pid       The pid of a player
     * @return boolean  true if all is fine|false if you want to block this message
     */
    public function replaceMessage(&$message, &$pid = -1){
        if($this->replacement_level != $this->config->get('leetspeak', 'level')){
            $this->replacement_level = $this->config->get('leetspeak', 'level');
        }

        $level_rules = array();
        if(array_key_exists("level_". $this->replacement_level, $this->replacement_array)){
            $level_rules = $this->replacement_array["level_" . $this->replacement_level];
        }

        $custom_rules = $this->config->get('leetspeak', 'customchars');

        $to_replace = array_merge($level_rules, $custom_rules);

        if($this->config->get('leetspeak', 'ignorecase')){
            $to_replace = array_merge($to_replace,array_change_key_case($to_replace, CASE_UPPER));
        }

        /* Not possible to use str_replace here due the "Replacement order gotcha" 
           @see http://php.net/manual/en/function.str-replace.php#refsect1-function.str-replace-notes
        */
        $message = strtr($message, $to_replace);

        return true;
    }
}
