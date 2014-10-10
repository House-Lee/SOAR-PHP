<?php
require_once (dirname(dirname(__FILE__))."/message/pb_message.php");
class Pluse extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
  }
  function timestamp()
  {
    return $this->_get_value("1");
  }
  function set_timestamp($value)
  {
    return $this->_set_value("1", $value);
  }
}
class ReqAuth extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
    $this->fields["2"] = "PBString";
    $this->values["2"] = "";
    $this->fields["3"] = "PBString";
    $this->values["3"] = "";
  }
  function timestamp()
  {
    return $this->_get_value("1");
  }
  function set_timestamp($value)
  {
    return $this->_set_value("1", $value);
  }
  function auth_key()
  {
    return $this->_get_value("2");
  }
  function set_auth_key($value)
  {
    return $this->_set_value("2", $value);
  }
  function sign()
  {
    return $this->_get_value("3");
  }
  function set_sign($value)
  {
    return $this->_set_value("3", $value);
  }
}
class AckCommon extends PBMessage
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
  function code()
  {
    return $this->_get_value("1");
  }
  function set_code($value)
  {
    return $this->_set_value("1", $value);
  }
  function msg()
  {
    return $this->_get_value("2");
  }
  function set_msg($value)
  {
    return $this->_set_value("2", $value);
  }
}
class Task_Desc extends PBMessage
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
    $this->fields["4"] = "PBString";
    $this->values["4"] = "";
    $this->fields["5"] = "PBString";
    $this->values["5"] = "";
  }
  function task_id()
  {
    return $this->_get_value("1");
  }
  function set_task_id($value)
  {
    return $this->_set_value("1", $value);
  }
  function retcode()
  {
    return $this->_get_value("2");
  }
  function set_retcode($value)
  {
    return $this->_set_value("2", $value);
  }
  function result()
  {
    return $this->_get_value("3");
  }
  function set_result($value)
  {
    return $this->_set_value("3", $value);
  }
  function str_result()
  {
    return $this->_get_value("4");
  }
  function set_str_result($value)
  {
    return $this->_set_value("4", $value);
  }
  function output()
  {
    return $this->_get_value("5");
  }
  function set_output($value)
  {
    return $this->_set_value("5", $value);
  }
}
class ReqSetConfig extends PBMessage
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
  function key()
  {
    return $this->_get_value("1");
  }
  function set_key($value)
  {
    return $this->_set_value("1", $value);
  }
  function value()
  {
    return $this->_get_value("2");
  }
  function set_value($value)
  {
    return $this->_set_value("2", $value);
  }
}
class ReqGetConfig extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBString";
    $this->values["1"] = "";
  }
  function key()
  {
    return $this->_get_value("1");
  }
  function set_key($value)
  {
    return $this->_set_value("1", $value);
  }
}
class ReqGetHistory extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
    $this->fields["2"] = "PBInt";
    $this->values["2"] = "";
  }
  function start()
  {
    return $this->_get_value("1");
  }
  function set_start($value)
  {
    return $this->_set_value("1", $value);
  }
  function length()
  {
    return $this->_get_value("2");
  }
  function set_length($value)
  {
    return $this->_set_value("2", $value);
  }
}
class HistoryItem extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "PBInt";
    $this->values["1"] = "";
    $this->fields["2"] = "PBString";
    $this->values["2"] = "";
    $this->fields["3"] = "PBString";
    $this->values["3"] = "";
  }
  function timestamp()
  {
    return $this->_get_value("1");
  }
  function set_timestamp($value)
  {
    return $this->_set_value("1", $value);
  }
  function task_id()
  {
    return $this->_get_value("2");
  }
  function set_task_id($value)
  {
    return $this->_set_value("2", $value);
  }
  function command()
  {
    return $this->_get_value("3");
  }
  function set_command($value)
  {
    return $this->_set_value("3", $value);
  }
}
class HistoryList extends PBMessage
{
  var $wired_type = PBMessage::WIRED_LENGTH_DELIMITED;
  public function __construct($reader=null)
  {
    parent::__construct($reader);
    $this->fields["1"] = "HistoryItem";
    $this->values["1"] = array();
  }
  function history($offset)
  {
    return $this->_get_arr_value("1", $offset);
  }
  function add_history()
  {
    return $this->_add_arr_value("1");
  }
  function set_history($index, $value)
  {
    $this->_set_arr_value("1", $index, $value);
  }
  function remove_last_history()
  {
    $this->_remove_last_arr_value("1");
  }
  function history_size()
  {
    return $this->_get_arr_size("1");
  }
}
?>