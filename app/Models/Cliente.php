<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_persona', 'fecha_limite', 'estado'
    ];

    public function persona(): BelongsTo {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function membresias(): HasMany {
        return $this->hasMany(Membresia::class, 'id_cliente');
    }
}