<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UbigeoController extends Controller
{

    /**
     * @OA\Get(
     *     path="/tecnimotors-backend/public/api/departments",
     *     summary="Get all departments",
     *     tags={"Ubigeo"},
     *     description="Show all departments",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of departments",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Amazonas"),
     *                 @OA\Property(property="ubigeo_code", type="string", example="01"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */
    public function indexDepartments(Request $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $departments = Department::all();
        return response()->json($departments);
    }

    /**
     * @OA\Get(
     *     path="/tecnimotors-backend/public/api/provinces/{departmentId}",
     *     summary="Get provinces by department ID",
     *     tags={"Ubigeo"},
     *     description="Show all provinces for a given department ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="departmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the department",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of provinces",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chachapoyas"),
     *                 @OA\Property(property="ubigeo_code", type="string", example="0101"),
     *                 @OA\Property(property="department_id", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */
    public function indexProvinces($departmentId, Request $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        $provinces = Province::where('department_id', $departmentId)->get();
        return response()->json($provinces);
    }

    /**
     * @OA\Get(
     *     path="/tecnimotors-backend/public/api/districts/{provinceId}",
     *     summary="Get districts by province ID",
     *     tags={"Ubigeo"},
     *     description="Show all districts for a given province ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="provinceId",
     *         in="path",
     *         required=true,
     *         description="ID of the province",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of districts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chachapoyas"),
     *                 @OA\Property(property="ubigeo_code", type="string", example="010101"),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */
    public function indexDistricts($provinceId, Request $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        $districts = District::where('province_id', $provinceId)->get();
        return response()->json($districts);
    }

    /**
     * @OA\Get(
     *     path="/tecnimotors-backend/public/api/ubigeos",
     *     summary="Get ubigeo information",
     *     tags={"Ubigeo"},
     *     description="Show a specific ubigeo by combining the ID, province, and department.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Ubigeo details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="province", type="string", example="Trujillo"),
     *             @OA\Property(property="department", type="string", example="La Libertad"),
     *             @OA\Property(property="ubigeo", type="string", example="1-Trujillo-La Libertad"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */


public function ubigeos(Request $request)
{
    // validación UUID
    if ($request->header('UUID') !== env('APP_UUID')) {
        return response()->json(['status' => 'unauthorized'], 401);
    }

    // paginado por defecto
    $perPage = (int) $request->query('per_page', 20);
    $perPage = $perPage > 0 ? $perPage : 20;
    $page = (int) $request->query('page', 1);
    $page = $page > 0 ? $page : 1;

    // consulta con joins para obtener province -> department
    $query = District::select(
            'districts.id as id_district',
            'districts.name as name',
            'districts.cadena as cadena',
            'provinces.id as province_id',
            'provinces.name as province_name',
            'departments.id as department_id',
            'departments.name as department_name'
        )
        ->join('provinces', 'districts.province_id', '=', 'provinces.id')
        ->join('departments', 'provinces.department_id', '=', 'departments.id');

    // filtros opcionales
    if ($request->filled('name')) {
        $name = strtolower($request->get('name'));
        $query->where(DB::raw('LOWER(districts.cadena)'), 'like', '%' . $name . '%');
    }

    if ($request->filled('department_id')) {
        $query->where('departments.id', $request->get('department_id'));
    }

    if ($request->filled('province_id')) {
        $query->where('provinces.id', $request->get('province_id'));
    }

    if ($request->filled('district_id')) {
        $query->where('districts.id', $request->get('district_id'));
    }

    // orden por departamento/provincia/distrito para resultados más intuitivos
    $query->orderBy('departments.name')->orderBy('provinces.name')->orderBy('districts.cadena');

    // paginación
    $ubigeos = $query->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data' => $ubigeos->items(),
        'meta' => [
            'page' => $ubigeos->currentPage(),
            'per_page' => $ubigeos->perPage(),
            'total' => $ubigeos->total(),
            'last_page' => $ubigeos->lastPage(),
        ],
    ]);
}


}
