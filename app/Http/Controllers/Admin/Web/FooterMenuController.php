<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Admin\Web\Concerns\ManagesSiteMenus;
use App\Http\Controllers\Controller;

class FooterMenuController extends Controller
{
    use ManagesSiteMenus;

    protected string $title;
    protected string $route;
    protected string $view;
    protected string $access;
    protected string $location;

    public function __construct()
    {
        $this->title = 'Footer Menu';
        $this->route = 'admin.footer-menu';
        $this->view = 'admin.web.site-menu';
        $this->access = 'footer-menu';
        $this->location = 'footer';

        $this->middleware(
            'permission:'.$this->access.'-view|'.$this->access.'-create|'.$this->access.'-edit|'.$this->access.'-delete',
            ['only' => ['index', 'show']]
        );
        $this->middleware('permission:'.$this->access.'-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:'.$this->access.'-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.$this->access.'-delete', ['only' => ['destroy']]);
    }
}
