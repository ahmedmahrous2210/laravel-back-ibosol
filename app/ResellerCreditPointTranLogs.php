<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerCreditPointTranLogs extends Model
{
    protected $table = 'reseller_credit_assignment_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'debitor_id', 'creditor_id', 'created_by', 'credit_point','tr_type'
    ];

    public function creditorDetail(){
        return $this->belongsTo('App\IBOReseller', 'creditor_id')->select('id','email', 'name');
    }

    public function debitorDetail(){
        return $this->belongsTo('App\IBOReseller', 'debitor_id')->select('id','email', 'name');
    }

    public function creator(){
        return $this->belongsTo('App\IBOReseller', 'created_by')->select('id','email', 'name');
    }
}