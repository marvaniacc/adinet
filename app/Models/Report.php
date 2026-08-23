<?php

namespace App\Models;

use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'title',
        'type',
        'description',
        'file_path',
        'file_name',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
        ];
    }
}
