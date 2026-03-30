<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Services\TargetListService;

class TargetController extends Controller
{
    public function index(TargetListService $service)
    {
        $user = auth()->user();

        $targets = $service->getForDelegate($user->id);

        return view('field.targets.index', compact('targets'));
    }
}
