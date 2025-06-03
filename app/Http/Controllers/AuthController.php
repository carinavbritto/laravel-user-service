<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints de autenticação"
 * )
 */
class AuthController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Login de usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=6)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login realizado com sucesso"),
     *     @OA\Response(response=401, description="Credenciais inválidas")
     * )
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Credenciais inválidas'
                ], 401);
            }

            return $this->createNewToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao realizar login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Registro de novo usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", minLength=2),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", minLength=6)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuário registrado com sucesso"),
     *     @OA\Response(response=400, description="Erro de validação")
     * )
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = auth()->login($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Usuário registrado com sucesso',
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Desloga o usuário atual",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso"
     *     )
     * )
     */
    public function logout()
    {
        try {
            auth()->logout();
            return response()->json([
                'status' => 'success',
                'message' => 'Usuário deslogado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao realizar logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     summary="Atualiza o token de autenticação",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token atualizado com sucesso"
     *     )
     * )
     */
    public function refresh()
    {
        try {
            return $this->createNewToken(auth()->refresh());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/user-profile",
     *     summary="Obtém o perfil do usuário autenticado",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Perfil do usuário retornado com sucesso"
     *     )
     * )
     */
    public function userProfile()
    {
        try {
            return response()->json([
                'status' => 'success',
                'user' => auth()->user()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar perfil do usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'status' => 'success',
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ],
            'user' => auth()->user()
        ]);
    }
}
