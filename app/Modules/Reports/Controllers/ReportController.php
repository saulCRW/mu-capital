<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Reports\Requests\QuarterlyReportRequest;
use App\Modules\Reports\Services\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function trimestral(QuarterlyReportRequest $request, Portfolio $portfolio): View
    {
        return view('reports.trimestral', [
            'portfolio' => $portfolio->load('client:id,nombre', 'benchmark:id,nombre', 'organization:id,nombre'),
            'reporte' => $this->service->trimestral($portfolio, (int) $request->validated('anio'), (int) $request->validated('trimestre')),
        ]);
    }
}
