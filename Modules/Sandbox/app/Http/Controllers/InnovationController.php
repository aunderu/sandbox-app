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
    public function getInnovationData($perPage = 3)
    {
        $innovationData = InnovationsModel::with(['school', 'innovationType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $innovationData;
    }

    public function loadMore(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 3;

        $innovationData = InnovationsModel::with(['school', 'innovationType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // ส่งกลับเฉพาะ HTML ของรายการ
        $view = view('sandbox::partials.innovation-items', ['innovationData' => $innovationData])->render();

        return response()->json([
            'html' => $view,
            'next_page' => $innovationData->currentPage() < $innovationData->lastPage() ? $innovationData->currentPage() + 1 : null,
        ]);
    }

}
