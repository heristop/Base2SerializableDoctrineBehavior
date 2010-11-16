<?php

/**
 * Base2Serializable behavior: allows to denormalize a many-to-many relationship
 *
 * @subpackage  Template
 * @author Alexandre Mogère
 */
class Doctrine_Template_Base2Serializable extends Doctrine_Template
{

  /**
   * Available options:
   *
   *  * relations:      An array composed of each relationships with theirs attributes (required):
   *  ** table:          The table name (required)
   *  ** primary_key:    The name of primary key (default: id)
   *  ** order_by:       An array composed of two fields:
   *                      * The column to order by the results (must be in the PhpName format)
   *                      * asc or desc
   *  ** table_method:   A method to return either a query, collection or single object
   *  ** column_filter:  The related table can have a specific column in order to do filtering
   * 
   *  * column_suffix:  A string which composes name of column which contains ids (default: _ids)
   *  * post_separator: A separator in case a post submit send a string instead of an array of primary keys
   */
  protected $_options = array(
    'relations'      => array(),
    'column_suffix'  => 's_list',
    'post_separator' => '|',
  );

  /**
   * __construct
   *
   * @param string $array
   * @return void
   */
  public function __construct(array $options = array())
  {
    $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
  }
  
  public function setTableDefinition()
  {
    $relations = $this->_options['relations'];
    
    foreach ($relations as $relation)
    {
      $column = $relation['table'] . $this->_options['column_suffix'];
      $this->_table->setColumn($column, 'varchar', 255, array(
        'type' => 'varchar',
        'length' => 255,
      ));
    }
    
    $this->addListener(new Doctrine_Template_Listener_Base2Serializable($this->_options));
  }

}