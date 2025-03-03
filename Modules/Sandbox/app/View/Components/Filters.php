<?php

namespace Modules\Sandbox\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Modules\Sandbox\Models\SchoolModel;

class Filters extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        // Debugging: Ensure this line is reached
        // dd('Component render method called');

        $schools = SchoolModel::orderBy('school_name_th')->get();
        return view('sandbox::components.filters', compact('schools'));
    }
}
