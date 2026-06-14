<?php

namespace App\Models;


use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToUser, LogsTeamActivity;

    protected $fillable = [
        'user_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'document',
        'address',
        'note',
    ];
}
