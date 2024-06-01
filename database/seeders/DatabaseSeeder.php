<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Comment;
use App\Models\Image;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Log;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    $admin = $user[0] = User::factory()->admin()->createOne([
      'name' => 'admin',
      'email' => 'admin@mail.ru',
    ]);
    $user_avatar[0] = Image::factory()->avatar($user[0])->createOne();
    $user[0]->avatar_id = $user_avatar[0]->id;
    $user[0]->save();

    $user[1] = User::factory()->createOne([
      'name' => 'user1',
      'email' => 'user1@mail.ru',
    ]);
    $user_avatar[1] = Image::factory()->avatar($user[1])->createOne();
    $user[1]->avatar_id = $user_avatar[1]->id;
    $user[1]->save();

    $user[2] = User::factory()->createOne([
      'name' => 'user2',
      'email' => 'user2@mail.ru',
    ]);
    $user_avatar[2] = Image::factory()->avatar($user[2])->createOne();
    $user[2]->avatar_id = $user_avatar[2]->id;
    $user[2]->save();

    $user[3] = User::factory()->createOne([
      'name' => 'user3',
      'email' => 'user3@mail.ru',
    ]);

    $tagsList = [
      'PHP', 'JS', 'functions', 'libraries', 'Namespaces', 'OOP',
      'Classes', 'Inheritance','Traits', 'Interfaces', 'Sessions',
      'Laravel', 'Symfony', 'Models', 'MVC',
      'Databases', 'PDO', 'Eloquent', 'Doctrine', 'SQL', 'MySQL', 'PostgreSQL',
      'VueJS', 'Angular', 'React', 'Lodash', 'Axios',
      'Vue-Router', 'Vuex', 'Vuelidate', 'Composition API', 'Options API', 'Components',
      'HTML', 'CSS', 'SCSS', 'Bootstrap', 'Tailwind',
      'http', 'https', 'www', 'server', 'system administration', 'Apache', 'Nginx',
      'API', 'Authorization', 'Tokens', 'Laravel Sanctum', 'WebSocket'
    ];
    /**
     * @var Collection<Tag>
     */
    $tags =  new Collection();
    foreach ($tagsList as $tagName) {
      $tags->push(Tag::factory()->generateByName($tagName)->createOne()) ;
    }

    $posts = Post::factory(100)->create();
    foreach ($posts as $post) {
      $post->tags()->attach($tags->random(random_int(1, 5)));
      // $post->comments()->saveMany(Comment::factory(random_int(0, 10))->create());
      // if (random_int(0, 9)) {
      //   $mainImage = Image::factory()->postImage($post->user()->getResults(), $post)->create();
      //   $post->main_image_id = $mainImage->id;
      //   $post->save();
      // }
    }
  }
};
