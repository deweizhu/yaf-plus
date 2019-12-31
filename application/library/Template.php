<?php

/**
 *  PHP Twig Template
 *
 * @author    Not well-known man
 */
class Template implements Yaf\View_Interface
{
    /** @var \Twig\Loader\Filesystem */
    protected $loader;
    /** @var \Twig\Environment */
    protected $twig;
    protected $variables = array();

    /**
     * @param string $templateDir
     * @param array  $options
     */
    public function __construct($templateDir, array $options = array())
    {
        $this->loader = new \Twig\Loader\FilesystemLoader($templateDir);
        $this->twig = new \Twig\Environment($this->loader, $options);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->variables[$name];
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->variables[$name]);
    }

    /**
     * 获取模板可用变量
     * @return array
     */
    public function variables(): array
    {
        return $this->variables;
    }

    /**
     * Return twig instance
     *
     * @return \Twig\Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * If $name is a k=>v array, it will assign it to template
     *
     * @param string | array $name
     * @param mixed          $value
     *
     * @return bool
     */
    public function assign($name, $value = NULL)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->variables[$k] = $v;
            }
        } else {
            $this->variables[$name] = $value;
        }
    }

    /**
     * @param string     $template
     * @param array|null $variables
     *
     * @return bool
     */
    public function display($template, $variables = NULL)
    {
        echo $this->render($template, $variables);
    }


    /**
     * @return string
     */
    public function getScriptPath()
    {
        $paths = $this->loader->getPaths();
        return reset($paths);
    }

    /**
     * @param string $template
     * @param array  $variables
     *
     * @return string
     */
    public function render($template, $variables = NULL)
    {
        if (is_array($variables)) {
            $this->variables = array_merge($this->variables, $variables);
        }
        return $this->twig->load($template)->render($this->variables);
    }

    /**
     * @param string $templateDir
     *
     * @return void
     */
    public function setScriptPath($templateDir)
    {
        $this->loader->setPaths($templateDir);
    }


    /**
     * 添加全局变量
     *
     * @param $name
     * @param $value
     */
    public function addGlobal($name, $value = '')
    {
        if (is_array($name)) {
            foreach ($name as $k => $v)
                $this->twig->addGlobal($k, $v);
        } else {
            $this->twig->addGlobal($name, $value);
        }
    }

    /**
     * 添加函数
     *
     * @param string      $funName
     * @param string|NULL $InnerFunName
     * @param bool        $needsContext
     */
    public function addFunction(string $funName, string $InnerFunName = NULL, bool $needsContext = FALSE)
    {
        if (strpos($InnerFunName, '::') !== FALSE)
            list($InnerClass, $InnerFunName) = explode('::', $InnerFunName);
        else
            $InnerClass = 'View';
        $InnerFunName === NULL AND $InnerFunName = $funName;
        $this->twig->addFunction(new \Twig\TwigFunction($funName,
            array($InnerClass, $InnerFunName),
            array('needs_context' => $needsContext)
        ));
    }
}