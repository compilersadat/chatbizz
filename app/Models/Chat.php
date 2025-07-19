<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'order_id',
        'sender_id',
        'receiver_id',
        'message',
        'sender_type',
    ];
}
