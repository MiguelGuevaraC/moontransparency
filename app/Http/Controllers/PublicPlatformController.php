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
     *     description="Solo expone Herramientas Digitales: Dashboard de Sostenibilidad, Calculadora de CO2 y Presión sobre el Bosque, cada una con nombre, frase y descripción.",
     *     tags={"Portal publico"},
     *
     *     @OA\Parameter(name="UUID", in="header", required=true, description="Clave de acceso de la web publica", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Navegacion publica con las herramientas digitales"),
     *     @OA\Response(response=401, description="UUID ausente o invalido")
     * )
     */
    public function show(Request $request)
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
        $calculatorBaseUrl = rtrim((string) config('co2.calculator_public_url') ?: $baseUrl, '/');
        $forestPressureBaseUrl = rtrim((string) config('forest_pressure.public_url') ?: $baseUrl, '/');

        // El portal solo muestra "Herramientas Digitales"; cada herramienta se
        // presenta como tarjeta y abre su plataforma al hacer clic.
        return response()->json(['data' => [
            'name' => config('platform.public_name'),
            'navigation' => [
                [
                    'code' => 'technological_tools',
                    'name' => 'Herramientas Digitales',
                    'path' => '/herramientas-digitales',
                    'children' => [
                        // El link externo lo define el frontend de forma fija.
                        $this->tool('sustainability_dashboard', [
                            'type' => 'external',
                        ]),
                        $this->tool('co2_calculator', [
                            'type' => 'iframe',
                            'embed_link_endpoint' => $calculatorBaseUrl.'/api/calculator/co2/embed-link',
                        ]),
                        $this->tool('forest_pressure', [
                            'type' => 'iframe',
                            'embed_link_endpoint' => $forestPressureBaseUrl.'/api/forest-pressure/embed-link',
                        ]),
                    ],
                ],
            ],
        ]]);
    }

    private function tool(string $code, array $access): array
    {
        $card = config("platform.tools.$code");

        return [
            'code' => $code,
            'name' => $card['name'],
            'tagline' => $card['tagline'],
            'description' => $card['description'],
        ] + $access;
    }
}
