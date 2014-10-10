<?php
require_once (dirname(dirname(__FILE__))."/message/pb_message.php");
class VMDesc extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
    $this->fields["2"] = "PBInt";
    $this->values["2"] = "";
    $this->fields["3"] = "PBInt";
    $this->values["3"] = "";
    $this->fields["4"] = "PBInt";
    $this->values["4"] = "";
    $this->fields["5"] = "PBString";
    $this->values["5"] = "";
  }
  function domain()
  {
    return $this->_get_value("1");
  }
  function set_domain($value)
  {
    return $this->_set_value("1", $value);
  }
  function CPU()
  {
    return $this->_get_value("2");
  }
  function set_CPU($value)
  {
    return $this->_set_value("2", $value);
  }
  function memory()
  {
    return $this->_get_value("3");
  }
  function set_memory($value)
  {
    return $this->_set_value("3", $value);
  }
  function disk()
  {
    return $this->_get_value("4");
  }
  function set_disk($value)
  {
    return $this->_set_value("4", $value);
  }
  function ip()
  {
    return $this->_get_value("5");
  }
  function set_ip($value)
  {
    return $this->_set_value("5", $value);
  }
}
class VMBatch_pb extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
    $this->fields["2"] = "VMDesc";
    $this->values["2"] = array();
  }
  function task_id()
  {
    return $this->_get_value("1");
  }
  function set_task_id($value)
  {
    return $this->_set_value("1", $value);
  }
  function vms($offset)
  {
    return $this->_get_arr_value("2", $offset);
  }
  function add_vms()
  {
    return $this->_add_arr_value("2");
  }
  function set_vms($index, $value)
  {
    $this->_set_arr_value("2", $index, $value);
  }
  function remove_last_vms()
  {
    $this->_remove_last_arr_value("2");
  }
  function vms_size()
  {
    return $this->_get_arr_size("2");
  }
}
class VMBatchResult_pb extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
    $this->fields["2"] = "PBInt";
    $this->values["2"] = "";
    $this->fields["3"] = "PBString";
    $this->values["3"] = "";
    $this->fields["4"] = "VMDesc";
    $this->values["4"] = array();
  }
  function task_id()
  {
    return $this->_get_value("1");
  }
  function set_task_id($value)
  {
    return $this->_set_value("1", $value);
  }
  function result()
  {
    return $this->_get_value("2");
  }
  function set_result($value)
  {
    return $this->_set_value("2", $value);
  }
  function desc()
  {
    return $this->_get_value("3");
  }
  function set_desc($value)
  {
    return $this->_set_value("3", $value);
  }
  function vms($offset)
  {
    return $this->_get_arr_value("4", $offset);
  }
  function add_vms()
  {
    return $this->_add_arr_value("4");
  }
  function set_vms($index, $value)
  {
    $this->_set_arr_value("4", $index, $value);
  }
  function remove_last_vms()
  {
    $this->_remove_last_arr_value("4");
  }
  function vms_size()
  {
    return $this->_get_arr_size("4");
  }
}
?>