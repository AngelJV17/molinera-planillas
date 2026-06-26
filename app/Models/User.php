<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'must_change_password',
    ];

    /**
     * Atributos ocultos en respuestas.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'status'               => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Trabajador vinculado al usuario.
     *
     * Un usuario puede estar vinculado a un trabajador.
     * Ejemplo:
     * - Super Admin: usuario sin trabajador.
     * - Trabajador con acceso: usuario vinculado a employee.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
}
