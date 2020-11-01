<?php
    $servername = "localhost";
    $username = "root";
    $password = "password";
    $dbname = "coco";
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
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

    if($_POST['submit'])
    {
        $resultset=array();
        $result=mysqli_query($conn, "select id from scuser where sckey = '".$_POST['key']."'");
        while($row = mysqli_fetch_array($result)) 
        array_push($resultset, $row);
        if($resultset==NULL)
        {
            $sql = "insert into scuser (sckey, lastpush, pushid, pushstatus) values ('".$_POST['key']."', '2000-01-01 00:00:00', 0, 0)";
            mysqli_query($conn, $sql);
            sc_send("订阅成功","已订阅成功  虫皇开播前一小时开始推送详情",$_POST['key']);
            echo "订阅成功,已推送订阅成功信息，没有收到请核对是否已绑定微信或者key是否正确";
        }
        else
            echo "该key已存在,无需重复提交";
    }
    if($_POST['cancel'])
    {
        $resultset=array();
        $result=mysqli_query($conn, "select id from scuser where sckey = '".$_POST['key']."'");
        while($row = mysqli_fetch_array($result)) 
        array_push($resultset, $row);
        if($resultset==NULL)
            echo "没有该key，请核对后输入";
        else
        {
            $sql = "delete from scuser where sckey = '".$_POST['key']."'";
            mysqli_query($conn, $sql);
            sc_send("取消订阅成功","已取消订阅",$_POST['key']);
            echo '取消订阅成功';
        }
    }
    mysqli_close($conn);
?>