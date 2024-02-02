<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getAllUsers()
    {
        try {
            $users = $this->userService->getAllUsers();
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserById(int $id)
    {  
        try {
            $user = $this->userService->getUserById($id);
            return response()->json($user);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function insertUser(InsertUserRequest $request)
    {
        try {
            $user = $this->userService->insertUser($request->all());
            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateUser(UpdateUserRequest $request)
    {
        try {
            $user = $this->userService->updateUser($request->all());
            return response()->json($user);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser(int $id)
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json(['message' => 'Usuário deletado com sucesso']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkUserStatus(int $id)
    {
        try {
            $status = $this->userService->checkUserStatus($id);
            return response()->json(['status' => $status]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
