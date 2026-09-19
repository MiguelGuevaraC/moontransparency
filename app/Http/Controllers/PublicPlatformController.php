<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicPlatformController extends Controller
{
    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/platform/public",
     *     operationId="publicPlatformConfiguration",
     *     summary="Obtener la identidad y navegacion del Portal de Impacto Moon Group",
     *     tags={"Portal publico"},
     *
     *     @OA\Parameter(name="UUID", in="header", required=true, description="Clave de acceso de la web publica", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Navegacion publica y herramientas tecnologicas"),
     *     @OA\Response(response=401, description="UUID ausente o invalido")
     * )
     */
    public function show(Request $request)
    {
        $calculatorBaseUrl = (string) config('co2.calculator_public_url');
        if ($calculatorBaseUrl === '') {
            $calculatorBaseUrl = $request->getSchemeAndHttpHost().$request->getBaseUrl();
        }
        $dashboardUrl = config('platform.sustainability_dashboard_url');

        return response()->json(['data' => [
            'name' => config('platform.public_name'),
            'navigation' => [
                [
                    'code' => 'projects',
                    'name' => 'Proyectos',
                    'path' => '/proyectos',
                ],
                [
                    'code' => 'contact',
                    'name' => 'Contáctanos',
                    'path' => '/contactanos',
                ],
                [
                    'code' => 'technological_tools',
                    'name' => 'Herramientas tecnológicas',
                    'children' => [
                        [
                            'code' => 'sustainability_dashboard',
                            'name' => 'Dashboard de Sostenibilidad',
                            'type' => 'external',
                            'url' => $dashboardUrl,
                            'configured' => filled($dashboardUrl),
                        ],
                        [
                            'code' => 'co2_calculator',
                            'name' => 'Calculadora de CO2',
                            'type' => 'iframe',
                            'embed_link_endpoint' => rtrim($calculatorBaseUrl, '/').'/api/calculator/co2/embed-link',
                        ],
                    ],
                ],
            ],
        ]]);
    }
}
