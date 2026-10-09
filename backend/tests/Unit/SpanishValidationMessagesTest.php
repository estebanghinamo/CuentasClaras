<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * lang/es/validation.php traduce los mensajes built-in de Laravel (antes
 * quedaban en ingles porque el archivo no existia, pese a APP_LOCALE=es) -
 * regresion puntual para la regla que motivo el fix (M-11 due_date).
 */
class SpanishValidationMessagesTest extends TestCase
{
    public function test_after_rule_message_is_in_spanish_with_humanized_attribute(): void
    {
        $validator = Validator::make(['due_date' => '2020-01-01'], ['due_date' => 'after:today']);

        $this->assertSame(
            'El campo fecha límite debe ser una fecha posterior a today.',
            $validator->errors()->first('due_date'),
        );
    }

    public function test_required_rule_message_is_in_spanish(): void
    {
        $validator = Validator::make([], ['name' => 'required']);

        $this->assertSame('El campo nombre es obligatorio.', $validator->errors()->first('name'));
    }
}
