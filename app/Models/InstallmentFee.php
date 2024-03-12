<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class InstallmentFee extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'due_date',
        'due_charges',
        'session_year_id',
        'school_id',
        'created_at',
        'updated_at'
    ];

    public function session_year(){
        return $this->belongsTo(SessionYear::class, 'session_year_id')->withTrashed();
    }

     //Getter Attributes
    public function getDueDateAttribute($value) {
        $data = getSchoolSettings('date_formate');
        return date($data['date_formate'] ?? 'd-m-Y' ,strtotime($value));
    }

    public function scopeOwner($query)
    {
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        if (Auth::user()->hasRole('Student')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }

    public function compulsory_fees()
    {
        return $this->hasMany(CompulsoryFee::class, 'installment_id')->withTrashed();
    }
}
