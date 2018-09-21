Setup simulator for local dev (i.e, so you can work on the simulator):
```
git clone git@gitlab.squiz.net:perspective/Simulator.git
mkdir CommentingAPI
cd CommentingAPI
mkdir Projects
mkdir Projects\Commenting
```

Example composer.json for your project:

```json
{
    "name": "Perspective/Commenting",
    "description": "Commenting API",
    "repositories": [
        {
            "type": "path",
            "url": "../Simulator",
            "options": {
                "symlink": false
            }
        }
    ],
    "require": {
        "Perspective/Simulator": "@dev"
    }
}
```

Sample file (call it whatever, but you also need to create the custom type classes manually in Projects/Comments/CustomTypes/DataRecord/Thread/Comment.php to use them:
```php
<?php
namespace Commenting;

include 'vendor/autoload.php';
use PerspectiveSimulator\StorageFactory;

class API
{
    public static function addComment($id, $requestBody=null)
    {
        $store  = StorageFactory::getDataStore('comments');
        $thread = $store->getUniqueDataRecord('threadid', $id);
        if ($thread === null) {
            // Thread doesn't exist yet, so create it.
            $thread = $store->createDataRecord('Thread');
            $thread->setValue('threadid', $id);
        }

        $comment = $store->createDataRecord('Comment', $thread->getId());
        $comment->setValue('comment-content', $requestBody['comment']);
        return [$comment->getId() => $comment->getValue('comment-content')];
    }

    public static function getComments($id)
    {
        $store  = StorageFactory::getDataStore('comments');
        $thread = $store->getUniqueDataRecord('threadid', $id);
        if ($thread === null) {
            // Thread doesn't exist yet, so can't have any comments.
            return [];
        }

        // Direct children of type Comment are the top-level comments on this thread.
        $children = $thread->getChildren(1);
        $comments = [];
        foreach (array_keys($children) as $childid) {
            $record = $store->getDataRecord($childid);
            if ($record instanceof \Commenting\DataRecordType\Comment) {
                $comments[$record->getId()] = $record->getValue('comment-content');
            }
        }

        return $comments;
    }

    public static function updateComment($id, $requestBody=null)
    {
        $store   = StorageFactory::getDataStore('comments');
        $comment = $store->getDataRecord($id);
        if ($comment === null) {
            return [];
        }

        $comment->setValue('comment-content', $requestBody['comment']);
        return [$comment->getId() => $comment->getValue('comment-content')];
    }
}

StorageFactory::createDataStore('comments');
StorageFactory::createDataRecordProperty('comment-content', 'text');
StorageFactory::createDataRecordProperty('threadid', 'unique');

print_r(API::addComment('asset-123', ['comment' => 'This is a test comment']));
print_r(API::addComment('asset-123', ['comment' => 'This is another test comment']));
print_r(API::getComments('asset-123'));
print_r(API::updateComment('3.1', ['comment' => 'This is another UPDATED test comment']));
print_r(API::getComments('asset-123'));
```

Custom types have this format:
```php
<?php
namespace Commenting\DataRecordType;

use PerspectiveSimulator\DataRecord;

class Comment extends DataRecord
{


}//end class
```

Every time you change the simulator repo, you need to run:
```
rm -rf vendor/
composer update
```

