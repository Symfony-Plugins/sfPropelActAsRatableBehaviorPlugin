<?php
/*
 * This file is part of the sfPropelActAsRatableBehaviorPlugin
 * 
 * @author Nicolas Perriault <nperriault@gmail.com>
 */
sfLoader::loadHelpers('Javascript', 'Tag');
/**
 * Return the HTML code for a unordered list showing rating stars
 * 
 * @param  BaseObject  $propel_object  Propel object instance
 * @param  array       $options        Array of HTML options to apply on the HTML list
 * @throws sfPropelActAsRatableException
 * @return string
 **/
function sf_rater($propel_object, $options = array())
{
  if (is_null($propel_object) or !$propel_object instanceof BaseObject)
  {
    sfLogger::getInstance()->debug('You cannot rate a NULL object');
  }
  
  // Add css resources to the response
  $css = '/sfPropelActAsRatableBehaviorPlugin/css/sf_rating';
  sfContext::getInstance()->getResponse()->addStylesheet($css);
  
  $star_width = sfConfig::get('app_rating_starwidth', 25);
  try
  {
    $max_rating = $propel_object->getMaxRating();
    $actual_rating = $propel_object->getRating();
    $bar_width = $actual_rating * $star_width;
    
    $options = _parse_attributes($options);
    if (!isset($options['class']))
    {
      $options = array_merge($options, array('class' => 'star-rating'));
    }
    if (!isset($options['style']) or !preg_match('/width:/i', $options['style']))
    {
      $full_bar_width = $max_rating * $star_width;
      $options = array_merge($options, 
                             array('style' => 'width:'.$full_bar_width.'px'));
    }
    
    $propel_object_class = get_class($propel_object);
    $id = $propel_object->getPrimaryKey();
    $msg_domid = sprintf('rating_message_%s_%s', $propel_object_class, $id) ;
    $bar_domid = sprintf('current_rating_%s_%s', $propel_object_class, $id) ;
    
    $list_content  = '  <li class="current-rating" id="'.$bar_domid.'" style="width:'.$bar_width.'px;">';
    $list_content .= sprintf('Currently rated %s star(s) on %d', 
                             $propel_object->getRating(), 
                             $max_rating);
    $list_content .= '  </li>';
    
    for ($i=1; $i <= $max_rating; $i++)
    {
      $list_content .= '  <li>'.link_to_remote(sprintf('Rate it %d stars', $i), 
                    array('url'      => sprintf('sfRating/rate?o=%s&id=%s&rating=%d', 
                                                $propel_object_class, 
                                                $id, 
                                                $i),
                          'update'   => $msg_domid,
                          'script'   => true,
                          'complete' => visual_effect('appear', $msg_domid).
                                        visual_effect('highlight', $msg_domid)), 
                    array('class'  => 'r'.$i.'stars',
                          'title'  => 'Rate it '.$i.' stars')).'</li>';
    }
    
    return content_tag('ul', $list_content, $options).
           content_tag('div', null, array('id' => $msg_domid));
  }
  catch (Exception $e)
  {
    sfLogger::getInstance()->debug('Exception catched from sf_rater helper: '.$e->getMessage());
  }
}

/**
 * Shows rating details for given ratable object
 * 
 * @param  BaseObject  $propel_object
 * @return string
 **/
function sf_rating_details($propel_object)
{
  /* TODO:
   *  .write object rating/n stars in a div
   *  .put a rollover on it
   *  .on rollover call ajax action 'ratingDetails' for object
   *  .update a floating div with result
   *  .on rollout clear the floating div
   * 
   */
}
