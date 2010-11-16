<?php

/**
 * Base2Serializable Listener
 *
 * @subpackage  Listener
 * @author Alexandre Mogère
 */
class Doctrine_Template_Listener_Base2Serializable extends Doctrine_Record_Listener
{
  protected $_options = array();
  
  public function __construct(array $options)
  {
    $this->_options = $options;
  }
  
  public function preInsert(Doctrine_Event $event)
  {
    $this->denormalize($event->getInvoker());
  }
  
  public function preUpdate(Doctrine_Event $event)
  {
    $this->denormalize($event->getInvoker());
  }
  
  /**
   * Deserializes relationship(s)
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function postHydrate(Doctrine_Event $event)
  {
    $data = $event->data;
    $relations = $this->_options['relations'];

    foreach ($relations as $relation)
    {
      $column = $relation['table'] . $this->_options['column_suffix'];
      $filter = $data[$column];
      $model = Doctrine_Inflector::classify($relation['table']);

      if (! isset($relation['table_method']))
      {
        $query = Doctrine_Core::getTable($model)->createQuery();
        if ($order = $relation['order_by'])
        {
          $query->addOrderBy($order[0] . ' ' . $order[1]);
        }
        $query->setHydrationMode(Doctrine_Core::HYDRATE_ARRAY);
        $objects = $query->execute();
      }
      else
      {
        $tableMethod = $relation['table_method'];
        $results = Doctrine_Core::getTable($model)->$tableMethod();
  
        if ($results instanceof Doctrine_Query)
        {
          $objects = $results->execute();
        }
        else if ($results instanceof Doctrine_Collection)
        {
          $objects = $results;
        }
        else if ($results instanceof Doctrine_Record)
        {
          $objects = new Doctrine_Collection($model);
          $objects[] = $results;
        }
        else
        {
          $objects = array();
        }
      }

      // transforms integer value to array
      $matches = array();
      foreach ($objects as $result)
      {
        $primaryKey = isset($relation['primary_key']) ? $relation['primary_key'] : 'id';
        
        // if related table contains a column with filter 
        if (isset($relation['column_filter']))
        {
          $result['code_filter'] = $result[$relation['column_filter']];
        }
        // computes the filter from primary key
        else
        {
          $result['code_filter'] = pow(2, (int) $result[$primaryKey]);
        }
      
        // checks if primary key is present in filter
        if (((int) $filter & (int) $result['code_filter']) != 0)
        {
          // builds an array composed of primary keys
          array_push($matches, $result[$primaryKey]);
        }
      }
      
      // replace value of column
      $data[$column] = $matches;
    }
    
    $event->data = $data;
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
      $column = $relation['table'] . $this->_options['column_suffix'];
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

    $binary = 0;
    foreach ($ids as $id)
    {
      if ("" === $id) continue;
      
      $binary = $binary + pow(2, (int) $id);
    }

    return $binary;
  }
  
}
