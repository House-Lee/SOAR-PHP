<?php
class Performance extends Controller {
    public function WarmUp() {
        $this->get_param();
        $dao = new PerformanceTestDao();
        for($i = 0; $i < $this->case_size; ++$i) {
            $dao->clear_buffer();
            $dao->set_val($i);
            $dao->Insert();
        }
    }
    public function Reset() {
        $config = SoarConfig::get('main.db');
		if ($config === null) {
			throw new Exception("db not configure");
		}
		$config['charset'] = "utf8";//强制设定字符集为utf8，仅支持utf8字符集操作
		$mysql = new MySQL($config , false);
		$this->set_return("result", $mysql->query("TRUNCATE TABLE  `performance_test`"));
    }
    public function Write_NC() {
        Model::DisableAutoCache();
        Model::DisableRetrieveResAutoCache();
        $this->get_param();
        $this->write();
    }
    
    public function Write_C() {
        Model::EnableRetrieveResAutoCache();
        $this->get_param();
        $this->write();
    }
    
    public function SingleRead_NC() {
        Model::DisableAutoCache();
        Model::DisableRetrieveResAutoCache();
        $this->get_param();
        $this->single_read();
    }
    
    public function SingleRead_C() {
        Model::EnableRetrieveResAutoCache();
        $this->get_param();
        $this->single_read();
    }
    
    public function MultiRead_NC() {
        Model::DisableAutoCache();
        Model::DisableRetrieveResAutoCache();
        $this->get_param();
        $this->multi_read();
    }
    
    public function MultiRead_C() {
        Model::EnableRetrieveResAutoCache();
        $this->get_param();
        $this->multi_read();
    }
    
    public function __destruct() {
        $this->set_success_and_quit();
    }
    
    private function write() {
        $new_val = rand(0, 32767);
        $id = rand(1 , $this->case_size - 1);
        $dao = new PerformanceTestDao();
        $dao->set_id($id);
        $dao->set_val($new_val);
        $dao->Update();
    }
    
    private function single_read() {
        $id = rand(1 , $this->case_size - 1);
        $dao = new PerformanceTestDao();
        $dao->GetOne($id);
//         $this->set_return("key", print_r($dao , true));
    }
    
    private function multi_read() {
        $idl = rand(1 , $this->case_size - 1);
        $idr = rand(1 , $this->case_size - 1);
        if ($idr < $idl) {
            $tmp = $idl;
            $idl = $idr;
            $idr = $tmp;
        }
        $dao = new PerformanceTestDao();
        $dao->Get([["id",">",$idl],["id","<=",$idr]]);
    }
    
    private function get_param() {
        $case_size = Controller::GetRequest('size');
        if (!$case_size || !is_numeric($case_size)) {
            $case_size = 20;
        }
        $this->case_size = $case_size;
    }
    private $case_size;
}