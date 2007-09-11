<?php
// Define your test Propel class with behavior applied here
define('TEST_CLASS', 'Article');

// Autofind the first available app environment
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
$apps_dir = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// Symfony test env bootstrap
require_once($sf_root_dir.'/test/bootstrap/functional.php');
require_once($sf_symfony_lib_dir.'/vendor/lime/lime.php');

if (!defined('TEST_CLASS') or !class_exists(TEST_CLASS))
{
  // Don't run tests
  return;
}

$exceptions = array();

// initialize database manager
$databaseManager = new sfDatabaseManager();
$databaseManager->initialize();
$con = Propel::getConnection();

// start tests
$t = new lime_test(42, new lime_output_color());

$t->ok(sfPropelActAsRatableBehavior::isRatable(TEST_CLASS), 
       sprintf('isRatable() class %s is ratable', TEST_CLASS));

try
{
  $obj = _create_object();
  $obj->setTitle('A test object');
  $obj->save();
  $obj2 = _create_object();
  $obj2->setTitle('Another test object');
  $obj2->save();
}
catch (Exception $e)
{
  $t->fail($e->getMessage());
}

$obj_pk = $obj->getPrimaryKey();
$t->ok(!is_null($obj_pk), 'getPrimaryKey() Test Object saved');
$obj2_pk = $obj2->getPrimaryKey();
$t->ok(!is_null($obj2_pk), 'getPrimaryKey() Other test Object saved');

// Override any existing max_rating parameter
sfConfig::set(
    sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_max_rating', 
            get_class($obj)), 5);
$t->is($obj->getMaxRating(), 5, 'getMaxRating() retrieve correct value');
sfConfig::set(
    sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_max_rating', 
            get_class($obj)), 10);
$t->is($obj->getMaxRating(), 10, 'getMaxRating() retrieve correct value, even when changed');

$max_rating = $obj->getMaxRating();
$t->isa_ok($max_rating, 'integer', 'getMaxRating() MAX_RATING is an integer');

$t->is($obj->getTitle(), 'A test object', 'getTitle() Object has been created');
$t->is($obj->hasBeenRated(), false, 'hasBeenRated() Object has not been rated yet');

// Tests will be IP address based
$user_1_hash = md5('200.123.123.123');
$user_2_hash = md5('78.98.112.254');

$t->ok(!$obj->hasBeenRatedByUser($user_1_hash), 'hasBeenRatedByUser() Object has not been rated by user 1 yet');

# User 1 overrate object 1
try
{
  $obj->setRating(11, $user_1_hash);
  $t->fail('setRating() It is possible to overrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to overrate an object');
}

# User 1 rate with a negative value
try
{
  $obj->setRating(-1, $user_1_hash);
  $t->fail('setRating() It is possible to underrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to underrate an object');
}

# User 1 rate with a string
try
{
  $obj->setRating('rototo', $user_1_hash);
  $t->fail('setRating() It is possible to misrate an object :(');
}
catch (Exception $e)
{
  $t->pass('setRating() It is impossible to misrate an object');
}

# User 1 rate object 1 correctly
$u1_rating = 10;
$t->ok($obj->setRating($u1_rating, $user_1_hash), 'setRating() Object rated OK by user 1 to '.$u1_rating);
$t->ok($obj->hasBeenRated(), 'hasBeenRated() Object has been rated');
$t->is($obj->hasBeenRatedByUser($user_1_hash), true, 'hasBeenRatedByUser() Object has been rated by user 1');
$t->is($obj->hasBeenRatedByUser($user_2_hash), false, 'hasBeenRatedByUser() Object has not been rated by user 2 yet');

$t->is($obj->getRating(), $u1_rating, 'getRating() rating retrieval OK');
$t->is($obj->getUserRating($user_1_hash), $u1_rating, 'getUserRating() user rating retrieval OK');

# User 2 rate object 1
$u2_rating = 5;
$t->ok($obj->setRating($u2_rating, $user_2_hash), 'setRating() Object rated by user 2 to '.$u2_rating);
$t->ok($obj->hasBeenRated(), 'hasBeenRated() Object has been rated');
$t->ok($obj->hasBeenRatedByUser($user_2_hash), 'hasBeenRatedByUser() Object has been rated by user 2');

$t->is($obj->getRating(), 7.5, 'getRating() rating retrieval OK');
$t->is($obj->getUserRating($user_2_hash), $u2_rating, 'getUserRating() user rating retrieval OK');

# User 1 rates object 2
$obj2->setRating(5, $user_1_hash);
$t->is($obj2->getUserRating($user_1_hash), 5, 'getUserRating() user rating retrieval OK');
$t->is($obj2->getRating(), 5, 'getRating() rating ok');
$obj2->clearRatings();
$t->is($obj2->getRating(), null, 'clearRatings() clear rating ok');

# User 2 changes his rating for object 1
$u2_rating = 8;
$t->ok($obj->setRating($u2_rating, $user_2_hash), 'setRating() User 2 changes his rating to '.$u2_rating);
$t->ok($obj->hasBeenRatedByUser($user_2_hash), 'hasBeenRatedByUser() Object is still rated by user 2');

$t->is($obj->getRating(), 9, 'getRating() rating retrieval = 9');
$t->is($obj->getUserRating($user_2_hash), $u2_rating, 'getUserRating() user rating retrieval OK');

# User 1 changes his rating
$u1_rating = 2;
$t->ok($obj->setRating($u1_rating, $user_1_hash), 'setRating() User 1 changes his rating to '.$u1_rating);
$t->ok($obj->hasBeenRatedByUser($user_1_hash), 'hasBeenRatedByUser() Object is still rated by user 1');

$t->is($obj->getRating(), 5, 'getRating() rating retrieval OK');
$t->is($obj->getUserRating($user_1_hash), $u1_rating, 'getUserRating() user rating retrieval OK');

# User 1 cancel his rating
$t->ok($obj->clearUserRating($user_2_hash), 'cleanUserRating() User 2 cleans his rating');
$t->ok(!$obj->hasBeenRatedByUser($user_2_hash), 'hasBeenRatedByUser() Object has now not been rated by user 2');
$t->is($obj->getRating(), $u1_rating, 'getRating() Object rating has been updated');

$t->ok($obj->clearRatings(), 'cleanRatings() All ratings are cleared');
$t->is($obj->getRating(), NULL, 'getRating() Rating is now NULL for this object');

// Rating based on a 12 max rating
$obj->clearRatings();
$obj2->clearRatings();
sfConfig::set(
    sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_max_rating', 
            get_class($obj)), 12);

$obj->setRating(6, $user_1_hash);
$obj->setRating(6, $user_2_hash);
$t->is($obj->getRating(), 6, 'getRating() base12 ok');
$obj->setRating(12, $user_2_hash);
$t->is($obj->getRating(), 9, 'getRating() base12 ok');
$obj->setRating(3, $user_1_hash);
$t->is($obj->getRating(), 7.5, 'getRating() base12 ok');

$t->diag('Tests are now terminated');

// Delete objects
$obj->delete();
$obj2->delete();


// test object creation
function _create_object()
{
  if (!defined('TEST_CLASS') or is_null(TEST_CLASS))
  {
    throw new Exception('No TEST_CLASS constant has been defined');
  }
  if (!class_exists(TEST_CLASS))
  {
    throw new Exception(sprintf('Unknow class "%s". Did you clear the cache ?', 
                                TEST_CLASS));
  }
  $classname = TEST_CLASS;
  return new $classname;
}
