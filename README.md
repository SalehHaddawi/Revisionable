# Revisionable
a php trait for laravel for logging model activity to database and can also be used without models.

After adding The model and migration files.

in any model add ``` use RevisionableTrait ``` after the model class declration.

to use it with none model: ``` \App\Revision::createNonModel('login'); ```

``` 'login' ``` can be any message.

#### to exclude attributes from revision:
``` protected static $dontRevision = ['title','age'];```

```'title'``` and ```'age'``` will not be revisioned

#### to format attributes before revision:
``` protected static $formatRevision = ['title' => 'Title','age'=>'Age'];```

```'title'``` will be revisioned as ```'Title'``` and ```'age'``` will be revisioned as ```'Age'```
