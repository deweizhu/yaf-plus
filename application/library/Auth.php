<?php defined('SYSPATH') OR die('No direct access allowed.');

class Auth 
{ 
    /**
     * Application
     * @var \Illuminate\Foundation\Application
     */
    protected $app;
    
    /**
     * Configuration
     * @var array
     */
    protected $config = [];
    
    /**
     * Registered providers
     * @var array
     */
    protected $providers = [];
    
    private static $_instance;
    
    /**
     * 单例模式
     * @return self
     */
    public static function instance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }
    
    /**
     * Here we are collecting all the providers from config
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct()
    {
        $this->config = Yaf_Application::app()->getConfig()->get('auth');
    	if ( ! $type = $this->config->get('driver'))
    	{
    		$type = 'database';
    	}
    
    	// Set the session class name
    	$class = 'Auth_'.ucfirst($type);
    	
        if(!empty($this->config)) {
            foreach($this->config as $key => $config) {
                $this->providers[$key] = new $class($config,$key);
            }
        }
    }
    
    /**
     * Here we are calling the provider
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments = [])
    {
        if(array_key_exists($name, $this->providers)) {
            return $this->providers[$name];
        } else {
            if(!empty($this->providers)) {
                foreach($this->providers AS $provider) {
                    if($provider->$name() !== null) {
                        return $provider->$name();
                    }
                }
            }
        }
    }    
}