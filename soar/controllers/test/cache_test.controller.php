<?php
class CacheTest extends Controller {
    public function test() {
        $rc = new ClusterRedis(["house-madmuc-server01.usask.ca:17016"]);
        var_dump($rc->Get("abc"));
    }
}