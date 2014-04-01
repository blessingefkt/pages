<?php namespace Pages;

class ParameterBinder {

    protected $binders = [];

    public function bind($key, $callback, $alias = null)
    {
        if (str_contains($key, ':'))
            list($key, $alias) = explode(':', $key);
        if (!$alias) $alias = $key;
        $this->binders[$key] = [$callback, $alias];
    }

    /**
     * @param $params
     * @param array $bindings
     * @return bool
     */
    public function processBinds($params, array &$bindings = [])
    {
        $matchedBinds = array_intersect_key($this->binders, $params);
        foreach ($matchedBinds as $param => $binding)
        {
            list($callback, $alias) = $binding;
            $key = $params[$param];
            $obj = null;
            if (is_string($callback))
                $obj = \App::make($callback)->find($key);
            else
                $obj = call_user_func($callback, $key);
            if (is_null($obj)) return false;

            $bindings[$alias] = $obj;
        }
        return true;
    }

} 