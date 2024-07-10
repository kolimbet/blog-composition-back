<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->boolean('is_admin')->default(false)->index();

      $table->boolean('is_tested')->default(false);
      $table->boolean('pre_moderation')->default(false);

      $table->boolean('is_banned')->default(false)->index();
      $table->foreignId('banned_by')->nullable()->constrained('users', 'id')->cascadeOnDelete();
      $table->dateTime('ban_time')->nullable();
      $table->text('ban_comment')->nullable();

      $table->foreignId('avatar_id')->nullable()->constrained('images', 'id')->cascadeOnDelete();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropSoftDeletes();
      $table->dropConstrainedForeignId('avatar_id');

      $table->dropColumn('ban_comment');
      $table->dropColumn('ban_time');
      $table->dropConstrainedForeignId('banned_by');
      $table->dropIndex(['is_banned']);
      $table->dropColumn('is_banned');

      $table->dropColumn('pre_moderation');
      $table->dropColumn('is_tested');

      $table->dropIndex(['is_admin']);
      $table->dropColumn('is_admin');
    });
  }
};
