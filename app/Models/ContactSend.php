<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactSend extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name_contact',
        'subject',
        'description',
        'contact_email',
        'sender_email',
        'status',
        'ip_address',
        'user_agent',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'name_contact'=> 'like',
        'subject'=> 'like',
        'description'=> 'like',
        'contact_email'=> 'like',
        'sender_email'=> 'like',
        'status'=> 'like',
        'created_at'=> 'between',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'            => 'desc',
    ];
}
