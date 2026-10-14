<?php

namespace App\Service\Auth;

use Illuminate\Support\Facades\Auth;
use App\Models\Empleado;
class LoginService{

    private Empleado $Empleado;
   public function authenticate(array $credentials): bool{
       if(Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])){
           session()->regenerate();
           return true;
       }
       return false;
   }

   public function logout(){
       if ($Empleado)
   }
}
