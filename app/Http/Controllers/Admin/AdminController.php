<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Category;

class AdminController extends Controller
{
    public function dashboard()
    {
        $users = User::count();
        #$coursesCount = Course::count();
        #$categoriesCount = Category::count();

        return view('admin.dashboard', compact('users'));
    }
}
