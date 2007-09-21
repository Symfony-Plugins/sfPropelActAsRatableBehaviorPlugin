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
      $propel_object_ref = $this->getRequestParameter('id');
      $rating = $this->getRequestParameter('rating');
      $user_ref = trim((string)$this->getRequestParameter('uref'));
      if ($user_ref === '')
      {
        $user_ref = NULL;
      }
      if (!($propel_object_name && $propel_object_ref && !is_null($rating)))
      {
        return $this->renderFatalError('Parameters are missing');
      }
      
      // Retrieve Propel object instance
      try
      {
        $propel_object = sfPropelActAsRatableBehavior::retrieveFromReferenceKey(
          $propel_object_name, $propel_object_ref
        );
      }
      catch (Exception $e)
      {
        return $this->renderFatalError(
          'Unable to retrieve ratable object: '.$e->getMessage());
      }
      
      if (is_null($propel_object))
      {
        return $this->renderFatalError(
                 sprintf('Ratable object instance with key %s was not found',
                         $propel_object_ref));
      }
      
      $already_rated = $propel_object->hasBeenRatedByUser($user_ref);
      
      // Retrieve Rating parameters from request and update object
      $propel_object->setRating((int)$rating, $user_ref);
      $this->message = $already_rated === true ?
                       'Thanks for updating your vote' :
                       'Thank you for your vote';
      // This is useful id escaping has been enabled (no decorated object type modification)
      $this->object_class = $propel_object_name;
      $this->object = $propel_object;
    }
    catch (Exception $e)
    {
      return $this->renderFatalError($e->getMessage());
    }
  }
  
  /**
   * Gets object rating details and end it to according view
   * 
   */
  public function executeRatingDetails()
  {
    $propel_object_name = $this->getRequestParameter('o');
    $propel_object_ref = $this->getRequestParameter('id');
    
    if ($this->getRequest()->getMethod() !== sfRequest::POST)
    {
      //return $this->renderText('POST requests only');
    }
    
    // Retrieve Propel object instance
    try
    {
      $propel_object = sfPropelActAsRatableBehavior::retrieveFromReferenceKey(
        $propel_object_name, $propel_object_ref
      );
    }
    catch (Exception $e)
    {
      return $this->renderFatalError(
        'Unable to retrieve ratable object: '.$e->getMessage());
    }
    
    $this->rating_details = $propel_object->getRatingDetails(true);
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
