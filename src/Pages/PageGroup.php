<?php namespace Pages;

use Illuminate\Support\Collection;

class PageGroup  {
    /**
     * @var int
     */
    protected $adminHome, $home;
    /**
     * @var string
     */
    protected $baseView, $title, $slug;
    /**
     * @var Page[]
     */
    protected $pages = [];
    /**
     * @var \stdClass
     */
    protected $handler;
    /**
     * @var callable
     */
    protected $urlMaker, $beforeCallbacks;
    /**
     * @var PageManager
     */
    protected $manager;

    /**
     * @param PageManager $manager
     * @param $slug
     * @param null $title
     */
    public function __construct(PageManager $manager, $slug, $title = null)
    {
        $this->slug = $slug;
        $this->title = $title ?: $slug;
        $this->manager = $manager;
    }

    /**
     * @param $key
     * @param $callback
     * @return $this
     */
    public function bind($key, $callback)
    {
        $this->manager->bind($key, $callback);
        return $this;
    }

    /**
     * @param $params
     * @param array $requestData
     * @return bool
     */
    public function processBinds($params, &$requestData = [])
    {
        return $this->manager->getBinder()->processBinds($params, $requestData);
    }


    /**
     * @param Page $page
     * @param $url
     * @return mixed
     */
    public function makePageUrl($page, $url)
    {
        if ($this->urlMaker)
            return call_user_func($this->urlMaker, $page, $url);
        return $url;
    }

    /**
     * @param $slug
     * @param $title
     * @param $value
     * @param string $type
     * @param bool $isAdmin
     * @return Page
     */
    public function add($slug, $title, $value, $type = 'view', $isAdmin = null)
    {
        if ($this->baseView && $type === 'view')
            $value = $this->baseView.'.'.$value;
        $page = $this->makePage($slug, $title, $value, $type);
        if ($isAdmin) $page->adminOnly($isAdmin);
        if ($slug === 'home')
        {
            $index = count($this->pages);
            if ($isAdmin)
                $this->adminHome = $index;
            else
                $this->home = $index;
        }
        $this->set($page);
        return $page;
    }

    /**
     * @param $slug
     * @param $title
     * @param $value
     * @param string $type
     * @return Page
     */
    public function addAdminPage($slug, $title, $value, $type = 'view')
    {
        return $this->add($slug, $title, $value, $type, true);
    }

    /**
     * @param $slug
     * @param bool $isAdmin
     * @return bool|Page
     */
    public function get($slug, $isAdmin = false)
    {
        foreach ($this->pages as $page) {
            if ($page->slug != $slug) continue;
            if ($page->adminOnly() != (bool) $isAdmin) continue;
            return $page;
        }
        return false;
    }


    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function set($key, $value = null)
    {
        if ($value)
            $this->pages[$key] = $value;
        else
            $this->pages[] = $key;
    }

    /**
     * @param $slug
     * @param bool $isAdmin
     * @return bool|Page
     */
    public function getPage($slug, $isAdmin = false)
    {
        return $this->get($slug, $isAdmin);
    }

    /**
     * @param $slug
     * @return bool|Page
     */
    public function getAdminPage($slug)
    {
        return $this->get($slug, true);
    }

    /**
     * @param bool $admin
     * @return Page
     */
    public function getHome($admin = false)
    {
        $index = ($admin) ? $this->adminHome : $this->home;
        if ($page = array_get($this->pages, $index, false))
            return $page;
    }

    /**
     * @return Page
     */
    public function getAdmin()
    {
        return $this->getHome(true);
    }

    /**
     * @param $view
     * @return $this
     */
    public function setBaseView($view)
    {
        $this->baseView = $view;
        return $this;
    }

    /**
     * @param callable $urlMaker
     * @return $this
     */
    public function setUrlMaker($urlMaker)
    {
        $this->urlMaker = $urlMaker;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseView()
    {
        return $this->baseView;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->pages);
    }

    /**
     * @return Collection|Page[]
     */
    public function getPages()
    {
        return new Collection($this->pages);
    }

    /**
     * @return Collection|Page[]
     */
    public function getVisiblePages()
    {
        return $this->getPages()->filter(function($page){
            return !$page->hidden();
        });
    }

    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    public function runBeforeCallback(Page $page)
    {
        if ($this->beforeCallbacks)
        {
            foreach ($this->beforeCallbacks as $callback)
            {
                $result = call_user_func($callback, $page);
                if (!is_null($result)) return $result;
            }
        }
    }

    /**
     * @return \Pages\PageManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param $slug
     * @param $title
     * @param $value
     * @param $type
     * @return Page
     */
    protected function makePage($slug, $title, $value, $type)
    {
        return new Page($this, $slug, $title, $value, $type);
    }
} 