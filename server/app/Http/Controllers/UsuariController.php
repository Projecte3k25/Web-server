<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ratchet\ConnectionInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class UsuariController extends Controller
{
  public static $jwt_key = "RISK_GAME";
  public static $usuaris = [];
  public static $usuaris_ids = [];

  public function login(Request $request)
  {
    $password = $request->input("password");
    $login = $request->input("login");
    $user = DB::select('select * from usuaris where login = ? AND password = ?', [$login, md5($password)]);
    if (count($user) == 0) {
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

  public function version(Request $request)
  {
    return response()->json([
      'version' => '1.0',
      'message' => 'Version'
    ]);
  }

  public static function profile(ConnectionInterface $from, $data)
  {
    $userId = UsuariController::$usuaris_ids[$from->resourceId];
    $from->send(json_encode([
      "method" => "profile",
      "data" => Usuari::find($userId)->makeHidden(['password', 'login']),
    ]));
  }

  public function uploadAvatar(Request $request)
  {
    try {
      $token = $request->header('Authorization');
      if (!str_starts_with($token, 'Bearer ')) {
        return response()->json(['message' => 'No autorizado'], 401);
      }

      $token = substr($token, 7);
      $decoded = JWT::decode($token, new Key(UsuariController::$jwt_key, 'HS256'));
      $id = $decoded->id;
      $user = Usuari::find($id);
      if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado'], 404);
      }

      $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
      ]);

      $imageFile = $request->file('avatar');
      $filename = $id . '.jpg';
      $destinationFolder = public_path('/media/avatars');
      $destinationPath = $destinationFolder . '/' . $filename;

      if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0755, true);
      }

      $manager = new ImageManager(new Driver());
      $image = $manager->read($imageFile->getPathname());

      $width = $image->width();
      $height = $image->height();
      $size = min($width, $height);

      $image->crop($size, $size, ($width - $size) / 2, ($height - $size) / 2);
      $image->resize(300, 300);
      $image->save($destinationPath, quality: 90);

      $user->avatar = '/media/avatars/' . $filename;
      $user->save();

      return response()->json([
        'avatarPath' => asset('/media/avatars/' . $filename),
      ]);
    } catch (\Throwable $e) {
      \Log::error('Error al subir avatar: ' . $e->getMessage());
      return response()->json([
        'message' => 'Error interno al subir la imagen',
        'error' => $e->getMessage() //debug
      ], 500);
    }
  }
}
