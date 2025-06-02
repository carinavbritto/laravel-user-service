<?php

namespace App\Http\Controllers;

use App\Jobs\PublishUserCreated;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpAmqpLib\Message\AMQPMessage;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'uuid', 'name', 'email', 'created_at', 'updated_at')->get();
        return response()->json($users);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users'
            ], [
                'name.required' => 'O nome é obrigatório',
                'name.min' => 'O nome deve ter pelo menos 3 caracteres',
                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email deve ser válido',
                'email.unique' => 'Este email já está em uso'
            ]);

            $user = User::create($validated);

            try {
                \Log::info('Iniciando publicação do evento para o usuário', [
                    'uuid' => $user->uuid,
                    'name' => $user->name
                ]);

                $message = new AMQPMessage(
                    json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name
                    ]),
                    [
                        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                        'content_type' => 'application/json'
                    ]
                );

                $publisher = new PublishUserCreated($user->uuid, $user->name);
                $publisher->handle();

                \Log::info('Evento publicado com sucesso para o usuário', [
                    'uuid' => $user->uuid,
                    'name' => $user->name
                ]);
            } catch (\Exception $e) {
                \Log::error('Erro ao publicar evento: ' . $e->getMessage());
                \Log::error($e->getTraceAsString());
            }

            return response()->json($user, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erro de validação',
                'messages' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível criar o usuário'
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|min:3',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->all());

        return response()->json($user);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }
}
