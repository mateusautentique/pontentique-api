<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(['message' => __CLASS__ . '@' . __FUNCTION__ .':'.__LINE__]);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => __CLASS__ . '@' . __FUNCTION__ .':'.__LINE__]);
    }

    public function show($id)
    {
        return response()->json(['message' => __CLASS__ . '@' . __FUNCTION__ .':'.__LINE__]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => __CLASS__ . '@' . __FUNCTION__ .':'.__LINE__]);
    }

    public function destroy($id)
    {
        return response()->json(['message' => __CLASS__ . '@' . __FUNCTION__ .':'.__LINE__]);
    }
}
