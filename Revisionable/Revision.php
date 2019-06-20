<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    protected $fillable = [
        'model',
        'model_id',
        'user_id',
        'event',
        'key',
        'desc',
        'old_value',
        'new_value',
        'ip'
    ];

    public static function userHistory($user_id, $limit = -1, $page = -1)
    {
        if (!$user_id) {
            $user_id = auth()->id();
        }

        if($limit > -1 && $page > -1)
            return Revision::where([
                ['user_id', 'is not', null],
                ['user_id', '=', $user_id]
            ])->limit($limit)->offset(($page - 1) * $limit)->get();

        return Revision::where([
            ['user_id', 'is not', null],
            ['user_id', '=', $user_id]
        ])->get();
    }

    public static function createNonModel($event, $attr = [])
    {
        $revision = new Revision($attr);

        $revision->event = $event;

        if(!$revision->user_id)
            $revision->user_id = auth()->id();

        if(!$revision->ip)
            $revision->ip = request()->ip();

        $revision->save();
    }
}
