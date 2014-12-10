<?php
class AutoLoad extends Controller {
    public function Test() {
        echo "<pre>";
        echo "Testing...\n";
        $obj = new SoarObj\TestCase\FooBar();
        $obj->doit();
        if ($obj->IsSuccess()) {
            echo $obj->GetReturn();
        }
        echo "\n";
        $obj = new FooBar();
        $obj->doit();
        if ($obj->IsSuccess()) {
            echo $obj->GetReturn();
        }
        echo "\n";
        $lib = new SoarLib\TestCase\LooBar();
        $lib->doit();
        if ($lib->IsSuccess()) {
            echo $lib->GetReturn();
        }
        echo "\n";
        $lib = new LooBar();
        $lib->doit();
        if ($lib->IsSuccess()) {
            echo $lib->GetReturn();
        }
        echo "\n";
        $dao = new UserDao();
        $dao->set_name("TestUser");
        $dao->set_gender(1);
        $lastuid = 0;
        if ($dao->Insert()) {
            $lastuid = $dao->GetLastInsertID();
            echo "Model: UserDao Pass, Last UID:".$lastuid;
        } else {
            echo "Model: UserDao Insert Failed";
        }
        echo "\n";
        $dao->GetOne($lastuid);
        echo "Last Inserted:".$dao->name()."\n";
        
        $dao = new ClientsDao();
        $dao->set_name("TestClient");
        $dao->set_gender(1);
        $dao->set_company("Soarnix");
        $lastcid = 0;
        if($dao->Insert()) {
            $lastcid = $dao->GetLastInsertID();
            echo "Model: ClientsDao Pass, Last CID:".$lastcid;
        } else {
            echo "Model: ClientsDao Insert Failed";
        }
        echo "\n";
        
        $dao = new RClientsUserDao();
        $dao->set_uid($lastuid);
        $dao->set_cid($lastcid);
        $dao->set_timestamps(time());
        if($dao->Insert()) {
            $lastrid = $dao->GetLastInsertID();
            echo "Model: RClientsUserDao Pass, Last RID:".$lastrid;
        } else {
            echo "Model: RClientsUserDao Insert Failed";
        }
        echo "\n";
    }
}
