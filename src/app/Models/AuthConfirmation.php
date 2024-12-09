<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthConfirmation extends Model
{
    use HasFactory;

    protected $table = 'auth_confirmations';

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',                
        'one_time_token_value',
        'one_time_token_expiration',
        'one_time_password',
    ];

    public function authenticationInformation()
    {
        return $this->belongsTo(AuthenticationInformation::class, 'user_id', 'user_id');
    }
}
