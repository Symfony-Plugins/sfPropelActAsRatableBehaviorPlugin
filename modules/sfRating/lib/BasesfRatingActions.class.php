<?php
/**
 * sfPropelActAsRatableBehaviorPlugin base actions.
 * 
 * @package    plugins
 * @subpackage rating 
 * @author     Nicolas Perriault <nperriault@gmail.com>
 * @link       http://trac.symfony-project.com/trac/wiki/sfPropelActAsRatableBehaviorPlugin
 */
class BasesfRatingActions extends sfActions
{
  
  /**
   * <p>Rate a propel object. This action is typically executed from an AJAX 
   * request.</p>
   * 
   * <p><strong>Required</strong> POST request parameters are:</p>
   * <ul>
   *   <li><code>o</code>:      Propel class name to rate</li>
   *   <li><code>id</code>:     Propel object primary key</li>
   *   <li><code>rating</code>: Rating to apply</li>
   * </ul>
   * 
   * <p>You should override this method in your own exteends actions class if 
   * you need to associate current rating with a user.</p>
   * 
   * @see  sfPropelActAsRatableBehavior API
   * @link http://trac.symfony-project.com/trac/wiki/sfPropelActAsRatableBehaviorPlugin
   *
   */
  public function executeRate()
  {
    try
    {
      if ($this->getRequest()->getMethod() !== sfRequest::POST)
      {
        return $this->renderText('POST requests only');
      }
      
      // Retrieve parameters from request
      $propel_object_name = $this->getRequestParameter('o');
      $propel_object_id = $this->getRequestParameter('id');
      $rating = $this->getRequestParameter('rating');
      
      
      // Retrieve ratable propel object
      if (!($propel_object_name && $propel_object_id && !is_null($rating)))
      {
        return $this->renderFatalError(
                 'Parameters are missing to retrieve ratable object');
      }
      
      $propel_object = $this->getRatableObject($propel_object_name, 
                                               $propel_object_id);      
      
      if (is_null($propel_object))
      {
        return $this->renderFatalError(
                 'Unable to retrieve ratable object: '.$e->getMessage());
      }
      
      // User retrieval
      $user_id = sfPropelActAsRatableBehaviorToolkit::getUserPK();
      if (is_null($user_id))
      {
        // Votes are cookie based
        $cookie_name = sprintf('rating_%s_%d', $propel_object_name, $propel_object_id);
        if (!is_null($this->getRequest()->getCookie($cookie_name)))
        {
          $message = 'You have already voted';
        }
        else
        {
          $propel_object->setRating((int) $rating);
          $cookie_expires = date('Y-m-d H:m:i', time() + (86400*365*10));
          $this->getResponse()->setCookie($cookie_name, (int)$rating, $cookie_expires);
          $message = 'Thank you for your vote';
        }
      }
      else
      {
        $already_rated = $propel_object->hasBeenRatedByUser($user_id);
        $propel_object->setRating((int) $rating, $user_id);
        $message = $already_rated === true ?
                         'Thanks for updating your vote' :
                         'Thank you for your vote';
      }
      
      // This is useful if escaping has been enabled (no decorated object type modification)
      $this->object_class = $propel_object_name;
      $this->object = $propel_object;
      $this->message = $message;
    }
    catch (Exception $e)
    {
      return $this->renderFatalError($e->getMessage());
    }
  }
  
  /**
   * Retrieve a ratable Propel object from parameters
   * 
   * @return BaseObject
   */
  protected function getRatableObject($class_name, $id)
  {
    $peer = $class_name.'Peer';
    $object = call_user_func(array($peer, 'retrieveByPK'), $id);
    return $object;
  }
  
  /**
   * This methods will returns a basic user error message while logging a 
   * complete one if provided in the debug log file 
   * 
   * @param string  $log_info  Log information message
   */
  protected function renderFatalError($log_info = null)
  {
    if (!is_null($log_info))
    {
      sfLogger::getInstance()->warning('Rating error: '.$log_info);
    }
    return $this->renderText('A problem has occured, sorry for the inconvenience');
  }
  
}
