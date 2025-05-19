<?php

namespace App\Http\Controllers\Auth;

use App\Models\WorkerLogin;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Auth\WorkerRegisterRequest;
use App\Http\Resources\Auth\WorkerRegisterResource;

class WorkerAuthController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
public function login(LoginRequest $request)
{
    $validator = Validator::make($request->all(), $request->rules());

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    if (!$token = auth()->guard('worker')->attempt($validator->validated())) {
        return response()->json([
            'message' => 'Invalid data'
        ], 422);
    }

    return $this->createNewToken($token);
}



    // public function register(WorkerRegisterRequest $request)
    // {
    // $admin = auth('admin')->user();
    // $branchManager= auth('worker')->user();

    //   $authorized = ($admin && $admin->role_id == 1) ||
    //               ($branchManager && $branchManager->workerLogin->role_id == 2);

    // if (!$authorized) {
    //     return response()->json([
    //         'message' => 'Unauthorized. Only Super Admins or Branch Managers can register new workers.'
    //     ], 403);
    // }



    //     $validator = Validator::make($request->all(), $request->rules());

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors()->toJson(), 400);
    //     }

    // $hijriDate = $this->getHijriDate();
    // $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');


    //   $workerData = array_merge(
    //     $validator->validated(),
    //     [
    //         'password' => bcrypt($request->password),
    //         'creationDate' => $gregorianDate,
    //         'creationDateHijri' => $hijriDate,
    //     //     'added_by' => $admin ? $admin->id : $branchManager->id,
    //     // 'added_by_type' => $admin ? get_class($admin) : get_class($branchManager),

    //     ]
    // );



    //     $worker = WorkerLogin::create($workerData);


    //     $worker->save();

    //     return response()->json([
    //         'message' => 'worker Registration successful',
    //         'worker' => new WorkerRegisterResource($worker->load(['worker','role', 'creator']))
    //     ]);
    // }


    public function register(WorkerRegisterRequest $request)
{
    $admin = auth('admin')->user();
    $branchManager = auth('worker')->user();

    $authorized = ($admin && $admin->role_id == 1) ||
                  ($branchManager && $branchManager->workerLogin->role_id == 2);

    if (!$authorized) {
        return response()->json([
            'message' => 'Unauthorized. Only Super Admins or Branch Managers can register new workers.'
        ], 403);
    }

    $validator = Validator::make($request->all(), $request->rules());

    if ($validator->fails()) {
        return response()->json($validator->errors()->toJson(), 400);
    }

    $hijriDate = $this->getHijriDate();
    $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $addedById = $admin ? $admin->id : ($branchManager ? $branchManager->id : null);
    $addedByType = $admin ? get_class($admin) : ($branchManager ? get_class($branchManager) : null);

    Log::info('Registering worker with added_by info', [
        'added_by' => $addedById,
        'added_by_type' => $addedByType,
    ]);

    $workerData = array_merge(
        $validator->validated(),
        [
            'password' => bcrypt($request->password),
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
        ]
    );

    $worker = WorkerLogin::create($workerData);

    $worker->save();

    return response()->json([
        'message' => 'worker Registration successful',
        'worker' => new WorkerRegisterResource($worker->load(['worker','role', 'creator']))
    ]);
}


    public function logout()
{
    auth()->guard('worker')->logout();
    return response()->json([
        'message' => 'worker successfully signed out',

    ]);
}


    public function refresh()
    {
        return $this->createNewToken(auth()->guard('worker')->refresh());
    }


    public function userProfile()
    {
        return response()->json([
        "data" => auth()->guard('worker')->user()
        ]);
    }


protected function createNewToken($token)
{
    $workerLogin = auth()->guard('worker')->user();
    $worker = $workerLogin->worker->load('workerLogin.role');

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth()->guard('worker')->factory()->getTTL() * 60,
        'worker' => $worker,
    ]);
}
}
