<?php
namespace App\Http\Controllers;
use App\Events\UserUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Mail\AcountActivation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Hashes;
use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->middleware('auth:api:jwt', ['except' => ['register', 'login']]);
        $this->setName('Usuarios');
    }

    public function register(Request $request) {
        $this->setIgnore(true);
        $validate =  Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                "confirmar_password"    => "required|same:password",
            ]
        );

        if($validate->fails()){
            return response()->json([
                "msg" => "Error al validar los datos",
                "error" => $validate->errors()
        ], 422);
        }

        $user = new User();
        $user->nombre = $request->nombre;
        $user->apellido_paterno = $request->apellido_paterno;
        $user->apellido_materno = $request->apellido_materno;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        $user->makeHidden('password');

        Mail::to($user->email)->send(new AcountActivation($user));

        return response()->json([
            "msg" => "Usuario registrado exitosamente",
            "data" => $user
        ], 201);
       

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $usuarios = User::withTrashed()->get();
        return $this->createResponse(200, $usuarios);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
  
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $caracteristica = User::find($id);
        if ($caracteristica) {    
            return $this->createResponse(200, $caracteristica);
        }
        return $this->createResponse(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->createResponse(404);
        }
        
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            "email"         => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
           
        ]);
        $user->nombre = $request->get('nombre', $user->nombre);
        $user->apellido_paterno = $request->get('apellido_paterno', $user->apellido_paterno);
        $user->apellido_materno = $request->get('apellido_materno', $user->apellido_materno);
        $user->email = $request->get('email', $user->email);
      

        event(new UserUpdated($user->id));

        $user->save();

        return $this->createResponse(200, $user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $caracteristica = User::withTrashed()->find($id);
        if ($caracteristica) {
            if ($caracteristica->trashed()) {
                $caracteristica->restore();
            } else {
                User::destroy($id);
            }
            event(new UserUpdated($caracteristica->id));
            return $this->createResponse(200);
        }
        return $this->createResponse(404);
    }
    
}
