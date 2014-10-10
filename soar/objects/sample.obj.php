<?php
class Sample extends Object {
    private $user = "default";
    private $gender = 0;
    public function __construct($user , $gender) {
        $this->user = $user;
        $this->gender = $gender;
    }
    
    public function Greeting() {
        $str = "Hello! ";
        if ($this->gender == 0) {
            $str .= "Mrs. ";
        } else if ($this->gender == 1) {
            $str .= "Mr. ";
        } else {
            $this->SetErrorCode("GENEDER_OUTOF_RANGE" , "normally gender should be 0 or 1");
            return;
        }
        $str .= " ".$this->user;
        $this->SetReturn('msg', $str);
    }
}