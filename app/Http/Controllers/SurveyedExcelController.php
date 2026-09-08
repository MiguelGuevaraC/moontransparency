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
        $rows = $this->excelService->exportRows($request->input('response_text'));

        return response($this->excelService->toHtml($rows), 200)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename=encuestas_respuestas.xls');
    }

    public function import(Request $request)
    {
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
