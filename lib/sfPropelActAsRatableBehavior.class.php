<?php
/*
 * This file is part of the sfPropelActAsRatableBehavior package.
 *
 * (c) 2007 Nicolas Perriault <nperriault@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This Propel behavior aims at providing rating capabilities on any Propel
 * object
 *
 * @package    plugins
 * @subpackage rating 
 * @author     Nicolas Perriault <nperriault@gmail.com>
 * @author     Fabian Lange
 */
class sfPropelActAsRatableBehavior
{
  
  /**
   * Default default max rating
   */
  const DEFAULT_MAX_RATING = 5;
  
  /**
   * Default float precision
   */
  const DEFAULT_PRECISION = 2;
  
  /**
   * Counts ratings made on given ratable object.
   * 
   * @param  BaseObject  $object
   * @return int
   */
  public function countRatings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doCount($c);
  }

  /**
   * Retrieves ratable object configured rating column name.
   * 
   * @param  BaseObject  $object
   * @return string
   */
  protected static function getRatingColumn(BaseObject $object)
  {
//    $column = sfConfig::get(
//      sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_rating_column', 
//              get_class($object)));
//    $peer = get_class($object).'Peer';
//    $available_columns = call_user_func(array($peer, 'getFieldNames'), 
//                                        BasePeer::TYPE_COLNAME, 
//                                        $column);
//    if (in_array($column, $available_columns))
//    {
//      return $column;
//    }
  }   
  
  /**
   * Retrieves configured float precision for ratings
   * 
   * @param  int  $default_precision
   * @return int
   */
  protected static function getPrecision($default_precision = null)
  {
    if (is_null($default_precision))
    {
      $default_precision = self::DEFAULT_PRECISION;
    }
    return sfConfig::get('app_rating_precision', $default_precision);
  }
  
  /**
   * Retrieves an existing rating object, or return a new empty one
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique user primary key
   * @return sfRating
   * @throws sfPropelActAsRatableException
   **/
  protected static function getOrCreate(BaseObject $object, $user_id = null)
  {
    if ($object->isNew())
    {
      throw new sfPropelActAsRatableException('Unsaved objects are not ratable');
    }
    
    if (is_null($user_id))
    {
      return new sfRating();
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $user_rating = sfRatingPeer::doSelectOne($c);
    return is_null($user_rating) ? new sfRating() : $user_rating;
  }

  /**
   * Clear all ratings for an object
   *
   * @param  BaseObject  $object
   **/
  public function clearRatings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doDelete($c);
  }

  /**
   * Clear user rating for an object
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   **/
  public function clearUserRating(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfPropelActAsRatableException('Impossible to clear a user rating with no user primary key provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    return sfRatingPeer::doDelete($c);
  }

  /**
   * Checks if an Object has been rated
   *
   * @param  BaseObject  $object
   **/
  public function hasBeenRated(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doCount($c) > 0;
  }

  /**
   * Checks if an Object has been rated by a user
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique reference to a user
   **/
  public function hasBeenRatedByUser(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfPropelActAsRatableException(
        'Impossible to check a user rating with no user primary key provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    return (sfRatingPeer::doCount($c) > 0);
  }
  
  /**
   * Old method to set maximum rating in a class constant
   * This stays here for compability purpose
   * 
   * @param  BaseObject  $object
   * @return int
   */
  protected static function getDefaultMaxRating(BaseObject $object)
  {
    $max_rating = @constant(get_class($object).'::MAX_RATING');
    if (!is_int($max_rating))
    {
      $max_rating = self::DEFAULT_MAX_RATING;
    }
    return $max_rating;
  }

  /**
   * Retrieves maximum rating for given object
   * 
   * @param  BaseObject  $object  Propel object instance
   * @return int
   * @throws sfPropelActAsRatableException
   */
  public function getMaxRating(BaseObject $object)
  {
    $max_rating = sfConfig::get(
      sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_max_rating', 
              get_class($object)));
    
    if (is_null($max_rating))
    {
      $max_rating = self::getDefaultMaxRating($object);
    }
    
    if (!is_int($max_rating))
    {
      throw new sfPropelActAsRatableException(
        'The max_rating parameter must be an integer');
    }
    
    if (is_float($max_rating) && floor($max_rating) != $max_rating) // yeah, php typing sucks...
    {
      throw new sfPropelActAsRatableException(
        sprintf('You cannot type %s::MAX_RATING as float (you provided "%s")', 
                get_class($object),
                $max_rating));
    }
    
    if ($max_rating < 2)
    {
      throw new sfPropelActAsRatableException(
        'The max_rating parameter must be an integer greater than 1');
    }
    
    return $max_rating;
  }

  /**
   * Retrieves the object rating
   *
   * @param  BaseObject  $object
   * @param  int         $precision   Result float precision
   * @return float
   **/
  public function getRating(BaseObject $object, $precision=2)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_ratings', 'COUNT('.sfRatingPeer::ID.')');
    $c->addAsColumn('total', 'SUM('.sfRatingPeer::RATING.')');
    $c->addGroupByColumn(sfRatingPeer::RATABLE_MODEL);
    $rs = sfRatingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    while ($rs->next())
    {
      $nb_ratings = $rs->getInt('nb_ratings');
      $total      = $rs->getInt('total');
      if (!$nb_ratings or $nb_ratings === 0)
      {
        return NULL; // Object has not been rated yet
      }
      return round($total / $nb_ratings, self::getPrecision($precision));
    }
  }
  
  /**
   * Gets the object rating details
   *
   * @author Fabian Lange
   * @author Nicolas Perriault
   * @param  BaseObject  $object
   * @param  boolean     $include_all  Shall we include all available ratings?
   * @return associative array containing (rating => count)
   **/
  public function getRatingDetails(BaseObject $object, $include_all = false)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_ratings', 'COUNT('.sfRatingPeer::ID.')');
    $c->addAsColumn('rating', sfRatingPeer::RATING);
    $c->addGroupByColumn(sfRatingPeer::RATING);
    $rs = sfRatingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    $details = array();
    while ($rs->next())
    {
      $details = $details + array ($rs->getInt('rating') => (int)$rs->getString('nb_ratings'));
    }
    if ($include_all === true)
    {
      for ($i=1; $i<=$object->getMaxRating(); $i++)
      {
        if (!array_key_exists($i, $details))
        {
          $details[$i] = 0;
        }
      }
    }
    ksort($details);
    return $details;
  }
  
  /**
   * Gets the object rating for given user pk
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   * @return int or false
   **/
  public function getUserRating(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new sfPropelActAsRatableException(
        'Impossible to get a user rating with no user primary key provided');
    }
    
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_ID, $user_id);
    $rating_object = sfRatingPeer::doSelectOne($c);
    if (!is_null($rating_object))
    {
      return $rating_object->getRating();
    }
  }

  /**
   * Rates the Object
   *
   * @param  BaseObject  $object
   * @param  int         $rating
   * @param  mixed       $user_id  Optionnal unique reference to user
   * @throws sfPropelActAsRatableException
   **/
  public function setRating(BaseObject $object, $rating, $user_id = null)
  {
    if (is_float($rating) && floor($rating) != $rating)
    {
      throw new sfPropelActAsRatableException(
        sprintf('You cannot rate an object with a float (you provided "%s")', 
                $rating));
    }
    
    $rating = (int)$rating;
    
    if ($rating > $object->getMaxRating())
    {
      throw new sfPropelActAsRatableException(
        sprintf('Maximum rating is %d', $object->getMaxRating()));
    }
    
    if ($rating < 1)
    {
      throw new sfPropelActAsRatableException('Minimum rating is 1');
    }
    
    $rating_object = self::getOrCreate($object, $user_id);
    $rating_object->setRatableModel(get_class($object));
    $rating_object->setRatableId($object->getPrimaryKey());
    $rating_object->setUserId($user_id);
    $rating_object->setRating($rating);
    return $rating_object->save();
  }
  
  /**
   * Deletes all rating for a ratable object (delete cascade emulation)
   * 
   * @param  BaseObject  $object
   */
  public function preDelete(BaseObject $object)
  {
    try
    {
      $c = new Criteria();
      $c->add(sfRatingPeer::RATABLE_ID, $object->getPrimaryKey());
      sfRatingPeer::doDelete($c);
    }
    catch (Exception $e)
    {
      throw new sfPropelActAsRatableException(
        'Unable to delete ratable object related ratings records');
    }
  }
  
  /**
   * Cache rating after saving
   * 
   * @param  BaseObject $object
   */
  public function postSave(BaseObject $object)
  {
//    $column = self::getRatingColumn($object);
//    if (is_null($column))
//    {
//      return;
//    }
//    $peer = $object->getPeer();
//    $field = $peer->translateFieldName($column, BasePeer::TYPE_COLNAME, 
//                                                BasePeer::TYPE_PHPNAME);
//    $setter = 'set'.$field;
//    return $object->$setter($object->getRating(self::getPrecision()));
  }

}