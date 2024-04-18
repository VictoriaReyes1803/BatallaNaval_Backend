<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;

class ActivationController extends Controller
{
    public function activate(Request $request,User $user){
        if(!$request->hasValidSignature()){
          return redirect('/errors');
  
        }
  
        $user->activado = true; 
        $user->save();
  
        return redirect('/bienvenida');
      }
      public function refresh()
      {
          return $this->respondWithToken(auth()->refresh());
      }
}
