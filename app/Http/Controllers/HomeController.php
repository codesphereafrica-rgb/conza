<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('ordering')
            ->get();

        $latestTopics = Topic::with(['category', 'user'])
            ->latest()
            ->take(5)
            ->get();

        return view('home', compact('categories', 'latestTopics'));
    }
}
