<?php
class ClientsDao extends Model {
	public $table = "clients";
	public $primary_key = "cid";
	public $fields = array(
						"cid" => "int",
						"name" => "string",
						"company" => "string",
						"gender" => "string",
						);
	public function set_cid($value) {
		$this->set('cid',$value);
		$this->need_auto_update = true;
	}
	public function cid() {
		return $this->get_key("cid");
	}
	public function set_name($value) {
		$this->set('name',$value);
		$this->need_auto_update = true;
	}
	public function name() {
		return $this->get_key("name");
	}
	public function set_company($value) {
		$this->set('company',$value);
		$this->need_auto_update = true;
	}
	public function company() {
		return $this->get_key("company");
	}
	public function set_gender($value) {
		$this->set('gender',$value);
		$this->need_auto_update = true;
	}
	public function gender() {
		return $this->get_key("gender");
	}
}