<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyedRequest\IndexSurveyedRequest;
use App\Services\SurveyedExcelService;
use Illuminate\Http\Request;

class SurveyedExcelController extends Controller
{
    public function __construct(private SurveyedExcelService $excelService)
    {
    }

    public function export(IndexSurveyedRequest $request)
    {
        abort_if(
            $request->user()?->isSurveyor(),
            403,
            'Los encuestadores no pueden descargar el historial en Excel.'
        );

        $rows = $this->excelService->exportRows(
            $request->input('response_text'),
            $request->user()
        );

        return response($this->excelService->toHtml($rows), 200)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename=encuestas_respuestas.xls');
    }

    public function import(Request $request)
    {
        abort_if(
            $request->user()?->isSurveyor(),
            403,
            'Los encuestadores no pueden importar encuestas desde Excel.'
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx'],
        ]);

        $result = $this->excelService->import($request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Importación completada correctamente.',
            'actualizados' => $result['responses_updated'],
            'opciones_actualizadas' => $result['options_updated'],
        ]);
    }
}
