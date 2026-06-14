<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToUser, LogsTeamActivity;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'icon',
        'color',
    ];
}
