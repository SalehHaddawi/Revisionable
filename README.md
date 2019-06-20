# Revisionable
a php trait for laravel for logging model activity to database and can also be used without models.

After adding The model and migration files.

in any model add ``` use RevisionableTrait ``` after the model class declration.

Like this:
```
.....
use App\Traits\RevisionableTrait;

class Client extends Model {

    use RevisionableTrait;
    
    .....

```

Any create, update or delete to the model will be logged to the database.

The ```Revision``` contains the following attributes beside the ```created_at``` and ```updated_at```:

| id  | model  | model_id | user_id|  event   | key    | desc   | old_value| new_value| ip |
| --  | -------| -------- | -------| -------- | -------| -------| -------- | -------- | -- |

The ```user_id``` attribute will be taken from ```auth()->id()```.

The ```event``` attribute **for a model** can be either ```create```, ```update``` or ```delete``` for non model you can use any event name,  ```event``` is the **only** required attribute.

The ```key``` attribute **for a model** will be the name of the model's attribute that has changed, 
for model creation it will be ```New``` + model class name.

The ```desc``` attribute is optional.

The ```old_value``` attribute **for a model** contains the old value before update, will be ```null``` for ```create``` and ```delete```.

The ```new_value``` attribute **for a model** contains the new value after update.

The ```ip``` attribute  will be taken from ```request()->ip()```.


**Note:** model creation is not logged by default
#### to enable model creation logging:
``` protected static $modelCreationRevision = true; ```

#### to exclude attributes from revision:
``` protected static $dontRevision = ['title','age'];```

```'title'``` and ```'age'``` will not be revisioned

#### to format attributes before revision:

``` protected static $formatRevision = ['title' => 'Title','age'=>'Age'];```

```'title'``` will be revisioned as ```'Title'``` and ```'age'``` will be revisioned as ```'Age'```

## to use it with none model:
you can place ```createNonModel``` function in any place you like to log an event like login, logout, downloading...

``` \App\Revision::createNonModel('login'); ```

Where ```'login'``` is the ```event``` name.

you can pass array of attributes to the ```createNonModel``` function:

``` \App\Revision::createNonModel('download_file', ['key'=> $file_name]); ```

