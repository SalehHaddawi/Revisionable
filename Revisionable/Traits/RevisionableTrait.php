<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use ReflectionClass;

trait RevisionableTrait {

    /*
    |--------------------------------------------------------------------------
    | trait attributes
    |--------------------------------------------------------------------------
    | this trait will look for the following attributes in a model
    |
    | 1. $dontRevision (static) array
    |       array of model attributes that you don't want to revision
    |
    |       EXAMPLE: protected static $dontKeep = ['title','age'];
    |
    | 2. $formatRevision (static) array
    |       array of model attributes with their formatted name

    |       EXAMPLE: protected static $formatRevision = ['title' => 'Title','age' => 'Age'];
    |
    */

    private $dontRevisionAttr = array();

    private $formatRevisionAttr = array();

    private $formatRevisionAttrKeys = array();

    public static function bootRevisionableTrait()
    {
        // created event is called whenever a model is created
        static::created(function ($model) {
            $model->createdRevision($model);
        });

        // updated event is called whenever a model is updated
        static::updated(function ($model) {
            $model->updatedRevision($model);
        });

        static::deleted(function ($model) {
            $model->deletedRevision();
        });
    }

    public function createdRevision($model){
        // get model short name
        $modelName = $this->getModelName($model);

        // get model primary key value
        // ($model->primaryKey) will return model primary key name
        $modelPrimaryKey = $model[$model->primaryKey];

        // the id of the logged in user
        $id = auth()->id();

        $revision = \App\Revision::create([
            'revisionable_type'     => $modelName,
            'revisionable_id'       => $modelPrimaryKey,
            'key'                   => self::CREATED_AT,
            'old_value'             => null,
            'new_value'             => new \DateTime(),
            'user_id'               => $id,
        ]);

        $revision->save();
    }

    public function updatedRevision($model){
        // array of keys that was changed in the model (from Laravel)
        $changesKeys = array_keys($model->changes);

        // get model short name
        $modelName = $this->getModelName($model);

        // get model primary key value
        // ($model->primaryKey) will return model primary key name
        $modelPrimaryKey = $model[$model->primaryKey];

        // the id of the logged in user
        $id = auth()->id();

        // revision array attributes
        $revisions = array();

        // get $dontRevision var value from model
        $dontRevisionArray = $this->getDontRevisionArray();

        foreach ($changesKeys as $changeKey) {

            if ($changeKey === 'updated_at' || ($dontRevisionArray && in_array($changeKey, $dontRevisionArray))) {
                continue;
            }

            $revisions[] = array(
                'revisionable_type'     => $modelName,
                'revisionable_id'       => $modelPrimaryKey,
                'key'                   => $this->getFormattedName($changeKey),
                'old_value'             => $model->original[$changeKey],
                'new_value'             => $model->changes[$changeKey],
                'user_id'               => $id,
                'created_at'            => new \DateTime(),
                'updated_at'            => new \DateTime(),
            );
        }

        // if any change
        if (count($revisions) > 0) {
            // create Revision model
            $revision = new \App\Revision();

            // insert all changes to database
            DB::table($revision->getTable())->insert($revisions);
        }
    }

    public function deletedRevision($model){
        // get model short name
        $modelName = $this->getModelName($model);

        // get model primary key value
        // ($model->primaryKey) will return model primary key name
        $modelPrimaryKey = $model[$model->primaryKey];

        // the id of the logged in user
        $id = auth()->id();

        $revision = \App\Revision::create([
            'revisionable_type'     => $modelName,
            'revisionable_id'       => $modelPrimaryKey,
            'key'                   => 'deleted_at',
            'old_value'             => null,
            'new_value'             => new \DateTime(),
            'user_id'               => $id,
        ]);

        $revision->save();
    }

    /**HELPERS**/

    public function getDontRevisionArray(){
        $dontRevisionArray = isset(static::$dontRevision) ? static::$dontRevision : null;
        return is_array($dontRevisionArray) ? $dontRevisionArray : null;
    }

    public function getFormatRevisionArray(){
        $formatRevisionArray = isset(static::$formatRevision) ? static::$formatRevision : null;
        return is_array($formatRevisionArray) ? $formatRevisionArray : null;
    }

    public function getFormattedName($key){
        if(!$this->formatRevisionAttr){
            $this->formatRevisionAttr = $this->getFormatRevisionArray();
        }

        if(!$this->formatRevisionAttrKeys){
            $this->formatRevisionAttrKeys = array_keys($this->formatRevisionAttr);
        }

        if(in_array($key, $this->formatRevisionAttrKeys)){
            return $this->formatRevisionAttr[$key];
        }

        return $key;
    }

    public function getModelName($model){
        // generic solution
        $reflection = new ReflectionClass($model);
        $reflection->getShortName();

        return $reflection->name;
    }
}