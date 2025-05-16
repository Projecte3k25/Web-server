<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ratchet\ConnectionInterface;

class UsuariController extends Controller
{
    public static $jwt_key = "RISK_GAME";
    public static $usuaris = [];
    public static $usuaris_ids = [];

    public function login(Request $request){
        $password = $request->input("password");
        $login = $request->input("login");
        $user = DB::select('select * from usuaris where login = ? AND password = ?', [$login, md5($password)]);
        if(count($user) == 0){
          return response()->json([
            'message' => 'Unauthorized'
          ], 401);
        }    
        $payload = array(
            "id" => $user[0]->id,
            "iat" => (int)microtime(true),
        );

        $jwt = JWT::encode($payload, $this::$jwt_key, 'HS256');
        return response()->json([
          "id" => $user[0]->id,
          'token' => $jwt,
          'message' => 'Success'
        ]);
      }    

      public function version(Request $request){
        return response()->json([
          'version' => '1.0',
          'message' => 'Version'
        ]);
      } 

      public static function profile(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $from->send(json_encode([
          "method" => "profile",
          "data" => Usuari::find($userId)->makeHidden(['password','login']),
        ]));
      }
}
