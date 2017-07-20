<?php
/**
 * Created by PhpStorm.
 * User: enno
 * Date: 18.07.17
 * Time: 15:10
 */

namespace CMRF\PersistenceLayer;


use CMRF\Core\AbstractCall;
use mysqli;

class SQLPersistingCallFactory extends CallFactory {

  static function schema() {
    return array(
      'description' => 'CMRF CiviCRM integration API calls',
      'fields' => array(
        'cid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Call ID',
        ),
        'status' => array(
          'description' => 'Status',
          'type' => 'varchar',
          'length' => 8,
          'not null' => TRUE,
          'default' => 'INIT',
        ),
        'connector_id' => array(
          'description' => 'Connector ID',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'request' => array(
          'description' => 'The request data sent',
          'type' => 'text',
          'serialize' => FALSE,
          'not null' => TRUE,
        ),
        'reply' => array(
          'description' => 'The reply data received',
          'type' => 'text',
          'serialize' => FALSE,
          'not null' => FALSE,
        ),
        'metadata' => array(
          'description' => 'Custom metadata on the request',
          'type' => 'text',
          'serialize' => FALSE,
          'not null' => FALSE,
        ),
        'request_hash' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'SHA1 hash of the request, enables quick lookups for caches',
        ),
        'create_date' => array(
          'type' => NULL,
          'mysql_type' => 'timestamp',
          'not null' => TRUE,
          'description' => 'Creation timestamp of this call',
        ),
        'scheduled_date' => array(
          'type' => NULL,
          'mysql_type' => 'timestamp',
          'not null' => FALSE,
          'description' => 'Scheduted timestamp of this call',
        ),
        'reply_date' => array(
          'type' => NULL,
          'mysql_type' => 'timestamp',
          'not null' => FALSE,
          'description' => 'Reply timestamp of this call',
        ),
        'cached_until' => array(
          'type' => NULL,
          'mysql_type' => 'timestamp',
          'not null' => FALSE,
          'description' => 'Cache timeout of this call',
        ),
        'retry_count' => array(
          'description' => 'Retry counter for multiple submissions',
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'cmrf_by_connector'  => array('connector_id', 'status'),
        'cmrf_cache_index'   => array('connector_id', 'request_hash', 'cached_until'),
      ),
      'primary key' => array('cid'),
    );
  }

  /** @var mysqli */
  private $connection;
  /** @var string */
  private $table_name;

  public function __construct(mysqli $sql_connection, $table_name, callable $constructor, callable $loader) {
    parent::__construct($constructor, $loader);
    $this->connection=$sql_connection;
    $this->table_name=$table_name;
  }

  /** @return \CMRF\Core\Call */
  public function createOrFetch($connector_id, $core, $entity, $action, $parameters, $options, $callback) {
    if(!empty($options['cache'])) {
      $hash = AbstractCall::getHashFromParams($entity,$action,$parameters,$options);
      $stmt=$this->connection->prepare("select * from {$this->table_name} where request_hash = ? and connector_id = ? and cached_until > NOW() limit 1");
      $stmt->bind_param("ss",$hash,$connector_id);
      $stmt->execute();
      $result=$stmt->get_result();
      $dataset=$result->fetch_object();
      if($dataset != NULL) {
        return $this->call_load($connector_id,$core,$dataset);
      }
    }
    /** @var \CMRF\Core\Call $call */
    $call=$this->call_construct($connector_id,$core,$entity,$action,$parameters,$options,$callback);
    $stmt = $this->connection->prepare("insert into {$this->table_name} 
             (status,connector_id,request,metadata,request_hash,create_date, scheduled_date)
      VALUES (?     ,?           ,?      ,?       ,?           ,?           , ?)");
    $status = $call->getStatus();
    $connectorID=$call->getConnectorID();
    $request=json_encode($call->getRequest());
    $metadata=json_encode($call->getMetadata());
    $hash=$call->getHash();
    $date=date('Y-m-d H:i:s');
    $scheduled_date = NULL;
    if($call->getScheduledDate() != NULL) {
      $scheduled_date=$call->getScheduledDate()->format('Y-m-d H:i:s');
    }
    $stmt->bind_param("sssssss",$status,$connectorID,$request,$metadata,$hash,$date, $scheduled_date);
    $stmt->execute();
    $call->setID($this->connection->insert_id);

    return $call;
  }

  public function update(\CMRF\Core\Call $call) {
    $id=$call->getID();
    if(empty($id)) {
      throw new \Exception("Unpersisted call given out to update. This won't work.");
    }
    else {
      $stmt = $this->connection->prepare("update {$this->table_name} set status=?,reply=?,reply_date=?,scheduled_date=?,cached_until=?,retry_count=? where cid=?");
      $cache_date=null;
      if ($call->getCachedUntil()) {
        $cache_date=$call->getCachedUntil()->format('Y-m-d H:i:s');
      }
      $status=$call->getStatus();
      $reply=json_encode($call->getReply());
      $reply_date = NULL;
      if($call->getReplyDate() != NULL) {
        $reply_date=$call->getReplyDate()->format('Y-m-d H:i:s');
      }
      $scheduled_date = NULL;
      if($call->getScheduledDate() != NULL) {
        $scheduled_date=$call->getScheduledDate()->format('Y-m-d H:i:s');
      }
      $retrycount=$call->getRetryCount();
      $stmt->bind_param("sssssii",$status,$reply,$reply_date,$scheduled_date,$cache_date,$retrycount,$id);
      $stmt->execute();
    }

  }

  public function purgeCachedCalls() {
    $stmt = $this->connection->query("delete from {$this->table_name} where status = 'DONE' and (cached_until < NOW() or cached_until is NULL)");
  }

  /**
   * Returns the queued calls which are ready for processing.
   *
   * @return array
   *   The array consists of the call ids
   */
  public function getQueuedCallIds() {
    $call_ids = array();
    $result = $this->connection->query("
      select cid from {$this->table_name} 
      where (status = 'INIT' OR status = 'RETRY') 
      and (DATE(scheduled_date) < NOW() or scheduled_date is NULL) 
      ORDER BY scheduled_date ASC");
    if ($result) {
      while ($dataset = $result->fetch_object()) {
        $call_ids[] = $dataset->cid;
      }
    }
    return $call_ids;
  }

  public function loadCall($call_id,$core) {
    $stmt=$this->connection->prepare("select * from {$this->table_name} where cid = ? limit 1");
    $stmt->bind_param("i",$call_id);
    $stmt->execute();
    $result=$stmt->get_result();
    $dataset=$result->fetch_object();
    if($dataset != NULL) {
      return $this->call_load($dataset->connector_id,$core,$dataset);
    }
  }

  public function findCall($options,$core) {
    //TODO: not yet implemented, as options is not yet known.
    return parent::findCall($options); // TODO: Change the autogenerated stub
  }


}
