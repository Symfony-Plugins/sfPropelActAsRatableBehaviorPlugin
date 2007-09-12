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
 */
class sfPropelActAsRatableBehavior
{
  
  /**
   * Default default max rating
   */
  const DEFAULT_MAX_RATING = 5;

  /**
   * Returns configured reference field name or NULL if none has been provided
   * 
   * @return string
   */
  public function getReferenceField(BaseObject $object)
  {
    return sfConfig::get(
      sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_reference_field', 
              get_class($object)));
  }

  /**
   * Retrieves reference field Propel type
   * 
   * @return BaseObject  $object
   * @return string or null if no reference field has been provided
   */
  public function getReferenceFieldType(BaseObject $object)
  {
    if (is_null($object->getReferenceField()))
    {
      return null;
    }
    
    $propel_type = sfConfig::get(
      sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_reference_field_type', 
              get_class($object)));
    if (!is_null($propel_type))
    {
      return $propel_type;
    }
    
    $reference_field = $object->getReferenceField();
    try // to retrieve column value from a phpName
    {
      $object_reference_key = $object->getByName($reference_field, BasePeer::TYPE_PHPNAME);
      self::setReferenceFieldType($object, BasePeer::TYPE_PHPNAME);
    } catch (Exception $e) {}
    
    try // to retrieve column value from a colName
    {
      $object_reference_key = $object->getByName($reference_field, BasePeer::TYPE_COLNAME);
      self::setReferenceFieldType($object, BasePeer::TYPE_COLNAME);
    } catch (Exception $e) {}
    
    try // to retrieve column value from a fieldName
    {
      $object_reference_key = $object->getByName($reference_field, BasePeer::TYPE_FIELDNAME);
      self::setReferenceFieldType($object, BasePeer::TYPE_FIELDNAME);
    } catch (Exception $e) {}
    
    return $this->getReferenceFieldType($object);
  }

  /**
   * <p>Retrieves the object reference key. By default, we use the primary key 
   * of the Propel object. It is possible to configure the name of the column to 
   * use as a reference specifying the 'reference_field' parameter of the 
   * behavior in the Propel model class it is applied to, eg:</p>
   * <pre>
   * sfPropelBehavior::add('sfTestObject', 
   *                       array('sfPropelActAsRatableBehavior' => 
   *                             array('reference_field' => sfTestObjectPeer::CUSTOM_ID)));
   * </pre>
   * 
   * @param  BaseObject  $object
   * @return string as a md5 hash of the reference field 
   * @throws sfPropelActAsRatableException
   */
  public function getReferenceKey(BaseObject $object)
  {
    if ($object->isNew())
    {
      throw new sfPropelActAsRatableException(
        'You cannot rate or retrieve rating for unsaved objects');
    }
    
    $reference_field = sfConfig::get(
      sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_reference_field', 
              get_class($object)));
    
    if (is_null($reference_field))
    {
      return md5($object->getPrimaryKey());
    }

    $object_reference_key = $object->getByName($reference_field, 
                                               $object->getReferenceFieldType());
    if (is_null($object_reference_key))
    {
      throw new sfPropelActAsRatableException(
        sprintf('Reference field %s cannot be retrieved for %s class objects', 
                $reference_field,
                get_class($object)));
    }
    
    return md5($object_reference_key);
  }
  
  /**
   * Retrieves an existing rating object, or return a new empty one
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_reference  Unique user reference
   * @return sfRating
   * @throws sfPropelActAsRatableException
   **/
  protected function getOrCreate(BaseObject $object, $user_reference=null)
  {
    if ($object->isNew())
    {
      throw new sfPropelActAsRatableException('Unsaved objects are not ratable');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    if (!is_null($user_reference))
    {
      $c->add(sfRatingPeer::USER_REFERENCE, $user_reference);
    }
    $result = sfRatingPeer::doSelectOne($c);
    return is_null($result) ? new sfRating() : $result;
  }

  /**
   * Clear all ratings for an object
   *
   * @param  BaseObject  $object
   **/
  public function clearRatings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doDelete($c);
  }

  /**
   * Clear user rating for an object
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_reference  Unique reference to the user
   **/
  public function clearUserRating(BaseObject $object, $user_reference)
  {
    if (is_null($user_reference) or trim((string)$user_reference) === '')
    {
      throw new sfPropelActAsRatableException('Impossible to clear a user rating with no user reference provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_REFERENCE, $user_reference);
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
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    return sfRatingPeer::doCount($c) > 0;
  }

  /**
   * Checks if an Object has been rated by a user
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_reference  Unique reference to a user
   **/
  public function hasBeenRatedByUser(BaseObject $object, $user_reference)
  {
    if (is_null($user_reference) or trim((string)$user_reference) === '')
    {
      throw new sfPropelActAsRatableException(
        'Impossible to check a user rating with no user reference provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_REFERENCE, $user_reference);
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
      sfLogger::getInstance()->warning(
        sprintf('No maximum rating has been set for "%s" ratable objects, '.
                'default has been set to %d',
                get_class($object),
                self::DEFAULT_MAX_RATING));
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
   * Gets the object rating
   *
   * @param  BaseObject  $object
   * @param  int         $floating_point
   * @return float
   **/
  public function getRating(BaseObject $object, $floating_point=2)
  {
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_ratings', 'COUNT('.sfRatingPeer::ID.')');
    $c->addAsColumn('total', 'SUM('.sfRatingPeer::RATING.')');
    $p = array();
    $sql = BasePeer::createSelectSql($c, $p);
    $con = Propel::getConnection();
    $stmt = $con->prepareStatement($sql);
    $stmt->setString(1, $object->getReferenceKey());
    $stmt->setString(2, get_class($object));
    $rs = $stmt->executeQuery(ResultSet::FETCHMODE_ASSOC);
    $rs->next();
    $nb_ratings = $rs->getString('nb_ratings');
    $total      = $rs->getString('total');
    if (!$nb_ratings or $nb_ratings === 0)
    {
      return NULL; // Object has not been rated yet
    }
    return round($total / $nb_ratings,
                 sfConfig::get('app_rating_floatingpoint', $floating_point));
  }
  
  /**
   * Gets the object rating for given user pk
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_reference  Unique reference to the user
   **/
  public function getUserRating(BaseObject $object, $user_reference)
  {
    if (is_null($user_reference) or trim((string)$user_reference) === '')
    {
      throw new sfPropelActAsRatableException(
        'Impossible to get a user rating with no user reference provided');
    }
    $c = new Criteria();
    $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
    $c->add(sfRatingPeer::RATABLE_MODEL, get_class($object));
    $c->add(sfRatingPeer::USER_REFERENCE, $user_reference);
    $rating_object = sfRatingPeer::doSelectOne($c);
    if (!is_null($rating_object))
    {
      return $rating_object->getRating();
    }
  }
  
  /**
   * Returns true if the passed model name is ratable
   * 
   * @author     Xavier Lacot
   * @author     Nicolas Perriault
   * @param      string  $object_name
   * @return     boolean
   */
  public static function isRatable($object_name)
  {
    if (!class_exists($object_name))
    {
      throw new sfPropelActAsRatableException(
                  sprintf('Unknown class %s', $object_name));
    }
    $base_class = sprintf('Base%s', ucfirst($object_name));
    return !is_null(sfMixer::getCallable($base_class.':getReferenceKey'));
  }
  
  /**
   * Retrieves an object instance from a given reference key
   * 
   * @param  string  $object_name
   * @param  string  $reference_key
   * @return BaseObject
   * @throws sfPropelActAsRatableException
   */
  public static function retrieveFromReferenceKey($object_name, $reference_key)
  {
    if (!class_exists($object_name))
    {
      throw new sfPropelActAsRatableException('Unknown class '.$object_name);
    }
    
    $object = new $object_name;
    $peer = $object->getPeer();
    $criteria = new Criteria();
    $ref_field_type = $object->getReferenceFieldType();
    if (is_null($ref_field_type))
    {
      // retrieve PK column name
      $table_map = call_user_func(array(get_class($peer), 'getTableMap'));
      $columns = $table_map->getColumns();
      foreach(array_keys($columns) as $key) 
      {
        if ($columns[$key]->isPrimaryKey()) 
        {
          $column_map = $columns[$key];
          $column = constant(get_class($peer).'::'.$column_map->getColumnName());
        }
      }
    }
    else
    {
      $column = call_user_func(array($peer, 'translateFieldName'),
                               $object->getReferenceField(), 
                               $ref_field_type, 
                               BasePeer::TYPE_COLNAME);
    }
    if (is_null($column))
    {
      throw new sfPropelActAsRatableException(
        'Unable to retrieve reference key column name');
    }
    $criteria->add($column,  
                   sprintf('MD5(%s) = "%s"', $column, $reference_key), 
                   Criteria::CUSTOM);
    return call_user_func(array(get_class($peer), 'doSelectOne'), $criteria);
  }
  
  /**
   * Rates the Object
   *
   * @param  BaseObject  $object
   * @param  int         $rating
   * @param  mixed       $user_reference  Optionnal unique reference to user
   * @throws sfPropelActAsRatableException
   **/
  public function setRating(BaseObject $object, $rating, $user_reference=null)
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
    
    $key = $object->getReferenceKey();
    if (is_null($key))
    {
      throw new sfPropelActAsRatableException(
        sprintf('No reference key available for field %s in class %s',
                $object->getReferenceField(),
                get_class($object)));
    }
    
    $rating_object = $this->getOrCreate($object, $user_reference);
    $rating_object->setRatableModel(get_class($object));
    $rating_object->setRatableId($key);
    if (!is_null($user_reference))
    {
      $rating_object->setUserReference($user_reference);
    }
    $rating_object->setRating($rating);
    
    return $rating_object->save();
  }
  
  /**
   * Sets current object reference field propel type
   * 
   * @param  BaseObject  $object
   * @param  string      $propel_type
   */
  public static function setReferenceFieldType(BaseObject $object, $propel_type)
  {
    sfConfig::set(
        sprintf('propel_behavior_sfPropelActAsRatableBehavior_%s_reference_field_type',
                get_class($object)), 
                $propel_type);
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
      $c->add(sfRatingPeer::RATABLE_ID, $object->getReferenceKey());
      sfRatingPeer::doDelete($c);
    }
    catch (Exception $e)
    {
      throw new sfPropelActAsRatableException(
        'Unable to delete ratable object related ratings records');
    }
  }

}