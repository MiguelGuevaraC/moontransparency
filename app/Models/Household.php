<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    protected $fillable = [
        'code',
    ];

    public function surveyeds()
    {
        return $this->hasMany(Surveyed::class);
    }

    public static function codePattern(): string
    {
        return '/^'.preg_quote(self::codePrefix(), '/').'-[0-9]{'.self::codeDigits().',}$/';
    }

    public static function formatCode(int $id): string
    {
        return sprintf('%s-%0'.self::codeDigits().'d', self::codePrefix(), $id);
    }

    private static function codePrefix(): string
    {
        return strtoupper(trim((string) config('households.code_prefix', 'HOG')));
    }

    private static function codeDigits(): int
    {
        return max(1, min(20, (int) config('households.code_digits', 8)));
    }
}
