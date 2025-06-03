<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    public function index()
    {
        return response()->file(public_path('swagger-ui/index.html'));
    }

    public function json()
    {
        $json = file_get_contents(storage_path('api-docs/api-docs.json'));
        return response($json, 200, ['Content-Type' => 'application/json']);
    }
}