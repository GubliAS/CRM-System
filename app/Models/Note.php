<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, HasRecordUsers;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'notable_type',
        'notable_id',
        'body',
    ];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
