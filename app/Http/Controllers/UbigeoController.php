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
    public function indexDepartments()
    {
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
    public function indexProvinces($departmentId)
    {
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
    public function indexDistricts($provinceId)
    {
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
        // obtener el valor de 'name' y seleccionar campos necesarios, aplicando el filtro y límite de resultados
        $ubigeos = District::select('id as id_district', 'cadena as name')
            ->when($request->filled('name'), function ($query) use ($request) {
                $query->where(DB::raw('LOWER(cadena)'), 'like', '%' . strtolower($request->name) . '%');
            })
            ->limit(20)
            ->get();


        // retornar resultados en formato json
        return response()->json($ubigeos);
    }

}
