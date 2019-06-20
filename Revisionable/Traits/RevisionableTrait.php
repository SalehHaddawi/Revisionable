<?php
namespace App\Traits;
use App\Revision;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
trait RevisionableTrait {
    /*
    |--------------------------------------------------------------------------
    | trait attributes
    |--------------------------------------------------------------------------
    | this trait will look for the following attributes in a model
    |
    | 1. static $dontRevision (array)
    |       array of model attributes that you don't want to revision
    |
    |       EXAMPLE: protected static $dontKeep = ['title','age'];
    |
    | 2. static $formatRevision (array)
    |       array of model attributes with their formatted name
    |
    |       EXAMPLE: protected static $formatRevision = ['title' => 'Title','age' => 'Age'];
    |
    | 3. static $modelCreationRevision  (true/false)
    |       boolean value to indicate if the trait should revision creation of a model default is false
    |
    |       EXAMPLE: protected static $modelCreationRevision = true;
    |
    */
    private $dontRevisionAttr = array();
    private $formatRevisionAttr = array();
    private $formatRevisionAttrKeys = array();
    private $globalDontRevision = ['updated_at','remember_token'];
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
        // deleted event is called whenever a model is deleted
        static::deleted(function ($model) {
            $model->deletedRevision();
        });
    }
    /**LISTENERS**/
    public function createdRevision($model){
        $revisionCreation = isset(static::$modelCreationRevision) ? static::$modelCreationRevision : false;
        if(!$revisionCreation)
            return;

        // get model short name
        $modelName = self::getClassName($model);
        // get model primary key value
        // ($model->primaryKey) will return model primary key name
        $modelPrimaryKey = $model[$model->primaryKey];
        // the id of the logged in user
        $id = auth()->id();

        $revision = Revision::create([
            'model'                 => $modelName,
            'model_id'              => $modelPrimaryKey,
            'user_id'               => $id,
            'event'                 => 'create',
            'key'                   => 'New '.$modelName,
            'old_value'             => null,
            'new_value'             => null,
            'ip'                    => request()->ip(),
        ]);

        $revision->save();
    }
    public function updatedRevision($model){
        // array of keys that was changed in the model (from Laravel)
        $changesKeys = array_keys($model->changes);
        // get model short name
        $modelName = self::getClassName($model);
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
            if (in_array($changeKey, $this->globalDontRevision) || ($dontRevisionArray && in_array($changeKey, $dontRevisionArray))) {
                continue;
            }
            $revisions[] = array(
                'model'                 => $modelName,
                'model_id'              => $modelPrimaryKey,
                'user_id'               => $id,
                'event'                 => 'update',
                'key'                   => $this->getFormattedName($changeKey),
                'old_value'             => $model->original[$changeKey],
                'new_value'             => $model->changes[$changeKey],
                'created_at'            => new \DateTime(),
                'updated_at'            => new \DateTime(),
                'ip'                    => request()->ip(),
            );
        }
        // if any change
        if (count($revisions) > 0) {
            // create Revision model
            $revision = new Revision();
            // insert all changes to database
            DB::table($revision->getTable())->insert($revisions);
        }
    }
    public function deletedRevision($model){
        // get model short name
        $modelName = self::getClassName($model);
        // get model primary key value
        // ($model->primaryKey) will return model primary key name
        $modelPrimaryKey = $model[$model->primaryKey];
        // the id of the logged in user
        $id = auth()->id();

        $revision = Revision::create([
            'model'     => $modelName,
            'model_id'       => $modelPrimaryKey,
            'user_id'               => $id,
            'event'                 => 'delete',
            //'key'                 => $this->getFormattedName('deleted_at'),           // null
            'old_value'             => null,
            'new_value'             => new \DateTime(),
            'ip'                    => request()->ip(),
        ]);

        $revision->save();
    }
    /**MODEL**/
    public static function revisionHistory(){
        return DB::table('revisions')->where('model','=',self::getClassName(self::class))->get();
    }
    function revisions(){
        return $this->hasMany('App\Revision','model_id',$this->primaryKey)->where('model','=',$this->getClassName($this));
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
        if($this->formatRevisionAttr && !$this->formatRevisionAttrKeys){
            $this->formatRevisionAttrKeys = array_keys($this->formatRevisionAttr);
        }
        if(in_array($key, $this->formatRevisionAttrKeys)){
            return $this->formatRevisionAttr[$key];
        }
        return $key;
    }
    public static function getClassName($model){
        // generic solution
        $reflection = new ReflectionClass($model);
        return $reflection->getName();
    }
}
