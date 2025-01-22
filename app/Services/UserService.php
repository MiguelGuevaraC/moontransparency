<?php
namespace App\Services;

use App\Models\Person;
use App\Models\User;

class UserService
{

    public function getUserById(int $id): ?User
    {
        return User::find($id);
    }

    public function createUser(array $data): string
    {
        return '';
    }
    
    public function updateUser($User, array $data)
    {
        return $User;
    }
    
    
    
 

    public function destroyById($id)
    {
        if ($id == 1) {
            return false; // No se permite la eliminación del usuario con ID 1
        }
        $User = User::find($id);

        if (!$User) {
            return false;
        }
        return $User->delete(); // Devuelve true si la eliminación fue exitosa
    }

}
