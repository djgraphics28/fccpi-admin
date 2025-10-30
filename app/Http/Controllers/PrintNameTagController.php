<?php

namespace App\Http\Controllers;

use App\Models\Youth;
use Illuminate\Http\Request;

class PrintNameTagController extends Controller
{
    public function print(Request $request)
    {
        $ids = $request->input('ids', []);
        $youths = Youth::whereIn('id', $ids)->orderBy('church', 'ASC')->get(['first_name', 'color','church']);

        if ($youths->isEmpty()) {
            return redirect()->back()->with('error', 'No youth selected for printing.');
        }

        return view('print.name-tags', compact('youths'));
    }
}
