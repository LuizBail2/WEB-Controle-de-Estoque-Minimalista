<?php
 
namespace App\Models;
 
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\LogsTeamActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Product extends Model
{
    use HasFactory, BelongsToUser, LogsTeamActivity;
 
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'category',
        'quantity',
        'minimum_quantity',
        'price',
        'supplier',
        'location',
        'note',
    ];
 
    public function movements()
    {
        return $this->hasMany(Movement::class);
    }
}