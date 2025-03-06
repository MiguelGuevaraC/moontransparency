<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name_table',
        'name_image',
        'route',
        'proyect_id',
        'ally_id',
        'survey_id',
        'donation_id',
        'user_created_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];const filters = [
        'name_table'      => 'like',
        'proyect_id'      => '=',
        'ally_id'         => '=',
        'survey_id'       => '=',
        'donation_id'     => '=',
        'user_created_id' => '=',
    ];

    const sorts = [
        'id' => 'desc',
    ];

    public function proyect()
    {
        return $this->belongsTo(Proyect::class, 'proyect_id');
    }

    public function ally()
    {
        return $this->belongsTo(Ally::class, 'ally_id');
    }

    public function donation()
    {
        return $this->belongsTo(Donation::class, 'donation_id');
    }
    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function user_created()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }
}
