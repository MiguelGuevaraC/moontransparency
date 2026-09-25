<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/alerts",
     *     operationId="listPanelAlerts",
     *     summary="Listar alertas del panel administrativo",
     *     tags={"Alerts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="unread", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100)),
     *     @OA\Response(response=200, description="Alertas paginadas y total sin leer"),
     *     @OA\Response(response=403, description="Solo administradores")
     * )
     */
    public function index(Request $request)
    {
        if ($response = $this->denyUnlessAdministrator($request)) {
            return $response;
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'unread' => ['nullable', 'in:0,1,true,false'],
        ]);
        $user = $request->user();
        $query = $user->notifications()->orderByDesc('sequence');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $alerts = $query->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => $alerts->getCollection()->map(fn ($notification) => $this->serialize($notification)),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/alerts/unread-count",
     *     operationId="countUnreadPanelAlerts",
     *     summary="Consultar cantidad de alertas sin leer",
     *     tags={"Alerts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Cantidad de alertas sin leer"),
     *     @OA\Response(response=403, description="Solo administradores")
     * )
     */
    public function unreadCount(Request $request)
    {
        if ($response = $this->denyUnlessAdministrator($request)) {
            return $response;
        }

        return response()->json([
            'data' => ['unread_count' => $request->user()->unreadNotifications()->count()],
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/alerts/{id}/read",
     *     operationId="markPanelAlertAsRead",
     *     summary="Marcar una alerta como leída",
     *     tags={"Alerts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Alerta marcada como leída"),
     *     @OA\Response(response=404, description="Alerta no encontrada")
     * )
     */
    public function markAsRead(Request $request, string $id)
    {
        if ($response = $this->denyUnlessAdministrator($request)) {
            return $response;
        }

        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return response()->json(['message' => 'Alerta no encontrada.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['data' => $this->serialize($notification->fresh())]);
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/alerts/read-all",
     *     operationId="markAllPanelAlertsAsRead",
     *     summary="Marcar todas las alertas como leídas",
     *     tags={"Alerts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Todas las alertas quedaron leídas")
     * )
     */
    public function markAllAsRead(Request $request)
    {
        if ($response = $this->denyUnlessAdministrator($request)) {
            return $response;
        }

        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['data' => ['unread_count' => 0]]);
    }

    private function denyUnlessAdministrator(Request $request)
    {
        if ($request->user()?->isAdministrator()) {
            return null;
        }

        return response()->json([
            'message' => 'Solo los administradores pueden consultar las alertas.',
        ], 403);
    }

    private function serialize($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
