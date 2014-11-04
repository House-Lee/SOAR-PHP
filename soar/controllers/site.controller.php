<?php
class Site extends Controller {
    public function index() {
        $user = Controller::GetRequest('user');
        $gender = Controller::GetRequest('gender');
        if ($user == null) {
            $user = "Smith";
        }
        if ($gender == null) {
            $gender = 0;
        }
        $sample_obj = new Sample($user, $gender);
        $sample_obj->Greeting();
        if (!$sample_obj->IsSuccess()) {
            SoarView::set('Message', $sample_obj->GetErrorAdditionMsg());
        } else {
            SoarView::set('Message', $sample_obj->GetReturn());
        }
        SoarView::show('index');
    }
    public function restful() {
        $user = Controller::GetRequest('user');
        $gender = Controller::GetRequest('gender');
        if ($user == null) {
            $user = "Smith";
        }
        if ($gender == null) {
            $gender = 0;
        }
        $sample_obj = new Sample($user, $gender);
        $sample_obj->Greeting();
        if (!$sample_obj->IsSuccess()) {
            $this->set_err_and_quit($sample_obj->GetErrorCode() , $sample_obj->GetErrorAdditionMsg());
        } else {
//             $this->set_rtn_and_quit('Message', $sample_obj->GetReturn());
            $this->set_return('Message', $sample_obj->GetReturn());
            $a = 1;
            $b = 2;
            $c = $a+$b;
            $this->set_return('Result', $c);
        }
    }
    public function authdemo() {
        echo "You can see this information now";
    }
    public function auth() {
        Controller::authorize(array('user_g1') , 30);//grant 30 senconds
        echo "Done";
    }
    public function deauth() {
        Controller::deauthorize();
        echo "OK";
    }
    public function test() {
        $udao = new UserDao();
        $udao->GetOne(1);
        $udao->set_name("exif");
        $udao->set_gender(1);
    }
    public function cset2() {
        SoarCookie::Set("strcookie", "The quick brown fox jumps over the lazy dog" ,20 , true);
    }
    public function cget2() {
        var_dump(SoarCookie::Get("strcookie"));
    }
    public function cset() {
        SoarCookie::Set("just_test", array("formula" => "a+b" , "element" => array(1,2)) , 1000 , true);
    }
    public function cget() {
        var_dump(SoarCookie::Get("just_test"));
    }
    public function cdel() {
        SoarCookie::Delete("just_test");
    }
}