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
      $user_ref = trim((string)$this->getRequestParameter('uref'));
      if ($user_ref === '')
      {
        $user_ref = NULL;
      }
      if (!($propel_object_name && $propel_object_id && !is_null($rating)))
      {
        return $this->renderText('Parameters are missing');
      }
      if (!class_exists($propel_object_name))
      {
        return $this->renderText(sprintf('Unknown class "%s"', $propel_object_name));      
      }
      
      // Retrieve Propel object instance
      $propel_object = new $propel_object_name;
      $propel_object_peer = $propel_object->getPeer();
      $propel_object = call_user_func(array($propel_object_peer, 'retrieveByPK'), 
                                      $propel_object_id);
      if (is_null($propel_object))
      {
        return $this->renderText('Impossible to get object instance');
      }
      
      // Retrieve Rating parameters from request and update object
      $propel_object->setRating((int)$rating, $user_ref);
      $this->message = 'Thank you for your vote';
      $this->object = $propel_object;
    }
    catch (Exception $e)
    {
      return $this->renderText('Error: '.$e->getMessage());
    }
  }  
}
