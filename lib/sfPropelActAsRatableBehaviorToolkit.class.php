<?php
/**
 * Symfony Propel rating behavior plugin toolkit 
 * 
 * @package plugins
 * @subpackage rating
 * @author Nicolas Perriault
 */
class sfPropelActAsRatableBehaviorToolkit 
{

  /**
   * <p>Retrieves user PK from configured function or class::method. A call to 
   * <code>$function()</code> or <code>$class::$method()</code> should returns a
   * voting user primary key.</p>
   * 
   * <p>Function example:</p>
   * <pre>
   * function get_connected_user_id()
   * {
   *   return sfContext::getInstance()->getUser()->getGuardUser()->getId(); 
   * }
   * </pre>
   * 
   * <p>Custom class and static method example:</p>
   * <pre>
   * class MySessionToolkit
   * {
   *   public static function getConnectedUserId()
   *   {
   *     return sfContext::getInstance()->getUser()->getGuardUser()->getId();
   *   } 
   * }
   * </pre>
   * 
   * @return mixed: int or null
   * @throws sfPropelActAsRatableException
   */
  public static function getUserPK()
  {
    $return = null;
    
    // Function
    $function = sfConfig::get('app_rating_user_pk_function');
    if (!is_null($function))
    {
      if (!function_exists($function))
      {
        throw new sfPropelActAsRatableException(
          sprintf('Function "%s" does not exist', $function));
      }
      $return = $function();
    }
    
    // Class::method
    $class  = sfConfig::get('app_rating_user_pk_class');
    $method = sfConfig::get('app_rating_user_pk_method');
    if (!is_null($class) && !is_null($method))
    {
      if (!class_exists($class) or !method_exists(new $class, $method))
      {
        throw new sfPropelActAsRatableException(
          sprintf('Static method "%s::%s()" does not exist', $class, $method));
      }
      $return = call_user_func(array(get_class($class), $method));
    }
    
    return $return; 
  }

}
