<?php
class UserDao extends Model {
	public $table = "user";
	public $primary_key = "uid";
	public $fields = array(
						"uid" => "int",
						"name" => "string",
						"gender" => "int",
						);
	public function set_uid($value) {
		$this->set('uid',$value);
		$this->need_auto_update = true;
	}
	public function uid() {
		return $this->get_key("uid");
	}
	public function set_name($value) {
		$this->set('name',$value);
		$this->need_auto_update = true;
	}
	public function name() {
		return $this->get_key("name");
	}
	public function set_gender($value) {
		$this->set('gender',$value);
		$this->need_auto_update = true;
	}
	public function gender() {
		return $this->get_key("gender");
	}
}
