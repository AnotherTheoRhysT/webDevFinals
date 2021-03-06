<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use App\Models\Orders;
use App\Models\Trip;
use App\Models\Route;


class PagesController extends Controller
{
    public function AvailableRoutes(){
        // $currUser = Auth::user();
        // $route = DB::table('route')
        // ->select ('O_termID', 'D_termID')
        // ->get();

        $terminal = DB::table('terminal')
        ->select('Location_Name', 'terminalID')
        ->get();

        $route = DB::table('trip')
        ->select ('O_termID', 'D_termID', 'Fare')
        ->select('route.routeID', 'O_termID', 'D_termID', 'terminal1.Location_Name AS origin', 'terminal2.Location_Name AS destination')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('terminal AS terminal1', 'terminal1.terminalID', '=', 'route.O_termID')
        ->join('terminal AS terminal2', 'terminal2.terminalID', '=', 'route.D_termID')
        ->groupby('trip.routeID')
        ->get();
        return view('user.route',['route'=>$route, 'terminal'=>$terminal]);
        }
    
    public function TicketDetails(Request $request){

        $users = DB::table('orders')
        ->select('vhire.PlateNum','vhire.vehicleID','terminal.Location_Name','trip.ETD','trip.ETA', 'orders.Quantity','route.Fare', 'orders.orderID', 'orders.statusChangeDT', 'orders.Status', 'orders.Date', 'trip.tripID')
        ->leftjoin('trip', 'orders.tripID', '=','trip.tripID')
        ->leftjoin('route', 'trip.routeID', '=','route.routeID')
        ->leftjoin('terminal','route.O_termID', '=', 'terminal.terminalID')
        ->leftjoin('vhire', 'vhire.vehicleID', '=','trip.vehicleID')
        ->where('orders.orderID', $request->input('orderID'))
        ->get();
        
            // var_dump($users);
        $currUser = Auth::user();

        return view('user.cancel',['users' => $users->first()]);
    }

    // public function Admin(){

    // $admin = DB::table('admin')
    // ->select('email','username')
    // ->where('status', 1)
    // ->get();

    // // $adminUser = Auth::Admin(); 
    // return view('admin.account',['admin' => $admin]);

    // }
    // Public function delete()

    public function showterminal(){
        // $currUser = Auth::user();
        $rname = DB::table('route')
        ->select ('O_termID', 'D_termID', 'Fare', 'Trip Duration')
        ->get();

        $tname = DB::table('terminal')
        ->select('terminalID')->get();

                //to get the user has an ID property
        
        // var_dump($rname);
        // return view('user.route',['route'=>$route]);
        return view('admin.route',['rname'=>$rname, 'tname' => $tname]);
        }    

    public function condition(Request $request){
        // dd($request);
        $input = $request->input();
        $rID = $input['T1']."-".$input['T2'];

        $update = DB::table('route')
        //->updateorInsert;
            ->upsert(
                ['routeID' => $rID,'O_termID' => $input['T1'], 'D_termID' => $input['T2'], 'Fare' => $input['fare'], 'Trip Duration' => strtotime($input['Travel'])],
                ['Fare' => $input['fare'], 'Trip Duration' => strtotime($input['Travel'])]
            );
        // var_dump($request);
        return redirect('routes');
    
    }


    public function DeleteRoute(Request $request)
    {
        // dd($request->all());

        //delete
        DB::table('route')->where('routeID', '=', $request->routeID)->delete();
        return redirect('routes');
    }


    public function Home(){

        $terminals = DB::table('terminal')
        ->select('terminalID', 'Location_Name')
        ->get();

        $scheds = DB::table('trip')
        ->select('ETD', 'ETA')
        ->groupby('ETD', 'ETA')
        ->get();
            
            // var_dump($users);
        //return $currUser;
        return view('user.home',['terminals' => $terminals, 'scheds' => $scheds]);
    }    


    public function Dashboard(){
        $booking = DB::table('orders')
        ->select('orders.orderID', 'orders.statusChangeDT', 'orders.tripID', 'users.username', 'orders.Status', 'terminal1.Location_Name AS origin', 'terminal2.Location_Name AS dest', 'trip.ETD', 'trip.ETA', 'vhire.vehicleID', 'vhire.PlateNum')
        ->join('users', 'orders.customerID', '=', 'users.userID')
        ->join('trip', 'orders.tripID', '=', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('terminal AS terminal1', 'route.O_termID', '=', 'terminal1.terminalID')
        ->join('terminal AS terminal2', 'route.D_termID', '=', 'terminal2.terminalID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->get();

        $vhire = DB::table('trip')
        ->select('vhire.vehicleID', 'vhire.PlateNum', 'trip.routeID', 'trip.tripID', 'users.username')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->get();

        $revenue = DB::table('orders')
        ->where('status', '=', 'CONFIRMED')
        ->sum('AmountDue');

        $total = DB::table('orders')
        ->count();

        $sold = DB::table('orders')
        ->where('Status', '=', 'CONFIRMED')
        ->count();

        return view('admin.dashboard', ['booking' => $booking, 'vhire' => $vhire, 'revenue'=> $revenue, 'total'=> $total, 'sold'=> $sold]);
    }

    public function Schedule(){
        $trips = DB::table('trip')
        ->select('*')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->get();

        $terms = DB::table('terminal')
        ->select('terminalID', 'Location_Name')
        ->get();

        return view('user.schedule',['trips' => $trips, 'terms' => $terms]);
    }
    public function Book(Request $request){
        $tripID = $request->input('tripID');
        $infos = DB::table('trip')
        ->select('trip.vehicleID', 'vhire.PlateNum', 'terminal.terminalID', 'terminal.Location_Name', 'trip.ETD', 'trip.ETA', 'trip.routeID', 'trip.FreeSeats', 'route.Fare', 'trip.tripID')
        ->leftjoin('route', 'trip.routeID', '=','route.routeID')
        ->leftjoin('vhire', 'trip.vehicleID', '=','vhire.vehicleID')
        ->leftjoin('terminal', 'route.O_termID', '=','terminal.terminalID')
        ->where('tripID', $tripID)
        ->get();

        return view('user.book',['info' => $infos->first()]);
    }
    
    public function Search(Request $request){
        $routeID = $request->input('routeID');

        $trips = DB::table('trip')
        ->select('*')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->where('trip.routeID', $routeID)
        ->get();

        return view('user.search',['trips' => $trips]);
    }

    public function HomeSearch(Request $request){
        $targetETD = date('h:i', strtotime(substr($request->input('time'), 0, 5)));
        $O_term = $request->input('O_term');
        $D_term = $request->input('D_term');

        $trips = DB::table('trip')
        ->select('*')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal as D_term', 'route.D_termID', '=', 'D_term.terminalID')
        ->join('terminal as O_term', 'route.O_termID', '=', 'O_term.terminalID')
        ->where('ETD', $targetETD)
        ->where('D_term.Location_Name', $D_term)
        ->where('O_term.Location_Name', $O_term)
        ->get();

        if(isset($trips->first()->routeID))return view('user.search',['trips' => $trips]);
        else return Redirect::back()->with('msg', 'No trip exists with such route and time. Please try again.');
    }

    public function AdminSched(){
        $vhires = DB::table('trip')
        ->select('vhire.PlateNum', 'route.routeID', 'trip.ETD', 'trip.ETA', 'users.username', 'trip.Status', 'trip.FreeSeats', 'vhire.Capacity', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->orderby('trip.ETD')
        ->orderby('trip.routeID')
        ->where('trip.status', '!=', 'DELETED')
        ->get();

        $open = DB::table('trip')
        ->select('vhire.PlateNum', 'route.routeID', 'trip.ETD', 'trip.ETA', 'users.username', 'trip.Status', 'trip.FreeSeats',  'vhire.Capacity', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->where('trip.status', 'OPEN')
        ->orderby('trip.ETD')
        ->orderby('trip.routeID')
        ->get();

        $closed = DB::table('trip')
        ->select('vhire.PlateNum', 'route.routeID', 'trip.ETD', 'trip.ETA', 'users.username', 'trip.Status', 'trip.FreeSeats',  'vhire.Capacity', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->where('trip.status', 'CLOSED')
        ->orderby('trip.ETD')
        ->orderby('trip.routeID')
        ->get();

        $arrived = DB::table('trip')
        ->select('vhire.PlateNum', 'route.routeID', 'trip.ETD', 'trip.ETA', 'users.username', 'trip.Status',  'trip.FreeSeats', 'vhire.Capacity', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->where('trip.status', 'ARRIVED')
        ->orderby('trip.ETD')
        ->orderby('trip.routeID')
        ->get();

        $deleted = DB::table('trip')
        ->select('vhire.PlateNum', 'route.routeID', 'trip.ETD', 'trip.ETA', 'users.username', 'trip.Status',  'trip.FreeSeats', 'vhire.Capacity', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('vhire', 'trip.vehicleID', '=', 'vhire.vehicleID')
        ->join('terminal', 'route.D_termID', '=', 'terminal.terminalID')
        ->join('users', 'vhire.driverID', '=', 'users.userID')
        ->where('trip.status', 'DELETED')
        ->orderby('trip.ETD')
        ->orderby('trip.routeID')
        ->get();

        $routes = DB::table('route')
        ->select('*')
        ->get();

        $drivers = DB::table('users')
        ->select('*')
        ->where('role', '=', 'DRIVER')
        ->get();

        $vehicles = DB::table('vhire')
        ->select('PlateNum')
        ->get();
        return view('admin.schedule',['deleted' => $deleted, 'vehicles' => $vehicles, 'vhires' => $vhires, 'open' => $open, 'closed' => $closed, 'arrived' => $arrived, 'routes' => $routes, 'drivers' => $drivers]);
    }

    public function AdminBooking(){
        $book = DB::table('orders')
        ->select('orders.orderID','users.username', 'orders.orderCreationDT', 'trip.routeID', 'trip.FreeSeats', 'orders.Status', 'orders.Quantity', 'trip.ETD', 'trip.ETA')
        ->join('users', 'users.userID', '=', 'orders.customerID')
        ->join('trip', 'trip.tripID', '=', 'orders.tripID')
        ->get();

        $confirmed = DB::table('orders')
        ->select('orders.orderID','users.username', 'orders.orderCreationDT', 'trip.routeID', 'trip.FreeSeats',  'orders.Status', 'orders.Quantity', 'trip.ETD', 'trip.ETA')
        ->join('users', 'users.userID', '=', 'orders.customerID')
        ->join('trip', 'trip.tripID', '=', 'orders.tripID')
        ->where('orders.Status', '=', 'CONFIRMED')
        ->get();

        $pending = DB::table('orders')
        ->select('orders.orderID','users.username', 'orders.orderCreationDT', 'trip.routeID', 'trip.FreeSeats',  'orders.Status', 'orders.Quantity', 'trip.ETD', 'trip.ETA')
        ->join('users', 'users.userID', '=', 'orders.customerID')
        ->join('trip', 'trip.tripID', '=', 'orders.tripID')
        ->where('orders.Status', '=', 'UNCONFIRMED')
        ->get();

        $cancelled = DB::table('orders')
        ->select('orders.orderID','users.username', 'orders.orderCreationDT', 'trip.routeID', 'trip.FreeSeats',  'orders.Status', 'orders.Quantity', 'trip.ETD', 'trip.ETA')
        ->join('users', 'users.userID', '=', 'orders.customerID')
        ->join('trip', 'trip.tripID', '=', 'orders.tripID')
        ->where('orders.Status', '=', 'CANCELLED')
        ->get();

        $trips = DB::table('trip')
        ->select('*')
        ->get();

        $passenger = DB::table('users')
        ->select('*')
        ->get();
        return view('admin.booking', ['book' => $book, 'confirmed' => $confirmed, 'pending' => $pending, 'cancelled' => $cancelled, 'trips' => $trips, 'passenger' => $passenger]);
    }

    public function DeleteBooking(Request $request){
        // dd($request->all());
        //delete
        DB::table('orders')->where('orderID', '=', $request->del_book)->delete();
        //redirect to account
        return redirect()->route('booking');

    }
    public function AddBooking(Request $request){

        $this->validate($request,[
            'passID' =>'required',
            'tripID' =>'required',
            'quantity' => 'required|max:255',
        ]);

            $fare = DB::table('trip')
            ->select('*')
            ->join('route', 'trip.routeID', '=', 'route.routeID')
            ->where('trip.tripID', '=', $request->tripID)
            ->get();

            // insert in database
            DB::table('orders')->insert([
                'customerID' => $request->passID,
                'tripID'     => $request->tripID,
                'Quantity'   => $request->quantity,
                'Date'       => $request->date,
                'status'     => $request->book_status,
                'AmountDue'  => $request->quantity * $fare->first()->Fare
            ]);

        return redirect()->route('booking');
    }

    public function Ticket(){
        $currUser = Auth::user();

        $orders = DB::table('orders')
        ->select(
            'O_term.Location_Name as O_Loc', 
            'D_term.Location_Name as D_Loc',
            'trip.ETD',
            'trip.ETA',
            'orders.Date',
            'orders.Quantity',
            'route.Fare',
            'orders.orderID',
            'orders.AmountDue',
            'orders.Status'
            )
        ->join ('trip', 'orders.tripID', '=', 'trip.tripID')
        ->join('route', 'trip.routeID', '=', 'route.routeID')
        ->join('terminal as O_term', 'route.O_termID', '=', 'O_term.terminalID')
        ->join('terminal as D_term', 'route.D_termID', '=', 'D_term.terminalID')
        ->where('customerID', $currUser->userID)
        ->where('orders.Status', '!=','CANCELLED')
        ->get();

        return view('user.ticket',['orders' => $orders]);
    }


    public function UpdateAcc(Request $request){
        // validate
        $this->validate($request,[
            'a_Username' =>'required|max:255',
            'a_Email' =>'required|max:255|email',
            'a_ContactNum' => 'required|max:255',
            // 'a_Password' => 'required'
        ]);

        DB::table('users')->where('userID', '=', $request->a_adminID)->update([
            'email'      => $request->a_Email,
            'contactNum' => $request->a_ContactNum,
            'username'   => $request->a_Username,  
            'password'  => Hash::make($request->a_Password),
            'status'     => $request->a_status,
        ]);
        
        return redirect()->route('account');
    }


    public function deleteAdminAcc(Request $request){
        //delete
        DB::table('users')->where('userID', '=', $request->adminID)->delete();
        //redirect to account
        return redirect()->route('account');
    }


    public function AddAdmin(){
        $admin = DB::table('users')
        ->select('*')
        ->where('role','=','ADMIN')
        ->get();

        $terminal = DB::table('terminal')
        ->select('*')
        ->get();

        $active =  DB::table('users')
        ->select('*')
        ->where('status', '=','ACTIVE')
        ->where('role', '=','ADMIN')
        ->get();

        $inactive =  DB::table('users')
        ->select('*')
        ->where('status', '=','INACTIVE')
        ->where('role', '=','ADMIN')
        ->get();

        // $adminUser = Auth::Admin(); 
        return view('admin.account',['admin' => $admin,'terminals'=>$terminal,'actives' => $active, 'inactives' => $inactive]);
    }

    public function AddAcc(Request $request){
        //validate
        $this->validate($request,[
            'Username' =>'required|max:255',
            'Email' =>'required|max:255|email',
            'ContactNum' => 'required|max:255',
            'Password' => 'required'
        ]);


        //insert in database
        DB::table('users')->insert([
            'email' => $request->Email,
            'contactNum' => $request->ContactNum,
            'username' => $request->Username,  
            'password' => Hash::make($request->Password),
            'status' => 'ACTIVE',
            'role' => 'ADMIN',
        ]);

        //redirect
        return redirect()->route('account');
    }
    public function AddSched(Request $request){
        //validate
        $this->validate($request,[
            'capacity' =>'required|min:1',
        ]);

        $vID = DB::table('vhire')
                ->select('vehicleID')
                ->where('PlateNum', $request->vhire)
                ->get()->first()->vehicleID;

        //insert in database
        DB::table('trip')->insert([
            'vehicleID' => $vID,
            'ETD' => $request->ETD,
            'ETA' => $request->ETA,  
            'routeID' => $request->route,
            'status' => 'OPEN',
            'FreeSeats' => $request->capacity,
        ]);

        //redirect
        return redirect('/scheds');
    }

    public function EditSched(Request $request){
        //validate
        $vID = DB::table('vhire')
                ->select('vehicleID')
                ->where('PlateNum', $request->vhire)
                ->get()->first()->vehicleID;

        DB::update('update trip 
            set ETD=?,
                ETA=?,
                routeID=?,
                status=?,
                FreeSeats=?
                where tripID = ?',
            [$request->ETD,$request->ETA,$request->route,$request->status,$request->capacity,$request->trip]
        );

        //redirect
        return redirect('/scheds');
    }


    public function UpdateRoute(Request $request){
        // validate

        // dd($request->all());
        $route = Route::find($request->rname);
        $oldFare = (float) $route->Fare;
        $newFare = (float) $request->fare;
        $multiplier = $newFare / $oldFare;
        $newname = $request->T1."-".$request->T2;

        $this->validate($request,[
            'rname' =>'required',
            'T1' =>'required',
            'T2' =>'required',
            'fare' =>'required',
            'Travel' =>'required',
        ]);

        DB::table('route')->where('routeID', '=', $request->rname)->update([
            'routeID'       => $newname,
            'O_termID'      => $request->T1,
            'D_termID'      => $request->T2,
            'Fare'          => $request->fare,
            'Trip Duration' => $request->Travel
        ]);

        $trips = Trip::select('*')->where('routeID', $request->rname)->get();
        foreach($trips as $trip){
            Orders::where('tripID', $trip->tripID)
            ->update([
                'AmountDue' => DB::raw('AmountDue * '.$multiplier)
            ]);
        }        
        return redirect('routes');
    }

    public function EditBook(Request $request){
        DB::update('update orders
            set status = ?, statusChangeDT = ?
            where orderID = ?',
            [$request->book_status, now(),$request->tripID]);

        if($request->Status == "CANCELLED")

        return redirect('/bookings');
    }
}
