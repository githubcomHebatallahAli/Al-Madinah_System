<?php

namespace App\Http\Controllers\Auth;

use App\Models\Admin;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Auth\AdminRegisterRequest;
use App\Http\Resources\Auth\AdminRegisterResource;

class AdminAuthController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;

    //    public function login(LoginRequest $request)
    // {
    //     $validator = Validator::make($request->all(), $request->rules());


    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     if (!$token = auth()->guard('admin')->attempt($validator->validated())) {
    //         return response()->json([
    //             'message' => 'Invalid data'
    //         ], 422);
    //     }

    //     $admin = auth()->guard('admin')->user();

    //     return $this->createNewToken($token);
    // }

    public function login(LoginRequest $request)
{
    $validator = Validator::make($request->all(), $request->rules());

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    if (!$token = auth()->guard('admin')->attempt($validator->validated())) {
        return response()->json([
            'message' => 'Invalid data'
        ], 422);
    }

    $admin = auth()->guard('admin')->user();

    if (!$admin || $admin->status !== 'active') {
        auth('admin')->logout();
        return response()->json([
            'message' => 'Your account is not active.'
        ], 403);
    }

    return $this->createNewToken($token);
}




//     public function register(AdminRegisterRequest $request)
// {
//     if (!Gate::allows('create', Admin::class)) {
//         return response()->json([
//             'message' => 'Unauthorized'
//         ], 403);
//     }

//     $validator = Validator::make($request->all(), $request->rules());

//     if ($validator->fails()) {
//         return response()->json($validator->errors()->toJson(), 400);
//     }


//     $hijriDate = $this->getHijriDate();
//     $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

//     $adminData = array_merge(
//         $validator->validated(),
//         [
//             'password' => bcrypt($request->password),
//             'status' => 'active',
//             'creationDate' => $gregorianDate,
//             'creationDateHijri' => $hijriDate,
//         ]
//     );

//     $admin = Admin::create($adminData);
//     return response()->json([
//         'message' => 'Admin Registration successful',
//         'admin' => new AdminRegisterResource($admin)
//     ]);
// }

public function register(AdminRegisterRequest $request)
{
    $currentAdmin = auth('admin')->user();

    if (!$currentAdmin || $currentAdmin->status !== 'active') {
        return response()->json([
            'message' => 'Unauthorized. Your account is not active.'
        ], 403);
    }

    if (!Gate::allows('create', Admin::class)) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    $validator = Validator::make($request->all(), $request->rules());

    if ($validator->fails()) {
        return response()->json($validator->errors()->toJson(), 400);
    }

    $hijriDate = $this->getHijriDate();
    $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $adminData = array_merge(
        $validator->validated(),
        [
            'password' => bcrypt($request->password),
            'status' => 'active',
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
        ]
    );

    $admin = Admin::create($adminData);

    return response()->json([
        'message' => 'Admin Registration successful',
        'admin' => new AdminRegisterResource($admin)
    ]);
}




    public function logout()
{
    auth()->guard('admin')->logout();
    return response()->json([
        'message' => 'admin successfully signed out',

    ]);
}


    public function refresh()
    {
        return $this->createNewToken(auth()->guard('admin')->refresh());
    }


    public function userProfile()
    {
        return response()->json([
        "data" => auth()->guard('admin')->user()
        ]);
    }


    protected function createNewToken($token)
    {
        $admin = Admin::with('role:id,name')->find(auth()->guard('admin')->id());
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('admin')->factory()->getTTL() * 60,
            'admin' => $admin,
        ]);
    }

}
