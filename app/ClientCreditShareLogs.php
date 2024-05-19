<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCreditShareLogs extends Model
{
    protected $table = 'client_credit_assignment_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'debitor_id', 'creditor_id', 'created_by', 'credit_point', 'tr_type'
    ];

    public function creditorDetail(){
        return $this->belongsTo('App\Clients', 'creditor_id');
    }

    public function debitorDetail(){
        return $this->belongsTo('App\Clients', 'debitor_id');
    }

    public function creator(){
        return $this->belongsTo('App\Clients', 'created_by');
    }
}