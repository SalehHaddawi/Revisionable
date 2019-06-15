<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    protected $fillable = [
        'revisionable_type',
        'revisionable_id',
        'key',
        'old_value',
        'new_value',
        'user_id',
    ];

    public static function createNonModel($key)
    {
        $revision = new Revision();

        $revision->revisionable_type = 'NONE';
        $revision->revisionable_id = -1;
        $revision->user_id = auth()->id();
        $revision->key = $key;
        $revision->old_value = null;
        $revision->new_value = null;

        $revision->save();
    }
}
