<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AggregateReport extends Model
{
    protected $table = 'aggregate_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'generated_by', 'report_type', 'period_start', 'period_end', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by', 'user_id');
    }
}