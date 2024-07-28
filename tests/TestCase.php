<?php

use Illuminate\Database\Eloquent\Model;
use Itjonction\Blockcache\HasCacheKey;
use Illuminate\Database\Capsule\Manager as DB;

abstract class TestCase extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->migrateTables();
    }
    protected function setUpDatabase()
    {
        $database = new DB;
        $database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $database->bootEloquent();
        $database->setAsGlobal();
    }
    protected function migrateTables()
    {
        DB::schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });
    }
    protected function makePost(): Post
    {
        $post = new Post;
        $post->title = 'My first post';
        $post->save();
        return $post;
    }
}

class Post extends Model
{
    use HasCacheKey;
}

