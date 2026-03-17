<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyResourceCollection;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CompanyResourceCollection
    {
        $query = Company::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate($request->get('per_page', 15));

        return new CompanyResourceCollection($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $company = Company::create($validated);

        return response()->json([
            'message' => 'Company created successfully.',
            'data' => new CompanyResource($company),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): CompanyResource
    {
        $company->load('applications', 'placements');

        return new CompanyResource($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Company updated successfully.',
            'data' => new CompanyResource($company),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully.',
        ]);
    }
}
