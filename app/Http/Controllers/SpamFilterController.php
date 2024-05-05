<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\SpamFilter;

class SpamFilterController extends Controller
{
    
    public function index(Request $request)
    {

    	$spamFilters = SpamFilter::orderBy('created_at', 'DESC')->paginate(10);
    	// dd($spamFilters);

    	return view('spamFilters.index', compact('spamFilters'));

    }

}
