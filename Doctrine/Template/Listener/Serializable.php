<?php

/**
 * Serializable Listener
 *
 * @subpackage  Listener
 * @author Alexandre Mogère
 */
abstract class Doctrine_Template_Listener_Serializable extends Doctrine_Record_Listener
{
  protected $_options = array();
  
  public function __construct(array $options)
  {
    $this->_options = $options;
  }
  
  /**
   * Normalizes relationship(s) 
   *
   * @param array $objects
   * @param string $filter
   * @return array
   */
  abstract protected function normalize($objects, $filter);
  
  /**
   * Denormalizes relationship(s) 
   *
   * @param Doctrine_Record $invoker
   * @return void
   */
  abstract protected function denormalize($invoker);
  
  public function preInsert(Doctrine_Event $event)
  {
    $this->denormalize($event->getInvoker());
  }
  
  public function preUpdate(Doctrine_Event $event)
  {
    $record = $event->getInvoker();
    
    if ($record->isModified())
    {
      $this->denormalize($record);
    }
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
      $column = Doctrine_Template_Serializable::getColumnName(
        $relation,
        $this->_options['column_suffix']
      );
      $filter = $data[$column];
      $model = Doctrine_Inflector::classify($relation['table']);

      if (! isset($relation['table_method']))
      {
        $query = Doctrine_Core::getTable($model)->createQuery();
        if (isset($relation['order_by']))
        {
          $order = $relation['order_by'];
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
      
      // replace value of column
      $data[$column] = $this->normalize($objects, $filter);
    }
    
    $event->data = $data;
  }

}
