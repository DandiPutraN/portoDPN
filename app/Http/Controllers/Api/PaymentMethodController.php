<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;

class PaymentMethodController extends Controller
{
    public function index(){
        $paymentMethods = PaymentMethod::all();

        return response()->json([
            'success' => true,
            'message' => 'Found ' . $paymentMethods->count() . ' Payment Methods',
            'data' => $paymentMethods
        ]);
        
    }
}
