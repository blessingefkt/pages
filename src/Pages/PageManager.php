<?php namespace Pages;

use Iyoworks\Support\Str;

class PageManager  {
    protected $items = [];
    /**
     * @var ParameterBinder
     */
    protected $binder;

    public function __construct(ParameterBinder $binder)
    {
        $this->binder = $binder;
    }

    /**
     * @param $page
     * @param null $group
     * @param bool $admin
     * @return \Tespa\Service\Pages\Page|bool
     */
    public function find($page, $group = null, $admin = false)
    {
        if (!$group && str_contains($page, '.'))
            list ($group, $page) = explode('.', $page, 1);

        if ($group)  return $this->group($group)->getPage($page, $admin);
        return false;
    }

    /**
     * @param $page
     * @param null $group
     * @return \Tespa\Service\Pages\Page|bool
     */
    public function findAdmin($page, $group = null)
    {
        return $this->find($page, $group, true);
    }

    /**
     * @param $page
     * @param null $group
     * @param bool $admin
     * @throws \Exception
     * @return \Tespa\Service\Pages\Page|bool
     */
    public function findOrFail($page, $group = null, $admin = false)
    {
        if ($page = $this->find($page, $group, $admin))
            return $page;
        $this->pageNotFound($page);
    }

    /**
     * @param $page
     * @param null $group
     * @return bool|Page
     */
    public function findAdminOrFail($page, $group = null)
    {
        return $this->findOrFail($page, $group, true);
    }

    /**
     * @param $name
     * @param null $slug
     * @return \Tespa\Service\Pages\PageGroup
     */
    public function add($name, $slug = null)
    {
        $slug = $slug ?: Str::slug($name);
        $page = new PageGroup($this, $slug, $name);
        return $this->addGroup($slug, $page);
    }

    /**
     * @param $slug
     * @return \Tespa\Service\Pages\PageGroup|null
     */
    public function group($slug)
    {
        if ($slug && $this->has($slug))
            return $this->items[$slug];
        return $this->add($slug);
    }

    /**
     * @param $slug
     * @param \Tespa\Service\Pages\PageGroup $group
     * @return \Tespa\Service\Pages\PageGroup
     */
    public function addGroup($slug, PageGroup $group)
    {
        return $this->items[$slug] = $group;
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param $key
     * @param $callback
     * @param null $alias
     */
    public function bind($key, $callback, $alias = null)
    {
        $this->binder->bind($key, $callback, $alias);
    }

    /**
     * @return ParameterBinder
     */
    public function getBinder()
    {
        return $this->binder;
    }

    /**
     * @param $page
     * @throws \Exception
     */
    protected function pageNotFound($page)
    {
        throw new \Exception("Page not found ($page)");
    }
} 