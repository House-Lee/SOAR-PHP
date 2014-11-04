<?php
class RClientsUserDao extends Model {
	public $table = "r_clients_user";
	public $primary_key = "id";
	public $fields = array(
						"id" => "int",
						"cid" => "int",
						"uid" => "int",
						"timestamps" => "int",
						);
	public function set_id($value) {
		$this->set('id',$value);
		$this->need_auto_update = true;
	}
	public function id() {
		return $this->get_key("id");
	}
	public function set_cid($value) {
		$this->set('cid',$value);
		$this->need_auto_update = true;
	}
	public function cid() {
		return $this->get_key("cid");
	}
	public function set_uid($value) {
		$this->set('uid',$value);
		$this->need_auto_update = true;
	}
	public function uid() {
		return $this->get_key("uid");
	}
	public function set_timestamps($value) {
		$this->set('timestamps',$value);
		$this->need_auto_update = true;
	}
	public function timestamps() {
		return $this->get_key("timestamps");
	}
}