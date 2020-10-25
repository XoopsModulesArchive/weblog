<?php

// XOOPS2 - weBlog+TrackBack 1.30
// TrackBack System ... Turbinado pelo KATARI.BE PHP version 1.00
// Presented by ADMIN @ ROUTE286, 2004.

require_once dirname(__DIR__, 2) . '/mainfile.php';

$mode = 2; // 1 = 承認待ち・2 = アクディブ・3 = 非表示

$point = 'blog_name,title,excerpt,url';

$dead_host = '';

$put_code = 'EUC-JP';

$enco = 1; // 0 = 自動変換のみ・1 = charset を認識する

$pathinfo = 1; // 0 = QUERY_STRING ("?") のみ・1 = PATH_INFO ("/") を用いる

if (('' != $_SERVER['PATH_INFO']) && (1 == $pathinfo)) {
    $pa = explode('/', $_SERVER['PATH_INFO']);

    $path = $pa[1];
} else {
    $path = $_SERVER['QUERY_STRING'];
}

if ('' != $dead_host) {
    $de = explode(',', $dead_host);

    foreach ($de as $de_host) {
        if (mb_strpos($_SERVER['REMOTE_ADDR'], $de_host)
            || mb_strpos($_SERVER['REMOTE_HOST'], $de_host)) {
            echo <<<EOF
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>Access Error</message>
</response>
EOF;

            return;
        }
    }
}

$in = $_REQUEST;

if ('' != $point) {
    $po = explode(',', $point);

    foreach ($po as $po_value) {
        if ('' == $in[$po_value]) {
            echo <<<EOF
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>$po_value is not input</message>
</response>
EOF;

            return;
        }
    }
}

$excerpt = preg_replace("\r", '', $excerpt);
$excerpt = preg_replace("\n", '', $excerpt);

if (1 == $enco) {
    if (('UTF-8' == $in['charset'])
        || ('utf-8' == $in['charset'])) {
        foreach ($in as $in_key => $in_value) {
            $in[$in_key] = mb_convert_encoding($in_value, $put_code, 'UTF-8');
        }
    } elseif (('EUC-JP' == $in['charset'])
              || ('euc-jp' == $in['charset'])) {
        foreach ($in as $in_key => $in_value) {
            $in[$in_key] = mb_convert_encoding($in_value, $put_code, 'EUC-JP');
        }
    } elseif (('Shift_JIS' == $in['charset'])
              || ('SHIFT_JIS' == $in['charset']) || ('shift_jis' == $in['charset']) || ('Shift-JIS' == $in['charset']) || ('SHIFT-JIS' == $in['charset'])
              || ('shift-jis' == $in['charset'])) {
        foreach ($in as $in_key => $in_value) {
            $in[$in_key] = mb_convert_encoding($in_value, $put_code, 'SJIS');
        }
    } else {
        $moji = $in['blog_name'] . $in['title'] . $in['excerpt'];

        $charset = mb_detect_encoding($moji, mb_detect_order(), true);

        foreach ($in as $in_key => $in_value) {
            $in[$in_key] = mb_convert_encoding($in_value, $put_code, $charset);
        }
    }
} else {
    $moji = $in['blog_name'] . $in['title'] . $in['excerpt'];

    $charset = mb_detect_encoding($moji, mb_detect_order(), true);

    foreach ($in as $in_key => $in_value) {
        $in[$in_key] = mb_convert_encoding($in_value, $put_code, $charset);
    }
}

$datetime = time();
$user_ip = $_SERVER['REMOTE_ADDR'];
$blog_name = $in['blog_name'];
$title = $in['title'];
$excerpt = $in['excerpt'];
$tb_url = $in['url'];

$tbl = $xoopsDB->prefix('xoopscomments');
$tbc = $xoopsDB->prefix('weblog');
$tbr = $xoopsDB->prefix('modules');

if (!$dbResult = $xoopsDB->query(
    "select comments from $tbc where blog_id='$path';"
)) {
    echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>DB Error - 3</message>
</response>
END;

    return;
}

[$come] = $xoopsDB->fetchRow($dbResult);
$come++;

$sql = "update $tbc set comments = '$come' where blog_id='$path';";

if (!$result = $xoopsDB->queryF($sql)) {
    echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>DB Error - $sql</message>
</response>
END;

    return;
}

if (!$dbResult = $xoopsDB->query(
    "select mid from $tbr where dirname='weblog';"
)) {
    echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>DB Error - 1</message>
</response>
END;

    return;
}

[$mid] = $xoopsDB->fetchRow($dbResult);

if (!$dbResult = $xoopsDB->query(
    "select max(com_id) from $tbl;"
)) {
    echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>DB Error - 2</message>
</response>
END;

    return;
}

[$num] = $xoopsDB->fetchRow($dbResult);
$num++;

$body = "<a href=\"$tb_url\">" . _BL_TRACKBACK . ": $blog_name</a>\n$excerpt\n";

$sql = "insert into $tbl values " . " ( $num , 0 , $num , $mid , $path ," . " '' , $datetime , $datetime , 0 , '$user_ip' ," . " '$title' , '$body' , 0 , $mode , '' , " . ' 1 , 0 , 0 , 0 , 1 );';

if (!$result = $xoopsDB->queryF($sql)) {
    echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>1</error>
<message>DB Error - $sql</message>
</response>
END;

    return;
}

echo <<<END
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
<error>0</error>
</response>
END;
