<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerificationCodeMail;
use App\Models\User;


class AuthController extends Controller
{  /**
    * Create a new AuthController instance.
    *
    * @return void
    */
   public function __construct()
   {
       $this->middleware('auth:api:jwt', ['except' => ['login']]);
   }

   /**
    * Get a JWT via given credentials.
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function login()
   {
       $credentials = request(['email', 'password']);
       $user = User::where('email', request()->email)->first();

       if (!$token = Auth::guard('api:jwt')->attempt($credentials)) {
           return response()->json(['error' => 'No autentificado.'], 401);
       } else if (($user && $user->activado == false)) {
           return response()->json(['error' => 'No se ha verificado.'], 403);
       }

       $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

       $user->token_verificacion = Hash::make($verificationCode);
       $user->save();

       Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
           return $this->respondWithToken($token);

   }

   public function verificarcodigo(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'verification_code' => 'required|string|min:6|max:6',
       ]);
   
       if ($validator->fails()) {
           return response()->json(['error' => $validator->errors()], 400);
       }
   
       $user = Auth::guard('api:jwt')->user();
       if (!$user) {
           return response()->json(['error' => 'No se pudo autenticar al usuario.'], 404);
       }

       $verificationCode = $request->input('verification_code');
       if (Hash::check($verificationCode, $user->token_verificacion)) {
           $user->email_verified_at = now();
           $user->verificado = true;
           $user->save();
           return response()->json(['message' => 'Cuenta activada correctamente.'], 200);
       } else {
           $user->verificado = false;
           $user->save();
           return response()->json(['error' => 'Código de verificación inválido.'], 400);
       }
   }

   public function verificar(Request $request)
   {
       $request->validate([
           'verification_code' => 'required|string|min:6|max:6',
       ]);

       $user = Auth::guard('api:jwt')->user();
       if (!$user) {
           return response()->json(['error' => 'Cuenta no encontrada.'], 404);
       }

       if (Hash::check($request->verification_code, $user->token_verificacion)) {
           $user->email_verified_at = now();
           $user->verificado = true;
           $user->save();
           return response()->json(['data' => $user], 200);
       } else {
           $user->verificado = false;
           $user->save();
           return response()->json(['error' => 'Código de verificación inválido.'], 400);
       }
   }

   /**
    * Get the authenticated User.
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function me()
   {
       if (!$user = auth('api:jwt')->user()) { 
           return response()->json(['error' => 'Unauthorized'], 401);
       } else {
           return response()->json(Auth::guard('api:jwt')->user());
       }
      
   }

   /**
    * Log the user out (Invalidate the token).
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function logout()
   {
       $user = Auth::guard('api:jwt')->user();
       $user->token_verificacion = null;
       $user->save();

       Auth::guard('api:jwt')->logout();
       return response()->json(['message' => 'Successfully logged out']);
   }

   /**
    * Refresh a token.
    *
    * @return \Illuminate\Http\JsonResponse
    */
   public function refresh()
   {
       return $this->respondWithToken(Auth::guard('api:jwt')->refresh());
   }

   /**
    * Get the token array structure.
    *
    * @param  string $token
    *
    * @return \Illuminate\Http\JsonResponse
    */
   protected function respondWithToken($token)
   {
       return response()->json([
           'data' => [
               'access_token' => $token,
               'token_type' => 'bearer',
               'expires_in' => Auth::guard('api:jwt')->factory()->getTTL() * 60
           ]
       ]);
   }
}
