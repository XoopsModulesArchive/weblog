<?php

// XOOPS2 - weBlog+TrackBack 1.30
// TrackBack System ... Turbinado pelo KATARI.BE SEND PHP version 1.00
// Presented by ADMIN @ ROUTE286, 2004.

// 更新 Ping 送信先 URL

$default_update = <<<EOF
http://bulkfeeds.net/rpc
http://ping.bloggers.jp/rpc/
http://ping.cocolog-nifty.com/xmlrpc
http://ping.myblog.jp/
http://blog.goo.ne.jp/XMLRPC
EOF;

// charset を加える

$ping_charset = 1; // 0 = 加えない・1 = 加える

// トラックバック Ping を UTF-8 に変換する

$utf8_change = 1; // 0 = 変換しない(EUCで送信)・1 = 変換する(UTF-8で送信)
