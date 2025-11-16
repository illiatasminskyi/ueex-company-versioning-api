<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\VersioningService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Company Versioning API",
 *     description="API для управління компаніями з підтримкою версійності"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 */
class CompanyController extends Controller
{
    private VersioningService $versioningService;
    
    public function __construct(VersioningService $versioningService)
    {
        $this->versioningService = $versioningService;
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/company",
     *     summary="Створити або оновити компанію",
     *     description="Створює нову компанію або оновлює існуючу з автоматичною версійністю",
     *     tags={"Companies"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","edrpou","address"},
     *             @OA\Property(property="name", type="string", maxLength=256, example="ТОВ Українська енергетична біржа"),
     *             @OA\Property(property="edrpou", type="string", maxLength=10, example="37027819"),
     *             @OA\Property(property="address", type="string", example="01001, Україна, м. Київ, вул. Хрещатик, 44")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Компанію створено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="created"),
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="version", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Компанію оновлено або дублікат",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="updated"),
     *                     @OA\Property(property="company_id", type="integer", example=1),
     *                     @OA\Property(property="version", type="integer", example=2)
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="duplicate"),
     *                     @OA\Property(property="company_id", type="integer", example=1)
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Помилка валідації",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Validation failed"),
     *             @OA\Property(property="messages", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'name' => 'required|string|max:256',
                'edrpou' => 'required|string|max:10',
                'address' => 'required|string',
            ]);

            // Check if company with this EDRPOU exists
            $existingCompany = Company::where('edrpou', $validated['edrpou'])->first();

            if (!$existingCompany) {
                // Create new company
                $company = Company::create($validated);
                
                // Create first version
                $version = $this->versioningService->createVersion(
                    $company,
                    'company_versions',
                    ['name', 'edrpou', 'address']
                );

                return response()->json([
                    'status' => 'created',
                    'company_id' => $company->id,
                    'version' => $version
                ], 201);
            } else {
                // Company exists, check for changes
                $hasChanges = false;
                
                // Check if any field has changed
                if ($existingCompany->name !== $validated['name'] ||
                    $existingCompany->edrpou !== $validated['edrpou'] ||
                    $existingCompany->address !== $validated['address']) {
                    $hasChanges = true;
                }

                if ($hasChanges) {
                    // Update the existing company
                    $existingCompany->update($validated);
                    
                    // Create new version
                    $version = $this->versioningService->createVersion(
                        $existingCompany,
                        'company_versions',
                        ['name', 'edrpou', 'address']
                    );

                    return response()->json([
                        'status' => 'updated',
                        'company_id' => $existingCompany->id,
                        'version' => $version
                    ]);
                } else {
                    // No changes detected
                    return response()->json([
                        'status' => 'duplicate',
                        'company_id' => $existingCompany->id
                    ]);
                }
            }
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all versions for a company by EDRPOU
     * 
     * @OA\Get(
     *     path="/company/{edrpou}/versions",
     *     summary="Отримати всі версії компанії",
     *     description="Повертає список всіх версій компанії за ЄДРПОУ",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="edrpou",
     *         in="path",
     *         description="ЄДРПОУ компанії",
     *         required=true,
     *         @OA\Schema(type="string", example="37027819")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список версій компанії",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="version", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="ТОВ Українська Енергетична Біржа"),
     *                 @OA\Property(property="edrpou", type="string", example="37027819"),
     *                 @OA\Property(property="address", type="string", example="м. Київ, вул. Хрещатик, 22"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2025-01-01 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Компанію не знайдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function versions(string $edrpou): JsonResponse
    {
        try {
            $company = Company::where('edrpou', $edrpou)->first();
            
            if (!$company) {
                return response()->json([
                    'error' => 'Company not found'
                ], 404);
            }

            $versions = $this->versioningService->getVersions(
                $company,
                'company_versions'
            );

            return response()->json($versions->map(function ($version) {
                return [
                    'version' => $version->version,
                    'name' => $version->name,
                    'edrpou' => $version->edrpou,
                    'address' => $version->address,
                    'created_at' => $version->created_at
                ];
            }));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
