<?php

namespace App\Enums;

enum Rol: int
{
    case ADMIN = 1;
    case USUARIO = 2;

    // Método auxiliar opcional para obtener la etiqueta legible
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'admin',
            self::USUARIO => 'usuario',
        };
    }
}