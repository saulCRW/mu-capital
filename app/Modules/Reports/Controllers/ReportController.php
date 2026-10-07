<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Reports\Requests\QuarterlyReportRequest;
use App\Modules\Reports\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    public function trimestral(QuarterlyReportRequest $request, Portfolio $portfolio): View
    {
        return view('reports.trimestral', $this->datos($request, $portfolio));
    }

    /** El mismo reporte en PDF (dompdf), tamaño carta. */
    public function pdf(QuarterlyReportRequest $request, Portfolio $portfolio): Response
    {
        $datos = $this->datos($request, $portfolio);
        $archivo = sprintf('reporte-%s-%dT%d.pdf', Str::slug($portfolio->nombre), $datos['reporte']['trimestre'], $datos['reporte']['anio']);

        return Pdf::loadView('reports.trimestral', $datos + ['pdf' => true])->setPaper('letter')->download($archivo);
    }

    /** @return array{portfolio: Portfolio, reporte: array<string, mixed>} */
    private function datos(QuarterlyReportRequest $request, Portfolio $portfolio): array
    {
        return [
            'portfolio' => $portfolio->load('benchmark:id,nombre', 'user:id,nombre'),
            'reporte' => $this->service->trimestral($portfolio, (int) $request->validated('anio'), (int) $request->validated('trimestre')),
        ];
    }
}
