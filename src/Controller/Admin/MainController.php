<?php

namespace App\Controller\Admin;

use App\Controller\Controller;

class MainController extends Controller
{
    public function dashboard()
    {
        $this->checkAuth(false, false);
        return $this->render('panel/dashboard.html.twig', []);
    }
}