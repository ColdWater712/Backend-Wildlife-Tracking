<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AggregateReport extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'aggregate_reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'generated_by', 'report_type', 'period_start', 'period_end',
        'report_data', 'generated_at', 'file_path',
    ];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'report_data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by', 'user_id');
    }
}
