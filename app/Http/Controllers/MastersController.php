<?php

namespace App\Http\Controllers;

class MastersController extends Controller
{
    /**
     * Display the CRM masters index page.
     */
    public function index()
    {
        return view('pages.masters.index');
    }

    /**
     * Display the HRMS masters index page.
     */
    public function hrmsIndex()
    {
        return view('pages.hrms.masters.index');
    }
}
