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
            $this->set_rtn_and_quit('Message', $sample_obj->GetReturn());
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
}