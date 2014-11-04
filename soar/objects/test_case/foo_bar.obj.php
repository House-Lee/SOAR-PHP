<?php
namespace SoarObj\TestCase;


class FooBar extends \Object {
    public function doit() {
        self::SetReturn('msg', "SoarObj\\TestCase\\FooBar Pass");
    }
}