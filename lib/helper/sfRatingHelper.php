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
 * @param  BaseObject  $object  Propel object instance
 * @param  array       $options        Array of HTML options to apply on the HTML list
 * @throws sfPropelActAsRatableException
 * @return string
 **/
function sf_rater($object, $options = array())
{
  if (is_null($object) or !$object instanceof BaseObject)
  {
    sfLogger::getInstance()->debug('You cannot rate a NULL object');
  }
  
  // Add css resources to the response
  $css = '/sfPropelActAsRatableBehaviorPlugin/css/sf_rating';
  sfContext::getInstance()->getResponse()->addStylesheet($css);
  
  $star_width = sfConfig::get('app_rating_star_width', 25);
  try
  {
    $max_rating = $object->getMaxRating();
    $actual_rating = $object->getRating();
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
    
    $object_class = get_class($object);
    $object_id = $object->getReferenceKey();
    $msg_domid = sprintf('rating_message_%s_%s', $object_class, $object_id) ;
    $bar_domid = sprintf('current_rating_%s_%s', $object_class, $object_id) ;
    
    $list_content  = '  <li class="current-rating" id="'.$bar_domid.'" style="width:'.$bar_width.'px;">';
    $list_content .= sprintf('Currently rated %s star(s) on %d', 
                             $object->getRating(), 
                             $max_rating);
    $list_content .= '  </li>';
    
    for ($i=1; $i <= $max_rating; $i++)
    {
      $list_content .= 
        '  <li>'.link_to_remote(sprintf('Rate it %d stars', $i), 
          array('url'      => sprintf('sfRating/rate?o=%s&id=%d&rating=%d', 
                                      $object_class, 
                                      $object_id, 
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
