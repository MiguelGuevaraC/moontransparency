<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name','cadena', 'ubigeo_code', 'province_id'];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function getConcatenado()
    {
        $province = $this->province; // Accedemos a la relación province
        $department = $province->department; // A través de la provincia, accedemos a la relación department

        return "{$this->ubigeo_code}-{$department->name}-{$province->name}-{$this->name}";
    }

}
