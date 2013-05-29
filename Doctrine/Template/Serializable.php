<?php

/**
 * Serializable behavior: allows to denormalize a many-to-many relationship
 *
 * @subpackage  Template
 * @author Alexandre Mogère
 */
class Doctrine_Template_Serializable extends Doctrine_Template
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
   * 
   *  * column_suffix:  A string which composes name of column which contains ids (default: _list)
   *  * serialization_format: php, glue, base2
   *  * separator: The separator if the format with separator is used
   *  * post_separator: A separator in case a post submit send a string instead of an array of primary keys (default: "|")
   */
  protected $_options = array(
    'relations'             => array(),
    'column_suffix'         => 'list',
    'serialization_format'  => 'glue',
    'separator'             => ";",
    'post_separator'        => "|",
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
      $this->_table->setColumn(
        self::getColumnName($relation, $this->_options['column_suffix']), 'varchar', 255, array(
        'type' => 'varchar',
        'length' => 255,
      ));
    }
    
    $format = Doctrine_Inflector::classify($this->_options['serialization_format']);
    $listerner = "Doctrine_Template_Listener_{$format}Serializable";
    if (! class_exists($listerner))
    {
      throw new Expection("The behavior '$listerner' does not exist.");
    }
    
    $this->addListener(new $listerner($this->_options));
  }
  
  static public function getColumnName($relation, $suffix)
  {
    $table = $relation['table'];
    $plural = "s";
    
    $lastLetter = substr($relation['table'], -1);
    $table = $relation['table'];
    $plural = "s";
    
    if ("y" == $lastLetter)
    {
      $table = substr($relation['table'], 0, strlen($relation['table']) - 1);
      $plural = "ies";
    }
    
    return sprintf("%s%s_%s",
      $table,
      $plural,
      $suffix
    );
  }

}