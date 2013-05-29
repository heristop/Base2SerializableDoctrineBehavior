<?php

/**
 * PHPSerializable Listener
 *
 * @subpackage  Listener
 * @author Alexandre Mogère
 */
class Doctrine_Template_Listener_PHPSerializable extends Doctrine_Template_Listener_Serializable
{
  /**
   * Normalizes relationship(s) 
   *
   * @param array $objects
   * @param string $filter
   * @return array
   */
  protected function normalize($objects, $filter)
  {
    // transforms serialized values to array
    $matches = array();
    foreach ($objects as $result)
    {
      if (is_string($filter))
      {
        $filter = (array) unserialize($filter);
        if (!empty($filter))
        foreach ($filter as $value)
        {
          array_push($matches, (int) $value);
        }
      }
    }
    
    return $matches;
  }
  
  /**
   * Denormalizes relationship(s) 
   *
   * @param Doctrine_Record $invoker
   * @return void
   */
  protected function denormalize($invoker)
  {
    $relations = $this->_options['relations'];
    
    foreach ($relations as $relation)
    {
      $column = Doctrine_Template_Serializable::getColumnName(
        $relation,
        $this->_options['column_suffix']
      );
      if ("" !== $invoker->$column)
      {
        if (! is_array($invoker->$column))
        {
          $invoker->$column = explode($this->_options['post_separator'], $invoker->$column);
        }
        
        $invoker->$column = $this->getSerializedIds($invoker->$column);
      }
    }
  }
  
  /**
   * Generates code_filter from a list of Ids
   *
   * @param array $ids
   * @return int
   */
  protected function getSerializedIds($ids)
  {
    sort($ids);
    
    $value = array();
    foreach ($ids as $id)
    {
      if ((int) $id == 0) continue;
      
      array_push($value, (int) $id);
    }
    
    return serialize($value);
  }
  
}
