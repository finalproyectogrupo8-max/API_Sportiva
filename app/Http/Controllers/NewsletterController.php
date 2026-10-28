<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletters,email',
        ]);

        Newsletter::create($validated);

        return response()->json(['message' => 'Suscripción realizada con éxito'], 201);
    }
}
