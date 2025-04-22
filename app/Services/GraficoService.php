<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Ally;
use App\Models\Indicator;
use App\Models\Proyect;
use App\Utils\Constants;
use Illuminate\Support\Facades\DB;

class GraficoService
{

    /**
     * 📈 Calcula el avance del proyecto.
     */
    public function total_projects()
    {
        return DB::table('proyects')
            ->whereNull('deleted_at')
            ->count('nro_beneficiaries');
    }
    public function sum_beneficiaries()
    {
        return DB::table('proyects')
            ->whereNull('deleted_at')
            ->sum('nro_beneficiaries');
    }
    public function sum_budget_estimated()
    {
        return DB::table('proyects')
            ->whereNull('deleted_at')
            ->sum('budget_estimated');
    }
    public function calculateProgress()
    {
        return (
            Proyect::with('activities:id,proyect_id,status')->latest()->take(6)->orderBy('id', 'desc')->get()
                ->map(fn($p) => [
                    'name'    => $p->name,
                    'percent' => $p->activities->count()
                    ? round($p->activities->sum(fn($a) =>
                        [Constants::STATUS_ACTIVITY_PENDIENTE   => 0,
                            Constants::STATUS_ACTIVITY_EN_EJECUCION => 50,
                            Constants::STATUS_ACTIVITY_FINALIZADO   => 100,
                        ][$a->status] ?? 0) / $p->activities->count(), 2) . ''
                    : '0',
                ])
        );
    }

    /**
     * 🌍 Obtiene el estado financiero del proyecto.
     */
    public function getFinancialStatus()
    {
        $totales = Proyect::withSum('activities', 'total_amount')
            ->withSum('activities', 'collected_amount')
            ->get();

        $montoTotalPresupuesto = $totales->sum('activities_sum_total_amount');
        $montoRecaudado        = $totales->sum('activities_sum_collected_amount');
        $montoFaltante         = max(0, $montoTotalPresupuesto - $montoRecaudado);

        return [
            'total_budget_amount' => $montoTotalPresupuesto,
            'collected_amount'    => $montoRecaudado,
            'remaining_amount'    => $montoFaltante,
        ];

    }

    /**
     * 🤝 Obtiene las donaciones agrupadas por tipo de contribución.
     */
    public function getDonationsByType()
    {
        return Ally::with('donations:id,ally_id,contribution_type,amount')
            ->latest()->take(6)->get()
            ->map(fn($p) => [
                'names'         => "{$p->first_name} {$p->last_name}",
                'business_name' => $p->business_name,
                'ruc_dni'       => $p->ruc_dni,
                'donations'     => collect(Constants::CONTRIBUTION_TYPES)
                    ->mapWithKeys(fn($type) =>
                        [$type => $p->donations->where('contribution_type', $type)->sum('amount'),
                        ]),
            ]);
    }

    public function indicators_by_proyect($id)
    {
        return Indicator::where('proyect_id', $id)->select('indicator_name','progress_value', 'target_value')->get();
    }

    public function activities_progress($id)
    {
        return Activity::where('proyect_id', $id)
            ->take(6)
            ->get(['name', 'total_amount', 'collected_amount'])
            ->map(fn($a) => [
                'name'             => $a->name ?? 'N/A', // Asegura que name tenga valor
                'total_amount'     => $a->total_amount ?? 0,
                'collected_amount' => $a->collected_amount ?? 0,
                'progress'         => $a->total_amount > 0 ? round(($a->collected_amount * 100) / $a->total_amount, 2) : '0',
            ]);
    }

    public function activities_status($id)
    {
        return Activity::where('proyect_id', $id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->union([
                Constants::STATUS_ACTIVITY_PENDIENTE    => 0,
                Constants::STATUS_ACTIVITY_EN_EJECUCION => 0,
                Constants::STATUS_ACTIVITY_FINALIZADO   => 0,
            ]);
    }

}
