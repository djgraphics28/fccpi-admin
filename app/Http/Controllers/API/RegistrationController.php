<?php

namespace App\Http\Controllers\API;

use App\Models\Youth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        // Validate all required fields
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'church' => 'required'
        ]);

        // Check for duplicate name and by church
        $existingYouth = Youth::where('first_name', $request->first_name)
                             ->where('last_name', $request->last_name)
                             ->orWhere('church', $request->church)
                             ->first();

        if ($existingYouth) {
            return response()->json([
                'message' => 'A youth with this name already exists',
            ], 422);
        }

        // Registration logic goes here
        Youth::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'church' => $request->church,
        ]);

        // After successful registration, you can return a response
        return response()->json([
            'message' => 'Registration successful',
            'name' => $request->first_name . ' ' . $request->last_name
        ]);
    }
}
