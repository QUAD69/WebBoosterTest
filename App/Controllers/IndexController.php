<?php

namespace App\Controllers;

use App\Core\Mvc\Controller;
use App\Models\Catalog;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->view->title = 'Webbooster - тестовое задание';
        $this->view->catalog = Catalog::find("`parent_id` IS NULL");
        $this->view->render('Index');
    }
}