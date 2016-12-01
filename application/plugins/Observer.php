<?php

/**
 *  SPL Observer
 *
 * @author: ZDW
 */
class ObserverPlugin implements SplObserver
{
    private $config = array(), $class = NULL;
    private static $plugin = array();

    public function __construct($class)
    {
        $plugin_dir = str_replace(array('Model', '_'), array('', '/'), $class);
        $dir = APPPATH . '/plugins/' . $plugin_dir . '/';
        if (is_file($dir . 'Config.php')) {
            $this->class = strstr($class, 'Model', TRUE);
            $config = include($dir . 'Config.php');
            foreach ($config as $file => $events) {
                foreach ($events as $event) {
                    $this->config[$event][] = $file;
                }
            }
        }
    }

    /**
     * 接收更新
     * @param SplSubject $subject
     * @return bool
     */
    public function update(SplSubject $subject): bool
    {
        $event = $subject->event;
        if (empty($this->config[$event])) {
            return FALSE;
        }
        foreach ($this->config[$event] as $file) {
            $class = $this->class . '_' . ucfirst($file) . 'Plugin';
            if (!isset(self::$plugin[$file])) {
                self::$plugin[$file] = new $class($subject);
            }
            self::$plugin[$file]->$event();
        }
        return TRUE;
    }
}