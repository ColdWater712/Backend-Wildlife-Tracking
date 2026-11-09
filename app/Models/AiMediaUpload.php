<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AiMediaUpload extends Model
{
    protected $table = 'ai_media_uploads';
    protected $primaryKey = 'media_id';
    const CREATED_AT = 'uploaded_at';
    const UPDATED_AT = null;

    protected $fillable = ['uploaded_by', 'report_id', 'media_type', 'file_path'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function prediction() { return $this->hasOne(AiPrediction::class, 'media_id', 'media_id'); }
}