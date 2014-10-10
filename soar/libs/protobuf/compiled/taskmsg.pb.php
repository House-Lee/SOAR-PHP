<?php
require_once (dirname(dirname(__FILE__))."/message/pb_message.php");
class ReqExecuteTask extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
    $this->fields["2"] = "PBString";
    $this->values["2"] = "";
  }
  function task_id()
  {
    return $this->_get_value("1");
  }
  function set_task_id($value)
  {
    return $this->_set_value("1", $value);
  }
  function command()
  {
    return $this->_get_value("2");
  }
  function set_command($value)
  {
    return $this->_set_value("2", $value);
  }
}
class ReqTaskCommon extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
  }
  function task_id()
  {
    return $this->_get_value("1");
  }
  function set_task_id($value)
  {
    return $this->_set_value("1", $value);
  }
}
class AckTaskOutput extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
    $this->fields["2"] = "PBString";
    $this->values["2"] = "";
  }
  function req_result()
  {
    return $this->_get_value("1");
  }
  function set_req_result($value)
  {
    return $this->_set_value("1", $value);
  }
  function output()
  {
    return $this->_get_value("2");
  }
  function set_output($value)
  {
    return $this->_set_value("2", $value);
  }
}
class AckTaskResult extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
    $this->fields["2"] = "PBInt";
    $this->values["2"] = "";
    $this->fields["3"] = "PBString";
    $this->values["3"] = "";
    $this->fields["4"] = "PBInt";
    $this->values["4"] = "";
  }
  function req_result()
  {
    return $this->_get_value("1");
  }
  function set_req_result($value)
  {
    return $this->_set_value("1", $value);
  }
  function task_result()
  {
    return $this->_get_value("2");
  }
  function set_task_result($value)
  {
    return $this->_set_value("2", $value);
  }
  function task_str_result()
  {
    return $this->_get_value("3");
  }
  function set_task_str_result($value)
  {
    return $this->_set_value("3", $value);
  }
  function ret_code()
  {
    return $this->_get_value("4");
  }
  function set_ret_code($value)
  {
    return $this->_set_value("4", $value);
  }
}
?>