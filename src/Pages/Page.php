<?php namespace Pages;

class Page {
    /**
     * @var string
     */
    public $slug, $title, $value;
    /**
     * @var \Illuminate\View\View
     */
    public $layout;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var callable[]
     */
    protected $requestActions = [
        '_before' => null,
        '_after' => null,
        'get' => null ];
    /**
     * @var array
     */
    protected $requestData = [], $requestParamKeys = [], $requestParams = [];
    /**
     * @var bool
     */
    protected $hide = false, $adminOnly = false;
    /**
     * @var PageGroup
     */
    protected $group;

    public function __construct($slug, $title, $value, $type = 'view', PageGroup $group = null)
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->value = $value;
        $this->type = $type;
        $this->group = $group;
    }

    /**
     * @return null|string
     */
    public function url()
    {
        $url = null;
        if ($this->isType('route'))
            $url = \URL::route($this->value);
        elseif ($this->isType('url'))
            $url = $this->value;
        return $this->group->makePageUrl($this, $url);
    }

    public function before($callable, $params = null)
    {
        $this->addAction('_before', $callable, $params);
        return $this;
    }

    public function after($callable, $params = null)
    {
        $this->addAction('_after', $callable, $params);
        return $this;
    }

    public function onGet($callable, $params = null)
    {
        $this->addAction('get', $callable, $params);
        return $this;
    }

    public function onPost($callable, $params = null)
    {
        $this->addAction('post', $callable, $params);
        return $this;
    }

    public function onPut($callable, $params = null)
    {
        $this->addAction('put', $callable, $params = null);
        return $this;
    }

    public function onDelete($callable, $params = null)
    {
        $this->addAction('delete', $callable, $params);
        return $this;
    }

    /**
     * @param $method
     * @param callable $action
     * @param $params
     * @return $this
     */
    protected function addAction($method, callable $action, $params)
    {
        $this->requestActions[$method] = $action;
        $this->requiredParams($method, $params);
        return $this;
    }

    public function requiredParams($method, $params)
    {
        $methods = is_string($method) ? explode(',', $method) : $method;
        if (is_string($params))
        {
            $params = func_get_args();
            array_shift($params);
        }
        else
            $params = (array) $params;

        foreach ($methods as $method)
        {
            $_params =  array_merge(array_get($this->requestParamKeys, $method, []) , $params);
            $this->requestParamKeys[$method] =  $_params;
        }
        return $this;
    }
    /**
     * @param $method
     * @return mixed
     */
    public function hasAction($method)
    {
        return array_key_exists($method, $this->requestActions);
    }

    /**
     * @param $method
     * @return mixed
     */
    public function getAction($method)
    {
        return $this->requestActions[$method];
    }

    /**
     * @param $method
     * @param array $viewData
     * @param array $params
     * @return mixed
     */
    public function runAction($method, array $viewData = [], array $params = [])
    {
        if (!$this->canRunAction($method, $params))
            return $this->actionNotFound($method);

        if (!$this->group->processBinds($params, $this->requestData))
            return $this->actionNotFound($method);

        $this->requestData = array_merge($viewData, $this->requestData);
        $this->requestParams = $params;

        $result = $this->group->runBeforeCallback($this);
        if ($result) return $result;

        if ($_action = $this->getAction('_before'))
        {
            $result = call_user_func_array($_action, [$this, $params]);
            if ($result) return $result;
        }

        if ($action = $this->getAction($method))
        {
            $result = call_user_func_array($action, [$this, $params]);
            if ($result) return $result;
        }

        if ($_action = $this->getAction('_after'))
        {
            $result = call_user_func_array($_action, [$this, $params]);
            if ($result) return $result;
        }

        $event = ($this->adminOnly() ? 'page.admin:' : 'page:').$this->slug;
        \Event::fire($event.$this->slug, [&$this]);
        $event .= ':'.$method;
        \Event::fire($event.$this->slug, [&$this]);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return array_get($this->requestParams, $key, $default);
    }

    /**
     * @param $slug
     * @param null $group
     * @return string
     */
    public function getPrefixedSlug($slug, $group = null)
    {
        if ($group) return $group .'.'. $slug;
        return $slug;
    }

    /**
     * @param null $value
     * @return bool
     */
    public function adminOnly($value = null)
    {
        if($value) $this->adminOnly = (bool) $value;
        return $this->adminOnly;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function hide($value = true)
    {
        $this->hide = (bool) $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function hidden()
    {
        return $this->hide;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->requestParams;
    }


    /**
     * @param $string
     * @return bool
     */
    public function isType($string)
    {
        return $this->type == $string;
    }

    /**
     * @param $method
     * @param $params
     * @return bool
     */
    public function validParamKeys($method, $params)
    {
        $paramKeys = array_get($this->requestParamKeys, $method, []);
        $missing = array_diff($paramKeys, array_keys($params));
        return empty($missing);
    }

    /**
     * @param $method
     * @param array $params
     * @return bool
     */
    protected function canRunAction($method, array $params)
    {
        return $this->hasAction($method) && $this->validParamKeys($method, $params);
    }

    /**
     * @param $method
     * @throws \Exception
     */
    protected function actionNotFound($method)
    {
        throw new \Exception("Page action not found {$this->title}@{$method}");
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    public function __get($name)
    {
        return array_get($this->requestData, $name, null)
            ?: array_get($this->requestParams, $name, null);
    }

    public function __set($name, $value)
    {
        return array_set($this->requestData, $name, $value);
    }
} 