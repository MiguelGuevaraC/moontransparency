<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Menu",
 *     type="object",
 *     required={"id", "code", "name", "path", "sort_order", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=13),
 *     @OA\Property(property="code", type="string", example="calculator"),
 *     @OA\Property(property="name", type="string", example="Calculadora"),
 *     @OA\Property(property="path", type="string", example="/calculadora"),
 *     @OA\Property(property="url", type="string", nullable=true, example="https://backend.example.com/calculadora"),
 *     @OA\Property(property="embed_link_endpoint", type="string", nullable=true, example="https://backend.example.com/api/calculator/co2/embed-link"),
 *     @OA\Property(property="external", type="boolean", example=true),
 *     @OA\Property(property="icon", type="string", nullable=true, example="calculate"),
 *     @OA\Property(property="sort_order", type="integer", example=130),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"})
 * )
 */
class MenuResource extends JsonResource
{
    public function toArray($request): array
    {
        $isBackendPage = $this->code === 'calculator';
        $calculatorBaseUrl = $isBackendPage
            ? $this->calculatorBaseUrl($request)
            : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'path' => $this->path,
            'url' => $isBackendPage ? $calculatorBaseUrl.$this->path : null,
            'embed_link_endpoint' => $isBackendPage
                ? $calculatorBaseUrl.'/api/calculator/co2/embed-link'
                : null,
            'external' => $isBackendPage,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }

    private function calculatorBaseUrl($request): string
    {
        $configuredUrl = (string) config('co2.calculator_public_url', '');
        if ($configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        return rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
    }
}
