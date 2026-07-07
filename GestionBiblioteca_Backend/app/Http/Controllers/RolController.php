<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Rol::all()]);
    }
}