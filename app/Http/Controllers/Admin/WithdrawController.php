<?php

namespace App\Http\Controllers\Admin;

use App\Models\Withdraw;
use App\Models\MainInvoice;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\WithdrawRequest;
use App\Http\Resources\Admin\WithdrawResource;

class WithdrawController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;


        public function showAllWithPaginate(Request $request)
    {
        $this->authorize('manage_users');

        $Withdraws = Withdraw::
                     orderBy('created_at', 'desc')
                     ->paginate(10);
        return response()->json([
            'data' => $Withdraws->map(function ($Withdraws) {
                return [
                    'id' => $Withdraws->id,
                    'withdrawnAmount' => $Withdraws-> withdrawnAmount,
                    'creationDate' => $Withdraws-> creationDate,
                ];
            }),
            'pagination' => [
                'total' => $Withdraws->total(),
                'count' => $Withdraws->count(),
                'per_page' => $Withdraws->perPage(),
                'current_page' => $Withdraws->currentPage(),
                'total_pages' => $Withdraws->lastPage(),
                'next_page_url' => $Withdraws->nextPageUrl(),
                'prev_page_url' => $Withdraws->previousPageUrl(),
            ],
            'message' => "Show All Withdraws Successfully."
        ]);
    }

        public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_users');

        $Withdraws = Withdraw::
                     orderBy('created_at', 'desc')
                     ->get();
        return response()->json([
            'data' => $Withdraws->map(function ($Withdraws) {
                return [
                    'id' => $Withdraws->id,
                    'withdrawnAmount' => $Withdraws-> withdrawnAmount,
                    'creationDate' => $Withdraws-> creationDate,
                ];
            }),

            'message' => "Show All Withdraws Successfully."
        ]);
    }


//     public function create(WithdrawRequest $request)
// {
//     $this->authorize('manage_users');

//     $totalSales = MainInvoice::sum('total');

//     // إجمالي المبالغ المسحوبة
//     $totalWithdrawals = Withdraw::sum('withdrawnAmount');

//     // حساب المبلغ المتاح للسحب
//     $availableWithdrawal = $totalSales - $totalWithdrawals;

//     $amountToWithdraw = $request->withdrawnAmount;

//     if ($amountToWithdraw > $availableWithdrawal) {
//         return response()->json([
//             'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
//             'availableWithdrawal' => $availableWithdrawal,
//         ], 400);
//     }

//     $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

//     $withdraw = Withdraw::create([
//         'withdrawnAmount' => $amountToWithdraw,
//         'remainingAmount' => $remainingAmountAfterWithdraw,
//         'description' => $request->description,
//     ]);

//     return response()->json([
//         'message' => 'تم السحب بنجاح.',
//         'data' => new WithdrawResource($withdraw),
//         'availableWithdrawal' => $remainingAmountAfterWithdraw,
//     ]);
// }


//     public function edit(string $id)
//     {
//         $this->authorize('manage_users');
//         $Withdraw = Withdraw::find($id);

//         if (!$Withdraw) {
//             return response()->json([
//                 'message' => "Withdraw not found."
//             ], 404);
//         }

//         return response()->json([
//             'data' =>new WithdrawResource($Withdraw),
//             'message' => "Edit Withdraw By ID Successfully."
//         ]);
//     }


//     public function update(WithdrawRequest $request, string $id)
//     {
//         $this->authorize('manage_users');

//         $withdraw = Withdraw::find($id);

//         if (!$withdraw) {
//             return response()->json([
//                 'message' => 'عملية السحب غير موجودة.',
//             ], 404);
//         }

//         $totalSales = MainInvoice::sum('total');
//         $totalWithdrawals = Withdraw::where('id', '!=', $id)->sum('withdrawnAmount'); // استبعاد السحب الحالي
//         $availableWithdrawal = $totalSales - $totalWithdrawals;

//         $amountToWithdraw = $request->withdrawnAmount;

//         if ($amountToWithdraw > $availableWithdrawal) {
//             return response()->json([
//                 'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
//                 'availableWithdrawal' => $availableWithdrawal,
//             ], 400);
//         }

//         $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

//         $withdraw->update([
//             'withdrawnAmount' => $amountToWithdraw,
//             'remainingAmount' => $remainingAmountAfterWithdraw,
//             'description' => $request->description,
//         ]);

//         return response()->json([
//             'message' => 'تم تحديث عملية السحب بنجاح.',
//             'data' => new WithdrawResource($withdraw),
//             'availableWithdrawal' => $remainingAmountAfterWithdraw,
//         ]);
//     }


    // public function create(WithdrawRequest $request)
    // {
    //     $this->authorize('manage_users');

    //     $totalSales = MainInvoice::sum('total');
    //     $totalWithdrawals = Withdraw::sum('withdrawnAmount');
    //     $availableWithdrawal = $totalSales - $totalWithdrawals;

    //     $amountToWithdraw = $request->withdrawnAmount;

    //     if ($amountToWithdraw > $availableWithdrawal) {
    //         return response()->json([
    //             'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
    //             'availableWithdrawal' => $availableWithdrawal,
    //         ], 400);
    //     }

    //     $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

    //     $withdraw = Withdraw::create(array_merge(
    //         [
    //             'withdrawnAmount' => $amountToWithdraw,
    //             'remainingAmount' => $remainingAmountAfterWithdraw,
    //             'description' => $request->description,
    //         ],
    //         $this->prepareCreationMetaData()
    //     ));

    //     return response()->json([
    //         'message' => 'تم السحب بنجاح.',
    //         'data' => new WithdrawResource($withdraw),
    //         'availableWithdrawal' => $remainingAmountAfterWithdraw,
    //     ]);

    // }

    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $withdraw = Withdraw::find($id);

        if (!$withdraw) {
            return response()->json([
                'message' => "Withdraw not found."
            ], 404);
        }

        return $this->respondWithResource($withdraw, "Edit Withdraw By ID Successfully.");
    }

public function create(WithdrawRequest $request)
{
    $this->authorize('manage_users');

    $totalSales = MainInvoice::sum('paidAmount');
    $totalWithdrawals = Withdraw::sum('withdrawnAmount');
    $availableWithdrawal = $totalSales - $totalWithdrawals;

    $amountToWithdraw = $request->withdrawnAmount;

    if ($amountToWithdraw > $availableWithdrawal) {
        return response()->json([
            'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
            'availableWithdrawal' => $availableWithdrawal,
        ], 400);
    }

    $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

    $withdraw = Withdraw::create(array_merge(
        [
            'withdrawnAmount' => $amountToWithdraw,
            'remainingAmount' => $remainingAmountAfterWithdraw,
            'description' => $request->description,
        ],
        $this->prepareCreationMetaData()
    ));

    return $this->respondWithResource(
        $withdraw,
        'تم السحب بنجاح.',
        200,
        ['availableWithdrawal' => $remainingAmountAfterWithdraw]
    );
}

public function update(WithdrawRequest $request, string $id)
{
    $this->authorize('manage_users');

    $withdraw = Withdraw::find($id);
    if (!$withdraw) {
        return response()->json([
            'message' => 'عملية السحب غير موجودة.',
        ], 404);
    }

    $oldData = $withdraw->toArray();

    $totalSales = MainInvoice::sum('paidAmount');
    $totalWithdrawals = Withdraw::where('id', '!=', $id)->sum('withdrawnAmount');
    $availableWithdrawal = $totalSales - $totalWithdrawals;

    $amountToWithdraw = $request->withdrawnAmount;

    if ($amountToWithdraw > $availableWithdrawal) {
        return response()->json([
            'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
            'availableWithdrawal' => $availableWithdrawal,
        ], 400);
    }

    $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

    $updateData = array_merge(
        [
            'withdrawnAmount' => $amountToWithdraw,
            'remainingAmount' => $remainingAmountAfterWithdraw,
            'description' => $request->description,
        ],
        $this->prepareUpdateMeta($request)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($withdraw->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        return $this->respondWithResource($withdraw, "لا يوجد تغييرات فعلية");
    }

    $withdraw->update($updateData);
    $changedData = $withdraw->getChangedData($oldData, $withdraw->fresh()->toArray());
    $withdraw->changed_data = $changedData;
    $withdraw->save();

    return $this->respondWithResource(
        $withdraw,
        'تم تحديث عملية السحب بنجاح.',
        200,
        ['availableWithdrawal' => $remainingAmountAfterWithdraw]
    );
}

    protected function prepareUpdateMetaData(): array
{
    $updatedBy = $this->getUpdatedByIdOrFail();
    return [
        'updated_by' => $updatedBy,
        'updated_by_type' => $this->getUpdatedByType(),
        'updated_at' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        'updated_at_hijri' => $this->getHijriDate(),
    ];
}

      protected function getResourceClass(): string
    {
        return WithdrawResource::class;
    }

}
