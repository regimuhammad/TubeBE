<?php

use App\Http\Controllers\Api\ArisanGroupController;
use App\Http\Controllers\Api\Login\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/arisan', [ArisanGroupController::class, 'viewArisan']);

Route::get('/user-by-wallet/{address}', [AuthController::class, 'getByWallet']);
Route::get('/arisanGroup/{groupId}/next-draw-number', [ArisanGroupController::class, 'getNextDrawNumber']);
Route::post('/arisanGroup/{groupId}/record-draw', [ArisanGroupController::class, 'recordDraw'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/my-arisan-groups', [ArisanGroupController::class, 'myGroups']);

Route::post('/arisanGroup/{groupId}/pay', [ArisanGroupController::class, 'pay'])->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function(){
    Route::post('/arisanGroup/{id}/join', [ArisanGroupController::class, 'joinById']);
    Route::resource('/arisanGroup', ArisanGroupController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/arisanGroup/{id}/contract-address', [ArisanGroupController::class, 'saveContractAddress']);
});

Route::get('/arisan/abi', function () {
    return response()->json(json_decode(file_get_contents(storage_path('app/arisan-abi.json')), true));
});


