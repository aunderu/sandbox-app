<?php

namespace Modules\Sandbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sandbox\Models\InnovationsModel;

class InnovationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getInnovationData()
    {
        $innovations = InnovationsModel::with(['school', 'innovationType'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return $innovations;
    }

    public function index()
    {
        return view('sandbox::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sandbox::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sandbox::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sandbox::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
