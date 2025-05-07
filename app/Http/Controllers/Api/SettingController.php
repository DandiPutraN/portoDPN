<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function index(){
        $setting = Setting::first();

        if($setting){
            return response()->json([
                'success' => true,
                'message' => 'Successfully get profile data',
                'data' => $setting
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to get profile data',
            'data' => null
        ], 404);


    }
}
