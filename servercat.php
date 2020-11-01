<?php
    $servername = "localhost";
    $username = "root";
    $password = "password";
    $dbname = "coco";
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    function getLiveTime()
    {
        $resultset = array();
        $jsonraw = file_get_contents('https://dsjzxgqd48j6m.cloudfront.net/coco-gachi.json');
        $array = json_decode($jsonraw,true);
        $title = $array['upcomingLives'][0]['title'];
        $link = $array['upcomingLives'][0]['link'];
        $date = new Datetime($array['upcomingLives'][0]['date']);
        $date -> setTimezone(new DateTimeZone('+8.0'));

        if($date -> format('Y-m-d H:i:s')=="2021-09-19 21:00:00")
            return NULL;
        else
        {
            $find = "select id from pushcontent where link = '".$link."' and status = 'scheduled'";
            $result=mysqli_query($GLOBALS['conn'], $find);
            while($row = mysqli_fetch_array($result)) 
                array_push($resultset, $row);
            if($resultset==NULL)
            {
                $sql = "insert into pushcontent (title, link, date, status) values ('".$title."', '".$link."', '".$date -> format('Y-m-d H:i:s')."', 'scheduled')";
                mysqli_query($GLOBALS['conn'], $sql);
            }
        }
    }

    function getLastPushContent()
    {
        $resultset = array();
        $result=mysqli_query($GLOBALS['conn'], "select * from pushcontent where id = (select max(id) from pushcontent)");
        while($row = mysqli_fetch_array($result)) 
            array_push($resultset, $row);
        if($resultset==NULL)
            return NULL;
        else
            return $resultset;
    }

    function sc_send( $text , $desp , $key )
    {
        $postdata = http_build_query(
            array(
                'text' => $text,
                'desp' => $desp
            )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        return $result = file_get_contents('http://sc.ftqq.com/'.$key.'.send', false, $context);
    }

    getLiveTime();
    $pushcontent = getLastPushContent();
    $currentdate = date('Y-m-d H:i:s',time());   
    $time = strtotime($pushcontent[0]['date'])-strtotime($currentdate);
    $resultset1 = array();
    if($time<=3600)
    {
        $result=mysqli_query($conn, "select * from scuser");
        while($row = mysqli_fetch_array($result)) 
            array_push($resultset1, $row);
        if(!$resultset1==NULL)
        {
            for($i=1;$i<=count($resultset1);$i++)
            {
                if(($resultset1[$i-1]['pushid']!=$pushcontent[0]['id'] || ($resultset1[$i-1]['pushid']==$pushcontent[0]['id'] && $resultset1[$i-1]['pushstatus']!=1)) && $time<=3600 && $time>1800)
                {
                        sc_send("冲蝗提醒-开播前1小时","标题:".$pushcontent[0]['title']."  链接:".$pushcontent[0]['link']."  开播时间:".$pushcontent[0]['date'],$resultset1[$i-1]['sckey']);
                        $sql = "update scuser set pushid = ".$pushcontent[0]['id'].", lastpush = '".$currentdate."', pushstatus = 1 where id = ".$i;
                        mysqli_query($GLOBALS['conn'], $sql);
                }
                if(($resultset1[$i-1]['pushid']!=$pushcontent[0]['id'] || ($resultset1[$i-1]['pushid']==$pushcontent[0]['id'] && $resultset1[$i-1]['pushstatus']!=2)) && $time<=1800 && $time>600)
                {
                    sc_send("冲蝗提醒-开播前半小时","标题:".$pushcontent[0]['title']."  链接:".$pushcontent[0]['link']."  开播时间:".$pushcontent[0]['date'],$resultset1[$i-1]['sckey']);
                    $sql = "update scuser set pushid = ".$pushcontent[0]['id'].", lastpush = '".$currentdate."', pushstatus = 2 where id = ".$i;
                    mysqli_query($GLOBALS['conn'], $sql);
                }
                if(($resultset1[$i-1]['pushid']!=$pushcontent[0]['id'] || ($resultset1[$i-1]['pushid']==$pushcontent[0]['id'] && $resultset1[$i-1]['pushstatus']!=3)) && $time<=600 && $time>0)
                {
                    sc_send("热车提醒-开播前10分钟","请提前预热  标题:".$pushcontent[0]['title']."  链接:".$pushcontent[0]['link']."  开播时间:".$pushcontent[0]['date'],$resultset1[$i-1]['sckey']);
                    $sql = "update scuser set pushid = ".$pushcontent[0]['id'].", lastpush = '".$currentdate."', pushstatus = 3 where id = ".$i;
                    mysqli_query($GLOBALS['conn'], $sql);
                }
            }
        }
    }
    mysqli_close($conn);
?>