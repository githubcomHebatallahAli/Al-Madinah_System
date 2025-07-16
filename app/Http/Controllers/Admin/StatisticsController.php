<?php

namespace App\Http\Controllers\Admin;


// use App\Models\MainInvoice;
use App\Http\Controllers\Controller;
use App\Models\MainInvoice;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function showStatistics()
{
    $this->authorize('manage_users');
       $InvoicesCount = MainInvoice::count();

     
        $CompletedInvoicesCount = MainInvoice::where('invoiceStatus', 'completed')->count();
        $RejectedInvoicesCount = MainInvoice::where('invoiceStatus', 'rejected')->count();
        $PendingInvoicesCount = MainInvoice::where('invoiceStatus', 'pending')->count();
        $AbsenceInvoicesCount = MainInvoice::where('invoiceStatus', 'absence')->count();
        $ApprovedInvoicesCount = MainInvoice::where('invoiceStatus', 'approved')->count();

        // ✅ إجمالي المبيعات من الفواتير المكتملة
        $salesMainInvoice = MainInvoice::where('invoiceStatus', 'completed')->sum('total');

        $salesFlights = DB::table('flight_invoices')
    ->where('invoiceStatus', 'completed')
    ->sum('total');

        $salesFromInvoices = $salesMainInvoice + $salesFlights;


        // ✅ إجمالي السحب
        $totalWithdrawals = Withdraw::sum('withdrawnAmount');

        // ✅ أرباح الباصات
        $busTrips = DB::table('bus_trips')
            ->join('main_invoices', 'bus_trips.id', '=', 'main_invoices.bus_trip_id')
            ->join('buses', 'bus_trips.bus_id', '=', 'buses.id')
            ->select(
                DB::raw('(SUM(main_invoices.seatsCount) * buses.seatPrice - buses.sellingPrice) as profit'),
                'bus_trips.id'
            )
            ->groupBy('bus_trips.id', 'buses.seatPrice', 'buses.sellingPrice')
            ->get();

        $busProfit = $busTrips->sum('profit');

        // ✅ أرباح الفنادق (bed / room)
        $hotelProfit = DB::table('main_invoice_hotels')
            ->join('hotels', 'hotels.id', '=', 'main_invoice_hotels.hotel_id')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN main_invoice_hotels.sleep = 'room'
                            THEN (hotels.sellingPrice - hotels.purchesPrice) * main_invoice_hotels.numRoom * main_invoice_hotels.numDay
                        WHEN main_invoice_hotels.sleep = 'bed'
                            THEN (hotels.bedPrice - (hotels.purchesPrice / 
                                CASE 
                                    hotels.roomType
                                        WHEN 'single' THEN 1
                                        WHEN 'double' THEN 2
                                        WHEN 'triple' THEN 3
                                        WHEN 'quad' THEN 4
                                        WHEN 'suite' THEN 5
                                        ELSE 1
                                END
                            )) * main_invoice_hotels.numBed * main_invoice_hotels.numDay
                        ELSE 0
                    END
                ) AS profit
            ")
            ->value('profit');

        // ✅ أرباح الطيران
        // $flightProfit = DB::table('flights')
        //     ->selectRaw('SUM((sellingPrice - purchesPrice) * quantity) as profit')
        //     ->value('profit');

        $flightProfit = DB::table('flight_invoices')
    ->join('flights', 'flights.id', '=', 'flight_invoices.flight_id')
    ->selectRaw('SUM((flight_invoices.total - (flights.purchesPrice * flight_invoices.pilgrimsCount))) as profit')
    ->where('flight_invoices.invoiceStatus', 'completed')
    ->value('profit');

        // ✅ أرباح مستلزمات الإحرام
       $ihramProfit = DB::table('main_invoice_supplies')
    ->join('ihram_supplies', 'ihram_supplies.id', '=', 'main_invoice_supplies.ihram_supply_id')
    ->selectRaw('SUM((main_invoice_supplies.price - ihram_supplies.purchesPrice) * main_invoice_supplies.quantity) as profit')
    ->value('profit');


        // ✅ إجمالي الربح
        $totalProfit = $busProfit + $hotelProfit + $flightProfit + $ihramProfit;

        // ✅ صافي الربح بعد السحب
        $netProfit = $totalProfit - $totalWithdrawals;

        // ✅ المبلغ المتاح للسحب = الربح - ما تم سحبه
        $availableWithdrawal = $totalProfit - $totalWithdrawals;

        $statistics = [
            'Invoices_count'         => $InvoicesCount ,
            'Pendind_invoices_count' => $PendingInvoicesCount,
            'Rejected_invoices_count' => $RejectedInvoicesCount,
            'Approved_invoices_count' => $ApprovedInvoicesCount,
            'Absence_invoices_count' => $AbsenceInvoicesCount,
            'Completed_invoices_count' => $CompletedInvoicesCount,
            'Sales'                  => round($salesFromInvoices, 2),
            'Bus_profit'             => round($busProfit, 2),
            'Hotel_profit'           => round($hotelProfit, 2),
            'Flight_profit'          => round($flightProfit, 2),
            'Ihram_profit'           => round($ihramProfit, 2),
            'Total_profit'           => round($totalProfit, 2),
            'Net_Profit'             => round($netProfit, 2),
            'Available_Withdrawal'   => round($availableWithdrawal, 2),
        ];

        return response()->json($statistics);
    }

}
