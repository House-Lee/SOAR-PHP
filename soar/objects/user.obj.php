<?php
class User extends Object {
    /*
     * This class illustrates how to collaborate with the model
     */
    public function NewUser($name , $gender) {
        $usrdao = new UserDao();
        if ($name == "" || $gender > 1 || $gender < 0) {
            $this->SetErrorCode('USER_PARAM_INVAILD');
            return;
        }
        $usrdao->set_name($name);
        $usrdao->set_gender($gender);
        if ($usrdao->Insert() == true) {
            $this->SetReturnSuccess();
        } else {
            $this->SetErrorCode('DB_FAILED');
        }
    }
    public function GetUser($uid) {
        $usrdao = new UserDao();
        $usrdao->GetOne($uid);
        /*
         * now can just use $usrdao->set_*attr* to update 
         * or use $usrdao->*attr* to retrieve value
         */
        //do sth...
    }
    public function SearchUser() {
        //Suggest that we want search users whose name start with "Li"
        //and gender is 1 (i.e. male)
        //we could just do the following:
        $usrdao = new UserDao();
        $usrdao->Get(array(
                        array('name' , 'lmatch' , 'Li'),
                        array('gender' , '=' , '1')
                        ));
        //do sth...
    }
}