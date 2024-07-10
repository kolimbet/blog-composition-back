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
   * @var Collection<User>|null
   */
  protected $users = null;

  /**
   * @var Collection<Image>|null
   */
  protected $avatars = null;

  /**
   * Array names of tags
   *
   * @var array
   */
  public $tagsNames = [
    'PHP', 'JS', 'functions', 'libraries', 'Namespaces', 'OOP',
    'Classes', 'Inheritance', 'Traits', 'Interfaces', 'Sessions',
    'Laravel', 'Symfony', 'Models', 'MVC',
    'Databases', 'PDO', 'Eloquent', 'Doctrine', 'SQL', 'MySQL', 'PostgreSQL',
    'VueJS', 'Angular', 'React', 'Lodash', 'Axios',
    'Vue-Router', 'Vuex', 'Vuelidate', 'Composition API', 'Options API', 'Components',
    'HTML', 'CSS', 'SCSS', 'Bootstrap', 'Tailwind',
    'http', 'https', 'www', 'server', 'system administration', 'Apache', 'Nginx',
    'API', 'Authorization', 'Tokens', 'Laravel Sanctum', 'WebSocket'
  ];

  public function __construct()
  {
    $this->users = new Collection();
    $this->avatars = new Collection();
  }

  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    $this->createUser('admin', 'admin@mail.ru', true, true);
    $this->createUser('electonic', 'electonic@mail.ru', true, true);

    for ($i = 1; $i <= 15; $i++) {
      $this->createUser("user{$i}", "user{$i}@mail.ru", random_int(0, 1));
    }

    /**
     * @var Collection<Tag>
     */
    $tags =  new Collection();
    foreach ($this->tagsNames as $tagName) {
      $tags->push(Tag::factory()->generateByName($tagName)->createOne());
    }

    $posts = Post::factory(100)->create();
    foreach ($posts as $post) {
      $post->tags()->attach($tags->random(random_int(1, 5)));

      $likesCounter = random_int(0, $this->users->count());
      if ($likesCounter) {
        $likesBy = $this->users->random($likesCounter);
        $likesBy->each(function ($user) use ($post) {
          $post->likes()->create(['user_id' => $user->id]);
        });
      }

      if ($post->is_published) {
        $commentsCounter = random_int(0, 25);
        if ($commentsCounter) {
          Comment::factory($commentsCounter, ['post_id' => $post->id])->create();
        }
      }
    }
  }

  /**
   * Create new User
   *
   * @param string $name
   * @param string $mail
   * @param boolean $isAdmin
   * @return void
   */
  protected function createUser($name, $mail, $withAvatar = false, $isAdmin = false)
  {
    /**
     * @var User|null
     */
    $newUser = null;

    if ($isAdmin) {
      $newUser = User::factory()->admin()->createOne([
        'name' => $name,
        'email' => $mail,
      ]);
    } else {
      $newUser = User::factory()->simple_user()->createOne([
        'name' => $name,
        'email' => $mail,
      ]);
    }

    if ($withAvatar) {
      $newAvatar = Image::factory()->avatar($newUser)->createOne();
      $newUser->avatar_id = $newAvatar->id;
      $newUser->save();
      $this->avatars->push($newAvatar);
    }

    $this->users->push($newUser);
  }
};