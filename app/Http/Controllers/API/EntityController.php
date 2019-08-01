<?php

namespace App\Http\Controllers\API;

use App\Entity;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;

class EntityController extends Controller
{
    public function getAll()
    {
        $entities = Entity::orderBy("name", "desc")->get();
        return response()->json(['data' => $entities], HttpStatus::SUCCESS);
    }
}
