<?php

namespace App\Http\Controllers\Auth;

use App\Models\Worker;
use App\Models\WorkerLogin;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\WorkerRequest;
use App\Http\Resources\Admin\WorkerResource;

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



    public function register(WorkerRequest $request)
    {
    $admin = auth('admin')->user();
    $branchManager= auth('worker')->user();

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


        $workerData = array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
        );

        $worker = WorkerLogin::create($workerData);


        $worker->save();

        return response()->json([
            'message' => 'worker Registration successful',
            'worker' => new WorkerResource($worker->load('workerLogin'))
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
