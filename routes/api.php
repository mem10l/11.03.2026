<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationStatusController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradingTypeController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::apiResource('user-roles', UserRoleController::class)->except(['store', 'update', 'destroy']);
Route::apiResource('application-statuses', ApplicationStatusController::class)->except(['store', 'update', 'destroy']);
Route::apiResource('grading-types', GradingTypeController::class)->except(['store', 'update', 'destroy']);

Route::apiResource('users', UserController::class);
Route::apiResource('companies', CompanyController::class);
Route::apiResource('classes', SchoolClassController::class);
Route::apiResource('internships', InternshipController::class);
Route::apiResource('applications', ApplicationController::class);
Route::post('applications-procedure', [ApplicationController::class, 'storeWithProcedure']);
Route::apiResource('placements', PlacementController::class);
Route::apiResource('grades', GradeController::class);
