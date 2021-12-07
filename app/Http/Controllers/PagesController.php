<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagesController extends Controller
{
    public function AvailableRoutes(){
        // $currUser = Auth::user();
        $route = DB::table('route')
        ->select ('O_termID', 'D_termID')
        ->get();

                //to get the user has an ID property
        
        return view('user.route',['route'=>$route]);
        //
        }
    
    public function TicketDetails(){

        $users = DB::table('vhire')
        ->select('vhire.PlateNum','terminal.Location Name','trip.ETD','trip.ETA', 'orders.Quantity','route.Fare')
        ->leftjoin('trip', 'trip.vehicleID', '=','vhire.vehicleID')
        ->leftjoin('route', 'trip.routeID', '=','route.routeID')
        ->leftjoin('terminal','route.O_termID', '=', 'terminal.terminalID')
        ->leftjoin('orders', 'orders.orderID', '=','trip.tripID')
        ->get();
        
            // var_dump($users);
        $currUser = Auth::user(); 
        return view('user.cancel',['users' => $users]);
        }
}