<?php

namespace Laravel\Sanctum;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub solo para PHPStan/Larastan: declara las columnas reales de
 * personal_access_tokens (ver db/schema.sql) que el modelo del paquete
 * no documenta via @property. No se carga en runtime.
 *
 * @property int $id
 * @property int $tokenable_id
 * @property string $tokenable_type
 * @property string $name
 * @property string $token
 * @property \DateTimeInterface|null $last_used_at
 * @property \DateTimeInterface|null $expires_at
 */
class PersonalAccessToken extends Model
{
}
