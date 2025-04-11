<?php
namespace App\Http\Controllers;

use App\Services\GraficoService;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="DashboardResumen",
 *     type="object",
 *     @OA\Property(property="headers", type="object",
 *         @OA\Property(property="sum_beneficiaries", type="integer", example=10000),
 *         @OA\Property(property="sum_budget_estimated", type="number", example=800000.00)
 *     ),
 *     @OA\Property(property="graphics", type="object",
 *         @OA\Property(property="project_progress", type="array", @OA\Items(
 *             @OA\Property(property="name", type="string", example="Proyecto Ejemplo"),
 *             @OA\Property(property="percent", type="integer", example=50)
 *         )),
 *         @OA\Property(property="financial_status", type="object",
 *             @OA\Property(property="total_budget_amount", type="number", example=605000),
 *             @OA\Property(property="collected_amount", type="number", example=203500),
 *             @OA\Property(property="remaining_amount", type="number", example=401500)
 *         ),
 *         @OA\Property(property="donations_by_type", type="array", @OA\Items(
 *             @OA\Property(property="names", type="string", example="Juan Pérez"),
 *             @OA\Property(property="business_name", type="string", example="Tecnologías Avanzadas S.A."),
 *             @OA\Property(property="ruc_dni", type="string", example="12345678901"),
 *             @OA\Property(property="donations", type="object",
 *                 @OA\Property(property="DINERO", type="integer", example=2000),
 *                 @OA\Property(property="RECURSOS", type="integer", example=1000),
 *                 @OA\Property(property="SERVICIOS", type="integer", example=0)
 *             )
 *         ))
 *     )
 * )
 */

 

class GraficosApiController extends Controller
{
    /**
 * @OA\Schema(
 *     schema="DashboardByProject",
 *     type="object",
 *     @OA\Property(property="indicators", type="array", @OA\Items(
 *         @OA\Property(property="progress_value", type="string", example="500.00"),
 *         @OA\Property(property="target_value", type="string", example="1000.00")
 *     )),
 *     @OA\Property(property="activities", type="object",
 *         @OA\Property(property="by_progress", type="array", @OA\Items(
 *             @OA\Property(property="name", type="string", example="Plan de Capacitación"),
 *             @OA\Property(property="total_amount", type="string", example="200000.00"),
 *             @OA\Property(property="collected_amount", type="string", example="200000.00"),
 *             @OA\Property(property="progress", type="integer", example=100)
 *         )),
 *         @OA\Property(property="by_status", type="object",
 *             @OA\Property(property="PENDIENTE", type="integer", example=3),
 *             @OA\Property(property="EN EJECUCION", type="integer", example=0),
 *             @OA\Property(property="FINALIZADO", type="integer", example=0)
 *         )
 *     )
 * )
 */
    private $graficoService;

    public function __construct(GraficoService $graficoService)
    {
        $this->graficoService = $graficoService;
    }

    // 📈 Gráfico 1: Avance del Proyecto
    public function avanceProyecto()
    {
        
        $data = $this->graficoService->calculateProgress();
        return response()->json(['data' => $data]);
    }

    // 🌍 Gráfico 2: Estado Financiero Global del Proyecto
    public function estadoFinanciero()
    {
        $estado = $this->graficoService->getFinancialStatus();
        return response()->json(['data' => $estado]);
    }

    // 🤝 Gráfico 3: Donaciones por Aliados (Agrupadas por Tipo de Contribución)
    public function donacionesPorTipo()
    {
        $donaciones = $this->graficoService->getDonationsByType();
        return response()->json(['data' => $donaciones]);
    }

    /**
     * @OA\Get(
     *     path="/moon_transparency/public/api/dashboard",
     *     summary="Resumen del dashboard",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Resumen del dashboard", @OA\JsonContent(ref="#/components/schemas/DashboardResumen")),
     *     @OA\Response(response=401, description="No autorizado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Unauthorized")))
     * )
     */


    public function dashboard_resumen(Request $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        return response()->json([
            "headers"  => [
                'total_projects'    => $this->graficoService->total_projects(),
                'sum_beneficiaries'    => $this->graficoService->sum_beneficiaries(),
                'sum_budget_estimated' => $this->graficoService->sum_budget_estimated(),
            ],
            "graphics" => [
                'project_progress'  => $this->graficoService->calculateProgress(),
                'financial_status'  => $this->graficoService->getFinancialStatus(),
                'donations_by_type' => $this->graficoService->getDonationsByType(),
            ],

        ]);
    }

    /**
 * @OA\Get(
 *     path="/moon_transparency/public/api/dashboard-by-project/{id}",
 *     summary="Resumen del dashboard por proyecto",
 *     tags={"Dashboard"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID del proyecto",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(response=200, description="Resumen del proyecto", @OA\JsonContent(ref="#/components/schemas/DashboardByProject")),
 *     @OA\Response(response=404, description="Proyecto no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Proyecto no encontrado")))
 * )
 */



    public function dashboard_by_project(Request $request,$id)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        return response()->json([
            'indicators' => $this->graficoService->indicators_by_proyect($id),
            'activities' => [
                "by_progress" => $this->graficoService->activities_progress($id),
                "by_status"   => $this->graficoService->activities_status($id),
            ],
        ]);
    }
}
