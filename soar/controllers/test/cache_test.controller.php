<?php
class CacheTest extends Controller {
    public function write() {
        Model::EnableRetrieveResAutoCache();
        $user = new UserDao();
        $user->set_name("NanChen");
        $user->Insert();
    }
    public function read() {
        Model::EnableRetrieveResAutoCache();
        $user = new UserDao();
        $user->GetOne(1);
        echo "<pre>";
        var_dump($user->name());
    }
    public function mread() {
        Model::EnableRetrieveResAutoCache();
        Model::SetResultListCacheTime(50);
        $user = new UserDao();
        $rtn = $user->Get([["gender","in",[0,2]]] , ["name"]);
        echo "<pre>";
        var_dump($rtn);
    }
    public function update() {
        $user = new UserDao();
        $user->GetOne(2);
        $user->set_gender(0);
    }
    public function del() {
        $user = new UserDao();
        $user->DeleteOne(1);
    }
}