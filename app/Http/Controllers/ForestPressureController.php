<?php

namespace App\Http\Controllers;

use App\Services\ForestPressureDatasetBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForestPressureController extends Controller
{
    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/forest-pressure/embed-link",
     *     operationId="createForestPressureEmbedLink",
     *     summary="Generar para la web de Moon Group un enlace temporal del dashboard de Presión sobre el Bosque",
     *     description="El dashboard calcula el Índice de Presión sobre el Bosque (IPB V2) con las participaciones finalizadas de la Encuesta de Presión sobre el Bosque.",
     *     tags={"Presión sobre el Bosque"},
     *
     *     @OA\Parameter(name="UUID", in="header", required=true, description="Clave de acceso de la web pública", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="URL firmada y temporal para usar como iframe o abrir en una pestaña"),
     *     @OA\Response(response=401, description="UUID ausente o inválido")
     * )
     */
    public function embedLink(Request $request)
    {
        $expiresAt = now()->addMinutes(
            max(1, min((int) config('forest_pressure.embed_link_ttl_minutes', 30), 120))
        );
        // Firma relativa: sigue siendo válida bajo /moontransparency/public.
        $relativeUrl = URL::temporarySignedRoute('forest-pressure.embed', $expiresAt, [], false);
        $publicBaseUrl = config('forest_pressure.public_url')
            ?: $request->getSchemeAndHttpHost().$request->getBaseUrl();
        $iframeUrl = rtrim($publicBaseUrl, '/').$relativeUrl;

        return response()->json(['data' => [
            'iframe_url' => $iframeUrl,
            'viewer_url' => $iframeUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'methodology' => config('forest_pressure.methodology'),
        ]]);
    }

    public function embed(ForestPressureDatasetBuilder $datasetBuilder)
    {
        $origins = collect(config('forest_pressure.embed_allowed_origins', []))
            ->filter(fn ($origin) => is_string($origin)
                && preg_match('#^https?://[a-z0-9.-]+(?::[0-9]+)?$#i', $origin))
            ->implode(' ');

        return response()
            ->view('presion-bosque', [
                'dataset' => $datasetBuilder->build(),
                'factors' => config('forest_pressure.factors'),
                'levels' => config('forest_pressure.levels'),
                'geobosques' => [
                    'base_url' => rtrim((string) config('geobosques.viewer.base_url'), '?'),
                    'marker_parameter' => (string) config('geobosques.viewer.marker_parameter'),
                ],
            ])
            ->header('Content-Security-Policy', 'frame-ancestors '.trim("'self' ".$origins))
            ->header('Referrer-Policy', 'no-referrer');
    }
}
