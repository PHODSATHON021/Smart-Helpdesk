<?php
declare(strict_types=1);

namespace App\Core;

/** กฎที่รองรับ: required | min:n | max:n | email | int | in:a,b,c | between:a,b */
final class Validator
{
    private array $errors = [];

    private function __construct() {}

    public static function make(array $data, array $rules): self
    {
        $v = new self();
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name !== 'required' && ($value === null || $value === '')) {
                    continue;
                }
                $msg = match ($name) {
                    'required' => ($value === null || $value === '') ? "กรุณากรอก {$field}" : null,
                    'min'      => mb_strlen((string) $value) < (int) $arg ? "{$field} ต้องมีอย่างน้อย {$arg} ตัวอักษร" : null,
                    'max'      => mb_strlen((string) $value) > (int) $arg ? "{$field} ต้องไม่เกิน {$arg} ตัวอักษร" : null,
                    'email'    => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "{$field} รูปแบบอีเมลไม่ถูกต้อง" : null,
                    'int'      => filter_var($value, FILTER_VALIDATE_INT) === false ? "{$field} ต้องเป็นตัวเลข" : null,
                    'in'       => !in_array((string) $value, explode(',', (string) $arg), true) ? "{$field} ไม่อยู่ในรายการที่อนุญาต" : null,
                    'between'  => self::between($value, (string) $arg) ? null : "{$field} อยู่นอกช่วงที่กำหนด",
                    default    => null,
                };
                if ($msg !== null) {
                    $v->errors[$field][] = $msg;
                    break;
                }
            }
        }
        return $v;
    }

    private static function between(mixed $value, string $arg): bool
    {
        [$a, $b] = array_map('intval', explode(',', $arg));
        return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= $a && (int) $value <= $b;
    }

    public function fails(): bool   { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }

    public function firstError(): string
    {
        return $this->errors ? (string) reset($this->errors)[0] : '';
    }
}
