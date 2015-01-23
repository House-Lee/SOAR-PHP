<?php
class TableDriven extends Controller {
    public function Demo() {
        $req = Controller::GetRequest('request');
//         if ($req == 'a') {
//             $a = 2;
//         } else if ($req == 'b') {
//             $a = 3;
//         } else if ($req == 'c') {
//             $a = 4;
//         } else if ($req == 'd') {
//             $a = 78;
//         } else if ($req == 'e') {
//             $a = 87;
//         } else {
//             $a = 0;
//         }
        $map = array(
                        'a' => "do_a", 'b' => "do_b", 'c' => "do_c" , 'd'=>"do_d" , 'e' => "do_e"
                        );
        if (!isset($map[$req])) {
            $a = "0";
        } else {
            $a = $this->{$map[$req]}();
        }
        echo $a."<br>";
    }
    
    private function do_a() {
        return "2";
    }
    
    private function do_b() {
        return "3";
    }
    
    private function do_c() {
        return "4";
    }
    private function do_d() {
        return "5";
    }
    private function do_e() {
        return "6";
    }
}