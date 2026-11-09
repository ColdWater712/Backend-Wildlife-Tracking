<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AggregateReport extends Model
{
    protected $table = 'aggregate_reports';
    protected $primaryKey = 'report_id';
    const CREATED_AT = 'generated_at'; // Map ke kolom 'generated_at'
    const UPDATED_AT = null;

    protected $fillable = ['generated_by', 'report_type', 'period_start', 'period_end', 'file_path'];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'generated_at' => 'datetime'];
}